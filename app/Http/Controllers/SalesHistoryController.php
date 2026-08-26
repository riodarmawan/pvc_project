<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;


class SalesHistoryController extends Controller
{
    /** Daftar cabang yang boleh diakses user (default + user_branches) */
    private function allowedBranchIds(): array
    {
        $u = Auth::user();
        $ids = [];

        if ($u && $u->default_branch_id) $ids[] = (int)$u->default_branch_id;

        $extra = DB::table('user_branches')
            ->where('user_id', $u->id ?? 0)
            ->pluck('branch_id')->map(fn($v) => (int)$v)->all();

        $ids = array_values(array_unique(array_merge($ids, $extra)));
        if (empty($ids)) {
            // fallback: izinkan semua? lebih aman kosong → tidak tampil apa2
            $ids = [-1];
        }
        return $ids;
    }

    /** Build query for history data (shared by index + ajaxTable) */
private function buildSalesQuery(array $allowed, int $branchId, Carbon $start, Carbon $end, string $q)
{
    return DB::table('pos_sales as s')
        ->leftJoin('customers as c', 'c.id', '=', 's.customer_id')
        ->join('branches as b', 'b.id', '=', 's.branch_id')
        ->selectRaw("
            s.id, s.sale_datetime, s.total, s.status, s.branch_id,
            b.name as branch_name,
            c.name as customer_name, c.phone as customer_phone,
            (SELECT IFNULL(SUM(l.qty),0) FROM pos_sale_lines l WHERE l.pos_sale_id = s.id) as items_qty
        ")
        ->whereIn('s.branch_id', $allowed)
        ->where('s.branch_id', $branchId)
        ->whereBetween('s.sale_datetime', [$start, $end])
        ->when($q !== '', function ($qq) use ($q) {
            $qq->where(function ($w) use ($q) {
                if (ctype_digit($q)) {
                    $w->orWhere('s.id', (int)$q);
                }
                $w->orWhere('c.name','like',"%{$q}%")
                  ->orWhere('c.phone','like',"%{$q}%");
            });
        })
        ->orderByDesc('s.sale_datetime');
}

/** LIST halaman riwayat */
public function index(Request $r)
{
    $allowed = $this->allowedBranchIds();

    $branchId = (int) ($r->query('branch_id') ?: (Auth::user()->default_branch_id ?? 0));
    if (!in_array($branchId, $allowed, true)) {
        $branchId = $allowed[0];
    }

    $d1 = $r->query('start_date') ?? $r->query('d1');
    $d2 = $r->query('end_date')   ?? $r->query('d2');

    $start = $d1 ? Carbon::parse($d1)->startOfDay() : now()->startOfMonth();
    $end   = $d2 ? Carbon::parse($d2)->endOfDay()   : now()->endOfDay();
    if ($start->gt($end)) { [$start, $end] = [$end, $start]; }

    $q = trim((string) $r->query('q', ''));

    $sales = $this->buildSalesQuery($allowed, $branchId, $start, $end, $q)
        ->paginate(15)
        ->appends([
            'branch_id'  => $branchId,
            'start_date' => $start->toDateString(),
            'end_date'   => $end->toDateString(),
            'q'          => $q,
        ]);

    // Summary stats
    $stats = DB::table('pos_sales as s')
        ->selectRaw('COUNT(*) as total_txn, IFNULL(SUM(s.total),0) as total_rev')
        ->whereIn('s.branch_id', $allowed)
        ->where('s.branch_id', $branchId)
        ->whereBetween('s.sale_datetime', [$start, $end])
        ->first();

    $branches = DB::table('branches')
        ->whereIn('id', $allowed)
        ->orderBy('name')
        ->get();

    return view('kasir.history', [
        'branches'   => $branches,
        'branchId'   => $branchId,
        'start'      => $start->toDateString(),
        'end'        => $end->toDateString(),
        'q'          => $q,
        'sales'      => $sales,
        'totalTxn'   => $stats->total_txn ?? 0,
        'totalRev'   => $stats->total_rev ?? 0,
    ]);
}

/** AJAX table reload (returns partial HTML) */
public function ajaxTable(Request $r)
{
    $allowed = $this->allowedBranchIds();

    $branchId = (int) ($r->query('branch_id') ?: (Auth::user()->default_branch_id ?? 0));
    if (!in_array($branchId, $allowed, true)) {
        $branchId = $allowed[0];
    }

    $d1 = $r->query('start_date');
    $d2 = $r->query('end_date');

    $start = $d1 ? Carbon::parse($d1)->startOfDay() : now()->startOfMonth();
    $end   = $d2 ? Carbon::parse($d2)->endOfDay()   : now()->endOfDay();
    if ($start->gt($end)) { [$start, $end] = [$end, $start]; }

    $q = trim((string) $r->query('q', ''));

    $sales = $this->buildSalesQuery($allowed, $branchId, $start, $end, $q)
        ->paginate(15)
        ->appends([
            'branch_id'  => $branchId,
            'start_date' => $start->toDateString(),
            'end_date'   => $end->toDateString(),
            'q'          => $q,
        ]);

    $stats = DB::table('pos_sales as s')
        ->leftJoin('customers as c', 'c.id', '=', 's.customer_id')
        ->selectRaw('COUNT(*) as total_txn, IFNULL(SUM(s.total),0) as total_rev')
        ->whereIn('s.branch_id', $allowed)
        ->where('s.branch_id', $branchId)
        ->whereBetween('s.sale_datetime', [$start, $end])
        ->when($q !== '', function ($qq) use ($q) {
            $qq->where(function ($w) use ($q) {
                if (ctype_digit($q)) {
                    $w->orWhere('s.id', (int)$q);
                }
                $w->orWhere('c.name','like',"%{$q}%")
                  ->orWhere('c.phone','like',"%{$q}%");
            });
        })
        ->first();

    $tableHtml = view('kasir.partials._history_table', ['sales' => $sales])->render();
    $pagHtml   = $sales->links('vendor.pagination.tailwind')->render();

    return response()->json([
        'ok'       => true,
        'table'    => $tableHtml,
        'pagination' => $pagHtml,
        'totalTxn' => $stats->total_txn ?? 0,
        'totalRev' => (float)($stats->total_rev ?? 0),
    ]);
}

    /** Detail 1 transaksi (AJAX) */
    /**
     * Nota retur milik sebuah penjualan, lengkap dengan itemnya.
     *
     * Satu-satunya sumber data retur untuk invoice, panel detail, dan Nota Retur —
     * supaya angka yang tampil di ketiganya tidak mungkin berbeda. Nilai retur dibaca
     * dari pos_refunds.amount (sudah tersimpan saat retur diproses), bukan dihitung ulang.
     */
    private function refundsOfSale(int $saleId)
    {
        $refunds = DB::table('pos_refunds')
            ->where('sale_id', $saleId)
            ->orderBy('id')
            ->get();

        foreach ($refunds as $r) {
            $r->items = DB::table('pos_refund_lines as rl')
                ->join('pos_sale_lines as sl', 'sl.id', '=', 'rl.pos_sale_line_id')
                ->join('products as p', 'p.id', '=', 'sl.product_id')
                ->where('rl.pos_refund_id', $r->id)
                ->selectRaw('p.sku, p.name, rl.qty, sl.price, (rl.qty * sl.price) as subtotal')
                ->get();
        }

        return $refunds;
    }

    public function detail($id)
    {
        $id = (int) $id;

        $allowed = $this->allowedBranchIds();

        $sale = DB::table('pos_sales as s')
            ->leftJoin('customers as c', 'c.id', '=', 's.customer_id')
            ->join('branches as b', 'b.id', '=', 's.branch_id')
            ->selectRaw('s.*, b.name as branch_name, c.name as customer_name, c.phone as customer_phone')
            ->where('s.id', $id)
            ->first();

        if (!$sale || !in_array((int)$sale->branch_id, $allowed, true)) {
            return response()->json(['ok'=>false,'message'=>'Transaksi tidak ditemukan.'], 404);
        }

        $lines = DB::table('pos_sale_lines as l')
            ->join('products as p', 'p.id', '=', 'l.product_id')
            ->selectRaw('p.sku, p.name, l.qty, l.price, l.subtotal')
            ->where('l.pos_sale_id', $id)->get();

        $pays = DB::table('pos_payments')
            ->select('method','amount','ref_no')
            ->where('pos_sale_id', $id)->get();

        return response()->json([
            'ok'   => true,
            'sale' => [
                'id'        => $sale->id,
                'datetime'  => $sale->sale_datetime,
                'branch'    => $sale->branch_name,
                'status'    => $sale->status,
                'total'     => (float)$sale->total,
                'customer'  => $sale->customer_name,
                'phone'     => $sale->customer_phone,
            ],
            'lines' => $lines,
            'payments' => $pays,
            'refunds'  => $this->refundsOfSale($id)->map(fn ($r) => [
                'id'     => $r->id,
                'date'   => $r->created_at,
                'amount' => (float) $r->amount,
                'reason' => $r->reason,
                'items'  => $r->items,
            ])->values(),
        ]);
    }

    /** Cetak invoice (html sederhana, siap print) */
    public function invoice($id)
    {
        $id = (int)$id;
        $allowed = $this->allowedBranchIds();

        $sale = DB::table('pos_sales as s')
            ->leftJoin('customers as c','c.id','=','s.customer_id')
            ->leftJoin('branches as b','b.id','=','s.branch_id')
            ->selectRaw('s.*, c.name as customer_name, c.phone as customer_phone,
                         c.address as customer_address, b.name as branch_name')
            ->where('s.id', $id)->first();

        if (!$sale || !in_array((int)$sale->branch_id, $allowed, true)) {
            abort(404);
        }

        $lines = DB::table('pos_sale_lines as l')
            ->join('products as p','p.id','=','l.product_id')
            ->where('l.pos_sale_id', $id)
            ->selectRaw('p.sku, p.name, l.qty, l.price, l.subtotal')->get();

        // Metode pembayaran — sebelumnya tidak pernah diambil, jadi tak muncul di nota.
        $pays = DB::table('pos_payments')
            ->select('method', DB::raw('SUM(amount) as amt'))
            ->where('pos_sale_id', $id)->groupBy('method')->get();

        $labelMetode = ['CASH'=>'Tunai','CARD'=>'Kartu','QR'=>'QRIS','TRANSFER'=>'Transfer','CREDIT'=>'Kredit'];

        // pos_sale_lines.price disimpan BRUTO; diskon nota ada di header. Subtotal
        // bruto ditampilkan supaya potongannya terlihat, bukan cuma total akhirnya.
        $discount = (float) ($sale->discount ?? 0);
        $bruto    = (float) $sale->total + $discount;

        // HTML minimal untuk print
        $html  = '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8">';
        $html .= '<title>Invoice #'.$id.'</title>';
        $html .= '<style>body{font-family:ui-sans-serif,system-ui;line-height:1.4;margin:24px}';
        $html .= 'table{width:100%;border-collapse:collapse}th,td{padding:6px;border-bottom:1px solid #eee;text-align:left}';
        $html .= '.right{text-align:right}.muted{color:#667} .h{font-weight:600}</style></head><body>';
        $html .= '<h2>Invoice #'.$id.'</h2>';
        $html .= '<div class="muted">Cabang: '.e($sale->branch_name ?? '-').'</div>';
        $html .= '<div class="muted">Tanggal: '.$sale->sale_datetime.'</div>';
        if ($sale->customer_name) {
            $html .= '<div class="muted">Pelanggan: '.e($sale->customer_name).' '.($sale->customer_phone? '• '.e($sale->customer_phone):'').'</div>';
            if (!empty($sale->customer_address)) {
                $html .= '<div class="muted">Alamat: '.nl2br(e($sale->customer_address)).'</div>';
            }
        }
        $html .= '<hr style="margin:12px 0">';
        $html .= '<table><thead><tr><th>Produk</th><th class="right">Qty</th><th class="right">Harga</th><th class="right">Subtotal</th></tr></thead><tbody>';
        foreach ($lines as $ln) {
            $html .= '<tr><td>'.e($ln->name).' <span class="muted">('.e($ln->sku).')</span></td>';
            $html .= '<td class="right">'.(int)$ln->qty.'</td>';
            $html .= '<td class="right">Rp '.number_format((float)$ln->price,2,',','.').'</td>';
            $html .= '<td class="right">Rp '.number_format((float)$ln->subtotal,2,',','.').'</td></tr>';
        }
        $html .= '</tbody></table>';

        $html .= '<div class="right" style="margin-top:8px">Subtotal: Rp '.number_format($bruto,2,',','.').'</div>';
        if ($discount > 0) {
            $html .= '<div class="right">Diskon: - Rp '.number_format($discount,2,',','.').'</div>';
        }
        $html .= '<div class="right h" style="margin-top:4px">Total: Rp '.number_format((float)$sale->total,2,',','.').'</div>';

        if ($pays->count()) {
            $html .= '<div style="margin-top:12px"><div class="h">Pembayaran</div>';
            foreach ($pays as $p) {
                $nama = $labelMetode[$p->method] ?? $p->method;
                $html .= '<div class="muted">'.e($nama).': Rp '.number_format((float)$p->amt,2,',','.').'</div>';
            }
            $html .= '</div>';
        }

        // Penanda retur. Angka di atas SENGAJA tidak diubah — invoice adalah dokumen
        // sejarah dan cetakan yang dipegang pembeli harus tetap cocok. Retur punya
        // dokumennya sendiri (Nota Retur); di sini cuma dirujuk supaya pembaca tahu
        // invoice ini bukan lagi posisi akhir.
        $refunds = $this->refundsOfSale($id);
        if ($refunds->count()) {
            $totalRetur = $refunds->sum(fn ($r) => (float) $r->amount);

            $html .= '<div style="margin-top:16px;padding:10px;border:1px solid #f0c8c8;background:#fff6f6">';
            $html .= '<div class="h">Sebagian / seluruh barang telah diretur</div>';
            foreach ($refunds as $r) {
                $tgl = \Carbon\Carbon::parse($r->created_at)->format('d/m/Y');
                $html .= '<div class="muted">Nota Retur #'.$r->id.' ('.$tgl.'): Rp '
                      .  number_format((float) $r->amount, 2, ',', '.')
                      .  ($r->reason ? ' — '.e($r->reason) : '').'</div>';
            }
            $html .= '<div style="margin-top:6px">Total diretur: <b>Rp '.number_format($totalRetur,2,',','.').'</b></div>';
            $html .= '<div style="margin-top:6px">Nilai akhir setelah retur: <b>Rp '
                  .  number_format((float)$sale->total - $totalRetur, 2, ',', '.').'</b></div>';
            $html .= '<div class="muted" style="margin-top:6px">Rincian barang yang dikembalikan ada di Nota Retur.</div>';
            $html .= '</div>';
        }
        $html .= '<script>window.print()</script></body></html>';

        return response($html);
    }

    /**
     * Cetak Nota Retur (credit note) — dokumen tersendiri yang mengacu ke invoice asal.
     *
     * Invoice tidak pernah diedit saat ada retur; inilah dokumen yang diterbitkan
     * sebagai gantinya, dan yang jadi bukti pegangan pembeli.
     */
    public function refundNote($refundId)
    {
        $refundId = (int) $refundId;
        $allowed  = $this->allowedBranchIds();

        $refund = DB::table('pos_refunds as r')
            ->join('pos_sales as s', 's.id', '=', 'r.sale_id')
            ->join('branches as b', 'b.id', '=', 's.branch_id')
            ->leftJoin('customers as c', 'c.id', '=', 's.customer_id')
            ->leftJoin('users as u', 'u.id', '=', 'r.approved_by')
            ->where('r.id', $refundId)
            ->selectRaw('r.*, s.id as sale_id, s.sale_datetime, s.branch_id,
                         b.name as branch_name, c.name as customer_name, c.phone as customer_phone,
                         c.address as customer_address, u.full_name as petugas')
            ->first();

        if (!$refund || !in_array((int) $refund->branch_id, $allowed, true)) {
            abort(404);
        }

        $items = DB::table('pos_refund_lines as rl')
            ->join('pos_sale_lines as sl', 'sl.id', '=', 'rl.pos_sale_line_id')
            ->join('products as p', 'p.id', '=', 'sl.product_id')
            ->where('rl.pos_refund_id', $refundId)
            ->selectRaw('p.sku, p.name, rl.qty, sl.price, (rl.qty * sl.price) as subtotal')
            ->get();

        // Metode pembayaran nota asal — dipakai sebagai keterangan "dikembalikan via".
        // ponytail: label metode saja, tanpa rincian nominal per metode. Pembagian
        // proporsional untuk split payment sudah ada di PosController::refundPaymentAllocation;
        // menyalinnya ke sini berisiko dua tempat berbeda hasil. Angkat jadi helper
        // bersama kalau nanti rincian nominalnya memang dibutuhkan di dokumen ini.
        $labelMetode = ['CASH'=>'Tunai','CARD'=>'Kartu','QR'=>'QRIS','TRANSFER'=>'Transfer','CREDIT'=>'Kredit'];
        $metode = DB::table('pos_payments')->where('pos_sale_id', $refund->sale_id)
            ->distinct()->pluck('method')
            ->map(fn ($m) => $labelMetode[$m] ?? $m)->implode(', ');

        $html  = '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8">';
        $html .= '<title>Nota Retur #'.$refundId.'</title>';
        $html .= '<style>body{font-family:ui-sans-serif,system-ui;line-height:1.4;margin:24px}';
        $html .= 'table{width:100%;border-collapse:collapse}th,td{padding:6px;border-bottom:1px solid #eee;text-align:left}';
        $html .= '.right{text-align:right}.muted{color:#667}.h{font-weight:600}</style></head><body>';

        $html .= '<h2>Nota Retur #'.$refundId.'</h2>';
        $html .= '<div class="muted">Atas Invoice #'.$refund->sale_id
              .  ' ('.\Carbon\Carbon::parse($refund->sale_datetime)->format('d/m/Y H:i').')</div>';
        $html .= '<div class="muted">Cabang: '.e($refund->branch_name ?? '-').'</div>';
        $html .= '<div class="muted">Tanggal retur: '.\Carbon\Carbon::parse($refund->created_at)->format('d/m/Y H:i').'</div>';
        if ($refund->customer_name) {
            $html .= '<div class="muted">Pelanggan: '.e($refund->customer_name)
                  .  ($refund->customer_phone ? ' • '.e($refund->customer_phone) : '').'</div>';
            if (!empty($refund->customer_address)) {
                $html .= '<div class="muted">Alamat: '.nl2br(e($refund->customer_address)).'</div>';
            }
        }
        $html .= '<hr style="margin:12px 0">';

        $html .= '<div class="h" style="margin-bottom:6px">Barang yang dikembalikan</div>';
        $html .= '<table><thead><tr><th>Produk</th><th class="right">Qty</th><th class="right">Harga</th><th class="right">Subtotal</th></tr></thead><tbody>';
        if ($items->count()) {
            foreach ($items as $it) {
                $html .= '<tr><td>'.e($it->name).' <span class="muted">('.e($it->sku).')</span></td>';
                $html .= '<td class="right">'.(int) $it->qty.'</td>';
                $html .= '<td class="right">Rp '.number_format((float) $it->price, 2, ',', '.').'</td>';
                $html .= '<td class="right">Rp '.number_format((float) $it->subtotal, 2, ',', '.').'</td></tr>';
            }
        } else {
            // Retur lama dari sebelum fitur retur per-item ada: rinciannya tidak tercatat.
            $html .= '<tr><td colspan="4" class="muted">Seluruh isi nota (rincian per item tidak tercatat).</td></tr>';
        }
        $html .= '</tbody></table>';

        $html .= '<div class="right h" style="margin-top:8px">Total Retur: Rp '
              .  number_format((float) $refund->amount, 2, ',', '.').'</div>';
        if ($metode !== '') {
            $html .= '<div class="right muted">Dikembalikan via: '.e($metode).'</div>';
        }

        $html .= '<div style="margin-top:12px"><span class="h">Alasan:</span> '.e($refund->reason ?: '-').'</div>';
        if (!empty($refund->petugas)) {
            $html .= '<div class="muted">Diproses oleh: '.e($refund->petugas).'</div>';
        }

        $html .= '<script>window.print()</script></body></html>';

        return response($html);
    }
}
