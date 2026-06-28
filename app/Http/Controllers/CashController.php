<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CashController extends Controller
{
    /** Metode non-tunai yang kita track di panel khusus */
    private const NON_CASH = ['CARD','QR','TRANSFER'];

    /** Ambil cabang aktif kasir */
    private function branchId(): int
    {
        $u = Auth::user();
        return (int) ($u->default_branch_id ?? 0);
    }

    /** Halaman utama Kas Kasir */
    public function index(Request $r)
    {
        $branchId = $this->branchId();

        $start = $r->get('start_date') ?: now()->subDays(7)->toDateString();
        $end   = $r->get('end_date')   ?: now()->toDateString();
        $startAt = "{$start} 00:00:00";
        $endAt   = "{$end} 23:59:59";

        $summary = $this->summary($branchId, $startAt, $endAt);

        $moves = DB::table('cash_movements as m')
            ->leftJoin('users as u','u.id','=','m.user_id')
            ->selectRaw('m.*, u.full_name as user_name')
            ->where('m.branch_id', $branchId)
            ->whereBetween('m.created_at', [$startAt, $endAt])
            ->orderByDesc('m.id')
            ->paginate(15)
            ->withQueryString();

        return view('kasir.cash.index', [
            'branchId' => $branchId,
            'start'    => $start,
            'end'      => $end,
            'summary'  => $summary,
            'moves'    => $moves,
        ]);
    }

    /** Simpan pengeluaran kas kecil (OUT) */
    public function storeOut(Request $r)
    {
        $r->validate([
            'amount'     => 'required|numeric|min:0.01',
            'category'   => 'required|string|max:30',   // BBM/Parkir/Tol/Makan/Lainnya
            'memo'       => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
        ]);

        $branchId = $this->branchId();

        DB::transaction(function () use ($r, $branchId) {
            DB::table('cash_movements')->insert([
                'branch_id' => $branchId,
                'user_id'   => (int) (Auth::id() ?? 0),
                'direction' => 'OUT',
                'category'  => $r->input('category'),
                'amount'    => round((float)$r->input('amount'), 2),
                'memo'      => trim((string)$r->input('memo', '')),
                'created_at'=> now(),
            ]);

            // === AKUNTANSI: Jurnal pengeluaran kas (atomik dengan mutasi kas) ===
            \App\Services\AccountingService::journalExpense(
                (float)$r->input('amount'),
                $r->input('category'),
                trim((string)$r->input('memo', '')),
                $branchId
            );
        });

        $start = $r->get('start_date') ?: now()->subDays(7)->toDateString();
        $end   = $r->get('end_date')   ?: now()->toDateString();

        return $this->respond($r, [
            'ok'      => true,
            'message' => 'Pengeluaran kas kecil tersimpan.',
            'html'    => $this->panels($branchId, $start, $end),
        ], 'kasir.cash', 'Pengeluaran kas kecil tersimpan.');
    }

    /** Simpan penyesuaian kas (IN/OUT) */
    public function storeAdjust(Request $r)
    {
        $r->validate([
            'direction'  => 'required|in:IN,OUT',
            'amount'     => 'required|numeric|min:0.01',
            'category'   => 'required|string|max:30',  // OPENING/SETOR_BANK/WITHDRAW/LAINNYA
            'memo'       => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
        ]);

        $branchId = $this->branchId();

        DB::transaction(function () use ($r, $branchId) {

        DB::table('cash_movements')->insert([
            'branch_id' => $branchId,
            'user_id'   => (int) (Auth::id() ?? 0),
            'direction' => $r->input('direction'),
            'category'  => $r->input('category'),
            'amount'    => round((float)$r->input('amount'), 2),
            'memo'      => trim((string)$r->input('memo', '')),
            'created_at'=> now(),
        ]);

        // === AKUNTANSI: Jurnal penyesuaian kas (per kategori) ===
        $direction = $r->input('direction');
        $amount    = (float) $r->input('amount');
        $category  = $r->input('category');
        $memo      = trim((string)$r->input('memo', ''));

        $kasId   = \App\Services\AccountingService::accountId('1100');
        $bankId  = \App\Services\AccountingService::accountId('1110');
        $modalId = \App\Services\AccountingService::accountId('3100');
        $priveId = \App\Services\AccountingService::accountId('3200');

        if ($direction === 'IN') {
            switch ($category) {
                case 'OPENING':
                    // Saldo awal: kas masuk dari modal owner
                    // DR Kas, CR Modal
                    \App\Services\AccountingService::createEntry([
                        'date'        => now()->format('Y-m-d'),
                        'description' => "Saldo awal kas: {$memo}",
                        'source_type' => 'CASH_ADJUST',
                        'source_id'   => null,
                        'branch_id'   => $branchId,
                        'lines'       => [
                            ['account_id' => $kasId,   'debit' => $amount, 'credit' => 0, 'memo' => 'Saldo awal'],
                            ['account_id' => $modalId, 'debit' => 0, 'credit' => $amount, 'memo' => 'Modal owner'],
                        ],
                    ]);
                    break;

                case 'TARIK_BANK':
                    // Tarik dari bank: kas naik, bank turun
                    // DR Kas, CR Bank
                    \App\Services\AccountingService::createEntry([
                        'date'        => now()->format('Y-m-d'),
                        'description' => "Tarik dari bank: {$memo}",
                        'source_type' => 'CASH_ADJUST',
                        'source_id'   => null,
                        'branch_id'   => $branchId,
                        'lines'       => [
                            ['account_id' => $kasId,  'debit' => $amount, 'credit' => 0, 'memo' => 'Kas naik'],
                            ['account_id' => $bankId, 'debit' => 0, 'credit' => $amount, 'memo' => 'Bank turun'],
                        ],
                    ]);
                    break;

                case 'SETOR_BANK':
                    // Setor ke bank: kas turun, bank naik
                    // DR Bank, CR Kas
                    \App\Services\AccountingService::createEntry([
                        'date'        => now()->format('Y-m-d'),
                        'description' => "Setor ke bank: {$memo}",
                        'source_type' => 'CASH_ADJUST',
                        'source_id'   => null,
                        'branch_id'   => $branchId,
                        'lines'       => [
                            ['account_id' => $bankId, 'debit' => $amount, 'credit' => 0, 'memo' => 'Bank naik'],
                            ['account_id' => $kasId,  'debit' => 0, 'credit' => $amount, 'memo' => 'Kas turun'],
                        ],
                    ]);
                    break;

                default: // LAINNYA
                    // Kas masuk lainnya: DR Kas, CR Modal
                    \App\Services\AccountingService::createEntry([
                        'date'        => now()->format('Y-m-d'),
                        'description' => "Setor kas: {$category}",
                        'source_type' => 'CASH_ADJUST',
                        'source_id'   => null,
                        'branch_id'   => $branchId,
                        'lines'       => [
                            ['account_id' => $kasId,   'debit' => $amount, 'credit' => 0, 'memo' => $memo],
                            ['account_id' => $modalId, 'debit' => 0, 'credit' => $amount, 'memo' => $category],
                        ],
                    ]);
                    break;
            }
        } else {
            // direction === OUT
            switch ($category) {
                case 'SETOR_BANK':
                    // Setor ke bank dari kas: kas turun, bank naik
                    // DR Bank, CR Kas
                    \App\Services\AccountingService::createEntry([
                        'date'        => now()->format('Y-m-d'),
                        'description' => "Setor ke bank: {$memo}",
                        'source_type' => 'CASH_ADJUST',
                        'source_id'   => null,
                        'branch_id'   => $branchId,
                        'lines'       => [
                            ['account_id' => $bankId, 'debit' => $amount, 'credit' => 0, 'memo' => 'Bank naik'],
                            ['account_id' => $kasId,  'debit' => 0, 'credit' => $amount, 'memo' => 'Kas turun'],
                        ],
                    ]);
                    break;

                case 'TARIK_BANK':
                    // Tarik dari bank ke kas: kas naik, bank turun
                    // DR Kas, CR Bank
                    \App\Services\AccountingService::createEntry([
                        'date'        => now()->format('Y-m-d'),
                        'description' => "Tarik dari bank: {$memo}",
                        'source_type' => 'CASH_ADJUST',
                        'source_id'   => null,
                        'branch_id'   => $branchId,
                        'lines'       => [
                            ['account_id' => $kasId,  'debit' => $amount, 'credit' => 0, 'memo' => 'Kas naik'],
                            ['account_id' => $bankId, 'debit' => 0, 'credit' => $amount, 'memo' => 'Bank turun'],
                        ],
                    ]);
                    break;

                case 'OPENING':
                    // Ambil saldo awal: DR Modal, CR Kas
                    \App\Services\AccountingService::createEntry([
                        'date'        => now()->format('Y-m-d'),
                        'description' => "Penarikan saldo awal: {$memo}",
                        'source_type' => 'CASH_ADJUST',
                        'source_id'   => null,
                        'branch_id'   => $branchId,
                        'lines'       => [
                            ['account_id' => $modalId, 'debit' => $amount, 'credit' => 0, 'memo' => 'Penarikan modal'],
                            ['account_id' => $kasId,   'debit' => 0, 'credit' => $amount, 'memo' => 'Kas turun'],
                        ],
                    ]);
                    break;

                default: // LAINNYA
                    // Kas keluar lainnya: DR Prive, CR Kas
                    \App\Services\AccountingService::createEntry([
                        'date'        => now()->format('Y-m-d'),
                        'description' => "Penarikan kas: {$category}",
                        'source_type' => 'CASH_ADJUST',
                        'source_id'   => null,
                        'branch_id'   => $branchId,
                        'lines'       => [
                            ['account_id' => $priveId, 'debit' => $amount, 'credit' => 0, 'memo' => $category],
                            ['account_id' => $kasId,   'debit' => 0, 'credit' => $amount, 'memo' => $memo],
                        ],
                    ]);
                    break;
            }
        }

        });

        $start = $r->get('start_date') ?: now()->subDays(7)->toDateString();
        $end   = $r->get('end_date')   ?: now()->toDateString();

        return $this->respond($r, [
            'ok'      => true,
            'message' => 'Penyesuaian kas tersimpan.',
            'html'    => $this->panels($branchId, $start, $end),
        ], 'kasir.cash', 'Penyesuaian kas tersimpan.');
    }

    /* ==================== Helpers ==================== */

    /**
     * Hitung saldo kas saat ini (all-time) + ringkasan periode.
     * - Saldo kas (uang fisik) = POS(CASH all-time) + IN(all-time) - OUT(all-time)
     * - Panel periode:
     *     • pos_cash      : total pembayaran POS metode CASH dalam rentang tanggal
     *     • pos_non_cash  : total pembayaran POS metode CARD/QR/TRANSFER dalam rentang tanggal
     *     • in / out / net: dari cash_movements dalam rentang tanggal (net = pos_cash + in - out)
     */
    private function summary(int $branchId, string $startAt, string $endAt): array
    {
        // Saldo kas saat ini (all-time)
        $posCashAll = DB::table('pos_payments as p')
            ->join('pos_sales as s','s.id','=','p.pos_sale_id')
            ->where('s.branch_id', $branchId)
            ->where('s.status','PAID')
            ->where('p.method','CASH')
            ->sum('p.amount');

        $inAll  = DB::table('cash_movements')
            ->where('branch_id',$branchId)->where('direction','IN')->sum('amount');
        $outAll = DB::table('cash_movements')
            ->where('branch_id',$branchId)->where('direction','OUT')->sum('amount');

        $saldo  = round($posCashAll + $inAll - $outAll, 2);

        // Ringkasan periode (filter tanggal)
        $posCashPeriod = DB::table('pos_payments as p')
            ->join('pos_sales as s','s.id','=','p.pos_sale_id')
            ->where('s.branch_id', $branchId)
            ->where('s.status','PAID')
            ->where('p.method','CASH')
            ->whereBetween('s.sale_datetime', [$startAt, $endAt])
            ->sum('p.amount');

        $posNonCashPeriod = DB::table('pos_payments as p')
            ->join('pos_sales as s','s.id','=','p.pos_sale_id')
            ->where('s.branch_id', $branchId)
            ->where('s.status','PAID')
            ->whereIn('p.method', self::NON_CASH)
            ->whereBetween('s.sale_datetime', [$startAt, $endAt])
            ->sum('p.amount');

        $inPeriod = DB::table('cash_movements')
            ->where('branch_id',$branchId)->where('direction','IN')
            ->whereBetween('created_at', [$startAt,$endAt])->sum('amount');

        $outPeriod = DB::table('cash_movements')
            ->where('branch_id',$branchId)->where('direction','OUT')
            ->whereBetween('created_at', [$startAt,$endAt])->sum('amount');

        return [
            'saldo'        => $saldo,
            'pos_cash'     => round($posCashPeriod,2),
            'pos_non_cash' => round($posNonCashPeriod,2),
            'in'           => round($inPeriod,2),
            'out'          => round($outPeriod,2),
            // Net kas = arus kas periode (yang mempengaruhi uang fisik)
            'net'          => round($posCashPeriod + $inPeriod - $outPeriod, 2),
        ];
    }

    /** Render ulang panel (summary + table) untuk AJAX */
    private function panels(int $branchId, string $start, string $end): array
    {
        $startAt = "{$start} 00:00:00";
        $endAt   = "{$end} 23:59:59";

        $summary = $this->summary($branchId, $startAt, $endAt);

        $moves = DB::table('cash_movements as m')
            ->leftJoin('users as u','u.id','=','m.user_id')
            ->selectRaw('m.*, u.full_name as user_name')
            ->where('m.branch_id', $branchId)
            ->whereBetween('m.created_at', [$startAt, $endAt])
            ->orderByDesc('m.id')
            ->paginate(15)
            ->withQueryString();

        return [
            'summary' => view('kasir.cash._summary', [
                'branchId' => $branchId,
                'start'    => $start,
                'end'      => $end,
                'summary'  => $summary,
            ])->render(),
            'table'   => view('kasir.cash._table', [
                'moves' => $moves,
            ])->render(),
        ];
    }

    /** JSON/Redirect helper */
    private function respond(Request $req, array $jsonPayload, string $redirectRoute, string $flash = null)
    {
        if ($req->expectsJson() || $req->ajax()) {
            return response()->json($jsonPayload);
        }
        if ($flash) session()->flash('success', $flash);
        return redirect()->route($redirectRoute);
    }
}
