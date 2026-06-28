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
            ->selectRaw('s.*, c.name as customer_name, c.phone as customer_phone, b.name as branch_name')
            ->where('s.id', $id)->first();

        if (!$sale || !in_array((int)$sale->branch_id, $allowed, true)) {
            abort(404);
        }

        $lines = DB::table('pos_sale_lines as l')
            ->join('products as p','p.id','=','l.product_id')
            ->where('l.pos_sale_id', $id)
            ->selectRaw('p.sku, p.name, l.qty, l.price, l.subtotal')->get();

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
        }
        $html .= '<hr style="margin:12px 0">';
        $html .= '<table><thead><tr><th>Produk</th><th class="right">Qty</th><th class="right">Harga</th><th class="right">Subtotal</th></tr></thead><tbody>';
        foreach ($lines as $ln) {
            $html .= '<tr><td>'.e($ln->name).' <span class="muted">('.e($ln->sku).')</span></td>';
            $html .= '<td class="right">'.(int)$ln->qty.'</td>';
            $html .= '<td class="right">Rp '.number_format((float)$ln->price,2,',','.').'</td>';
            $html .= '<td class="right">Rp '.number_format((float)$ln->subtotal,2,',','.').'</td></tr>';
        }
        $html .= '</tbody></table><div class="right h" style="margin-top:8px">Total: Rp '.number_format((float)$sale->total,2,',','.').'</div>';
        $html .= '<script>window.print()</script></body></html>';

        return response($html);
    }
}
