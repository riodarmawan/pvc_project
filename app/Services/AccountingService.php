<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AccountingService
{
    /**
     * Generate nomor jurnal otomatis: JRN-YYYYMMDD-XXXX
     */
    public static function generateEntryNo(): string
    {
        $date = now()->format('Ymd');
        $prefix = "JRN-{$date}-";

        $last = DB::table('journal_entries')
            ->where('entry_no', 'LIKE', "{$prefix}%")
            ->orderByDesc('entry_no')
            ->value('entry_no');

        if ($last) {
            $seq = (int) substr($last, -4) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Buat journal entry baru dengan lines.
     * debit dan credit harus seimbang (sama besar).
     *
     * @param array{date:string, description:string, source_type:string|null, source_id:int|null, branch_id:int|null, lines:array} $data
     * @return int journal_entry id
     */
    public static function createEntry(array $data): int
    {
        // Validate: total debit == total credit
        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($data['lines'] as $line) {
            $totalDebit += (float) ($line['debit'] ?? 0);
            $totalCredit += (float) ($line['credit'] ?? 0);
        }

        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new \Exception("Jurnal tidak seimbang: Debit={$totalDebit} != Credit={$totalCredit}");
        }

        // entry_no unik; pada transaksi bersamaan dua proses bisa menghasilkan
        // nomor sama → tabrakan constraint. Coba ulang dengan nomor baru.
        $entryId = null;
        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                $entryId = DB::table('journal_entries')->insertGetId([
                    'entry_no'    => self::generateEntryNo(),
                    'date'        => $data['date'] ?? now()->format('Y-m-d'),
                    'description' => $data['description'],
                    'source_type' => $data['source_type'] ?? null,
                    'source_id'   => $data['source_id'] ?? null,
                    'branch_id'   => $data['branch_id'] ?? null,
                    'created_by'  => Auth::id() ?? $data['created_by'] ?? 1,
                    'is_posted'   => true,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
                break;
            } catch (\Illuminate\Database\QueryException $e) {
                // 1062 = duplicate entry; ulangi dengan nomor baru. Selain itu lempar.
                $code = $e->errorInfo[1] ?? null;
                if ($code !== 1062 || $attempt === 4) {
                    throw $e;
                }
            }
        }

        foreach ($data['lines'] as $line) {
            DB::table('journal_entry_lines')->insert([
                'journal_entry_id' => $entryId,
                'account_id'       => $line['account_id'],
                'debit'            => round((float) ($line['debit'] ?? 0), 2),
                'credit'           => round((float) ($line['credit'] ?? 0), 2),
                'memo'             => $line['memo'] ?? null,
            ]);
        }

        return $entryId;
    }

    /**
     * Jurnal otomatis saat POS Sale finalized (supports split payments)
     *
     * DR: Kas (1100) / Piutang (1200) — per payment method
     * CR: Penjualan Barang (4100) — total
     */
    public static function journalPosSale(int $saleId, float $total, $paymentMethod, ?int $customerId, ?int $branchId, array $payments = []): void
    {
        $kasId      = self::accountId('1100');
        $piutangId  = self::accountId('1200');
        $penjualanId = self::accountId('4100');

        $lines = [];

        // Build DR lines from payments array (split payment support)
        if (!empty($payments)) {
            foreach ($payments as $p) {
                $method = $p['method'] ?? 'CASH';
                $amount = (float) ($p['amount'] ?? 0);
                if ($amount <= 0) continue;

                $isCredit = in_array($method, ['TRANSFER', 'CREDIT']) && $customerId;
                $debitAccountId = $isCredit ? $piutangId : $kasId;

                $lines[] = [
                    'account_id' => $debitAccountId,
                    'debit'      => $amount,
                    'credit'     => 0,
                    'memo'       => "Pembayaran {$method}",
                ];
            }
        } else {
            // Fallback: single payment method (backward compatibility)
            $isCredit = in_array($paymentMethod, ['TRANSFER', 'CREDIT']) && $customerId;
            $debitAccountId = $isCredit ? $piutangId : $kasId;

            $lines[] = [
                'account_id' => $debitAccountId,
                'debit'      => $total,
                'credit'     => 0,
                'memo'       => 'Pembayaran dari customer',
            ];
        }

        // CR Penjualan (total)
        $lines[] = [
            'account_id' => $penjualanId,
            'debit'      => 0,
            'credit'     => $total,
            'memo'       => 'Penjualan barang',
        ];

        self::createEntry([
            'date'        => now()->format('Y-m-d'),
            'description' => "Penjualan POS #{$saleId}",
            'source_type' => 'POS_SALE',
            'source_id'   => $saleId,
            'branch_id'   => $branchId,
            'lines'       => $lines,
        ]);
    }

    /**
     * Jurnal otomatis HPP saat POS Sale
     *
     * DR: COGS (5100)
     * CR: Persediaan (1300)
     */
    public static function journalPosCogs(int $saleId, float $totalCost, ?int $branchId): void
    {
        if ($totalCost <= 0) return;

        $cogsId        = self::accountId('5100');
        $persediaanId  = self::accountId('1300');

        self::createEntry([
            'date'        => now()->format('Y-m-d'),
            'description' => "HPP POS #{$saleId}",
            'source_type' => 'POS_SALE',
            'source_id'   => $saleId,
            'branch_id'   => $branchId,
            'lines'       => [
                ['account_id' => $cogsId,       'debit' => $totalCost, 'credit' => 0, 'memo' => 'Harga pokok penjualan'],
                ['account_id' => $persediaanId, 'debit' => 0, 'credit' => $totalCost, 'memo' => 'Pengurangan persediaan'],
            ],
        ]);
    }

    /**
     * Jurnal otomatis saat POS Refund
     *
     * DR: Retur Penjualan (4900)
     * CR: Kas (1100)
     */
    public static function journalPosRefund(int $refundId, float $amount, ?int $branchId): void
    {
        $returId = self::accountId('4900');
        $kasId   = self::accountId('1100');

        self::createEntry([
            'date'        => now()->format('Y-m-d'),
            'description' => "Retur POS #{$refundId}",
            'source_type' => 'POS_REFUND',
            'source_id'   => $refundId,
            'branch_id'   => $branchId,
            'lines'       => [
                ['account_id' => $returId, 'debit' => $amount, 'credit' => 0, 'memo' => 'Retur penjualan'],
                ['account_id' => $kasId,   'debit' => 0, 'credit' => $amount, 'memo' => 'Pengembalian kas'],
            ],
        ]);
    }

    /**
     * Jurnal pembalik HPP saat retur penjualan (barang kembali ke gudang).
     *
     * DR: Persediaan (1300)
     * CR: HPP (5100)
     */
    public static function journalReturnToInventory(int $refundId, float $totalCost, ?int $branchId): void
    {
        if ($totalCost <= 0) return;

        self::createEntry([
            'date'        => now()->format('Y-m-d'),
            'description' => "Retur ke persediaan #{$refundId}",
            'source_type' => 'POS_REFUND',
            'source_id'   => $refundId,
            'branch_id'   => $branchId,
            'lines'       => [
                ['account_id' => self::accountId('1300'), 'debit' => round($totalCost, 2), 'credit' => 0, 'memo' => 'Barang kembali ke persediaan'],
                ['account_id' => self::accountId('5100'), 'debit' => 0, 'credit' => round($totalCost, 2), 'memo' => 'Pembalikan HPP'],
            ],
        ]);
    }

    /**
     * Jurnal otomatis saat pembelian (GRN)
     *
     * DR: Persediaan (1300)
     * CR: Utang Dagang (2100)
     */
    public static function journalPurchase(int $poId, float $total, ?int $branchId): void
    {
        $persediaanId = self::accountId('1300');
        $utangId      = self::accountId('2100');

        self::createEntry([
            'date'        => now()->format('Y-m-d'),
            'description' => "Pembelian PO #{$poId}",
            'source_type' => 'PURCHASE',
            'source_id'   => $poId,
            'branch_id'   => $branchId,
            'lines'       => [
                ['account_id' => $persediaanId, 'debit' => $total, 'credit' => 0, 'memo' => 'Penerimaan barang'],
                ['account_id' => $utangId,      'debit' => 0, 'credit' => $total, 'memo' => 'Utang ke supplier'],
            ],
        ]);
    }

    /**
     * Jurnal otomatis saat bayar supplier
     *
     * DR: Utang Dagang (2100)
     * CR: Kas (1100)
     */
    public static function journalPaySupplier(int $poId, float $amount, ?int $branchId): void
    {
        $utangId = self::accountId('2100');
        $kasId   = self::accountId('1100');

        self::createEntry([
            'date'        => now()->format('Y-m-d'),
            'description' => "Pembayaran supplier PO #{$poId}",
            'source_type' => 'PAYMENT_SUPPLIER',
            'source_id'   => $poId,
            'branch_id'   => $branchId,
            'lines'       => [
                ['account_id' => $utangId, 'debit' => $amount, 'credit' => 0, 'memo' => 'Pelunasan utang'],
                ['account_id' => $kasId,   'debit' => 0, 'credit' => $amount, 'memo' => 'Pembayaran kas'],
            ],
        ]);
    }

    /**
     * Jurnal otomatis saat bayar beban operasional
     *
     * DR: Beban Operasional (5200)
     * CR: Kas (1100)
     */
    public static function journalExpense(float $amount, string $category, string $memo, ?int $branchId): void
    {
        $bebanId = self::accountId('5200');
        $kasId   = self::accountId('1100');

        self::createEntry([
            'date'        => now()->format('Y-m-d'),
            'description' => "Beban: {$category}",
            'source_type' => 'EXPENSE',
            'source_id'   => null,
            'branch_id'   => $branchId,
            'lines'       => [
                ['account_id' => $bebanId, 'debit' => $amount, 'credit' => 0, 'memo' => $memo],
                ['account_id' => $kasId,   'debit' => 0, 'credit' => $amount, 'memo' => 'Pembayaran kas'],
            ],
        ]);
    }

    /**
     * Jurnal otomatis saat customer bayar tagihan project (AR collection)
     *
     * DR: Kas (1100)
     * CR: Piutang Usaha (1200)
     */
    public static function journalCollectPayment(float $amount, ?int $customerId, ?int $branchId, string $method = 'CASH'): void
    {
        if ($amount <= 0) return;

        // TRANSFER masuk ke Bank, selain itu (CASH/CARD/QR) ke Kas.
        $debitAccountId = $method === 'TRANSFER' ? self::accountId('1110') : self::accountId('1100');
        $piutangId      = self::accountId('1200');

        self::createEntry([
            'date'        => now()->format('Y-m-d'),
            'description' => "Pembayaran customer" . ($customerId ? " #{$customerId}" : ''),
            'source_type' => 'PAYMENT_CUSTOMER',
            'source_id'   => $customerId,
            'branch_id'   => $branchId,
            'lines'       => [
                ['account_id' => $debitAccountId, 'debit' => $amount, 'credit' => 0, 'memo' => "Penerimaan {$method}"],
                ['account_id' => $piutangId,      'debit' => 0, 'credit' => $amount, 'memo' => 'Pelunasan piutang'],
            ],
        ]);
    }

    /**
     * Jurnal akrual saat tagihan proyek diterbitkan (revenue diakui, piutang muncul).
     *
     * DR: Piutang Usaha (1200) sebesar total
     * CR: Penjualan Barang (4100) sebesar nilai barang/material
     * CR: Pendapatan Jasa (4200) sebesar nilai jasa
     */
    public static function journalProjectBilling(int $saleId, float $goodsRevenue, float $serviceRevenue, ?int $customerId, ?int $branchId): void
    {
        $total = round($goodsRevenue + $serviceRevenue, 2);
        if ($total <= 0) return;

        $lines = [
            ['account_id' => self::accountId('1200'), 'debit' => $total, 'credit' => 0, 'memo' => 'Piutang billing proyek'],
        ];
        if ($goodsRevenue > 0) {
            $lines[] = ['account_id' => self::accountId('4100'), 'debit' => 0, 'credit' => round($goodsRevenue, 2), 'memo' => 'Penjualan barang proyek'];
        }
        if ($serviceRevenue > 0) {
            $lines[] = ['account_id' => self::accountId('4200'), 'debit' => 0, 'credit' => round($serviceRevenue, 2), 'memo' => 'Pendapatan jasa instalasi'];
        }

        self::createEntry([
            'date'        => now()->format('Y-m-d'),
            'description' => "Billing Proyek #{$saleId}",
            'source_type' => 'PROJECT_BILLING',
            'source_id'   => $saleId,
            'branch_id'   => $branchId,
            'lines'       => $lines,
        ]);
    }

    /**
     * Jurnal untuk pendapatan jasa instalasi
     *
     * DR: Kas (1100)
     * CR: Pendapatan Jasa (4200)
     */
    public static function journalServiceRevenue(int $sourceId, float $amount, ?int $branchId): void
    {
        $kasId    = self::accountId('1100');
        $jasaId   = self::accountId('4200');

        self::createEntry([
            'date'        => now()->format('Y-m-d'),
            'description' => "Pendapatan jasa #{$sourceId}",
            'source_type' => 'SERVICE',
            'source_id'   => $sourceId,
            'branch_id'   => $branchId,
            'lines'       => [
                ['account_id' => $kasId,  'debit' => $amount, 'credit' => 0, 'memo' => 'Penerimaan kas'],
                ['account_id' => $jasaId, 'debit' => 0, 'credit' => $amount, 'memo' => 'Pendapatan jasa instalasi'],
            ],
        ]);
    }

    /**
     * Get account ID by code
     */
    public static function accountId(string $code): int
    {
        $id = DB::table('chart_of_accounts')->where('code', $code)->value('id');
        if (!$id) {
            throw new \Exception("Akun {$code} tidak ditemukan di COA");
        }
        return (int) $id;
    }

    /**
     * Hitung total HPP dari cart items
     */
    public static function calculateCogs(array $cart): float
    {
        $totalCost = 0;
        foreach ($cart as $pid => $row) {
            $product = DB::table('products')->where('id', $pid)->first();
            if (!$product) continue;
            
            $hpp = 0.0;
            // Prioritas: kolom hpp
            if (!empty($product->hpp) && (float) $product->hpp > 0) {
                $hpp = (float) $product->hpp;
            } elseif ($product->notes && preg_match('/hpp\s*:\s*([0-9\.]+)/i', $product->notes, $matches)) {
                // Fallback: parse dari notes (legacy)
                $hpp = (float) $matches[1];
            }
            
            if ($hpp > 0) {
                $totalCost += $hpp * (float) ($row['qty'] ?? 0);
            }
        }
        return round($totalCost, 2);
    }
}
