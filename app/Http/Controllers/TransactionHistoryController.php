<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class TransactionHistoryController extends Controller
{
    /**
     * Menampilkan laporan riwayat transaksi gabungan dari Penjualan, Retur, dan Proyek.
     *
     * Retur ditampilkan sebagai baris tersendiri bernilai negatif (bukan mengurangi
     * nilai nota aslinya), supaya konsisten dengan pembukuan: penjualan diakui penuh
     * di akun 4100 dan retur dicatat terpisah di 4900 sebagai pengurang. Nota yang
     * sudah dicetak pun tetap cocok dengan yang tercatat di sistem.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'branch_id'  => 'nullable|integer|exists:branches,id',
            'type'       => 'nullable|string|in:sales,projects',
        ]);

        $startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : null;
        $endDate   = isset($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : null;
        $branchId  = $filters['branch_id'] ?? null;
        $type      = $filters['type'] ?? null;

        $transactions = DB::query()
            ->fromSub($this->buildQuery($startDate, $endDate, $branchId, $type), 'transactions')
            ->orderBy('transaction_date', 'desc')
            ->paginate(30)
            ->withQueryString();

        // Ringkasan dihitung atas SELURUH data terfilter, bukan cuma halaman yang tampil.
        $summary = DB::query()
            ->fromSub($this->buildQuery($startDate, $endDate, $branchId, $type), 'transactions')
            ->selectRaw('
                COALESCE(SUM(CASE WHEN transaction_value > 0 THEN transaction_value ELSE 0 END), 0) as total_penjualan,
                COALESCE(SUM(CASE WHEN transaction_value < 0 THEN -transaction_value ELSE 0 END), 0) as total_retur,
                COALESCE(SUM(transaction_value), 0) as total_netto
            ')
            ->first();

        return view('reports.transactions.index', [
            'title'        => 'Laporan Riwayat Transaksi',
            'transactions' => $transactions,
            'summary'      => $summary,
            'branches'     => DB::table('branches')->where('is_active', 1)->orderBy('name')->get(),
            'filters'      => $filters,
        ]);
    }

    /**
     * Bangun query gabungan. Dipanggil dua kali (daftar & ringkasan), jadi harus
     * selalu menghasilkan builder baru — unionAll memodifikasi builder aslinya.
     */
    private function buildQuery($startDate, $endDate, $branchId, ?string $type)
    {
        // Penjualan POS. Status REFUND ikut ditampilkan: penjualannya tetap terjadi,
        // pengurangannya muncul sebagai baris Retur tersendiri.
        $sales = DB::table('pos_sales as s')
            ->join('branches as b', 's.branch_id', '=', 'b.id')
            ->leftJoin('customers as c', 's.customer_id', '=', 'c.id')
            ->whereIn('s.status', ['PAID', 'REFUND'])
            ->whereNull('s.project_id') // penjualan biasa, bukan tagihan proyek
            ->select(
                DB::raw("'Penjualan' as transaction_type"),
                's.id as transaction_id',
                's.sale_datetime as transaction_date',
                DB::raw("CONCAT('Penjualan POS #', s.id) as description"),
                'c.name as customer_name',
                'b.name as branch_name',
                's.total as transaction_value',
                's.status'
            )
            ->when($startDate, fn($q) => $q->where('s.sale_datetime', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('s.sale_datetime', '<=', $endDate))
            ->when($branchId, fn($q) => $q->where('s.branch_id', $branchId));

        // Retur: baris tersendiri, nilainya negatif. Tanggal memakai tanggal retur,
        // bukan tanggal nota, supaya omset per hari mencerminkan kejadian sebenarnya.
        $refunds = DB::table('pos_refunds as r')
            ->join('pos_sales as s', 's.id', '=', 'r.sale_id')
            ->join('branches as b', 's.branch_id', '=', 'b.id')
            ->leftJoin('customers as c', 's.customer_id', '=', 'c.id')
            ->select(
                DB::raw("'Retur' as transaction_type"),
                'r.id as transaction_id',
                'r.created_at as transaction_date',
                DB::raw("CONCAT('Retur penjualan #', s.id) as description"),
                'c.name as customer_name',
                'b.name as branch_name',
                DB::raw('-r.amount as transaction_value'),
                DB::raw("'REFUND' as status")
            )
            ->when($startDate, fn($q) => $q->where('r.created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('r.created_at', '<=', $endDate))
            ->when($branchId, fn($q) => $q->where('s.branch_id', $branchId));

        $projects = DB::table('projects as p')
            ->join('branches as b', 'p.branch_id', '=', 'b.id')
            ->leftJoin('customers as c', 'p.customer_id', '=', 'c.id')
            ->leftJoin('pos_sales as ps', 'ps.project_id', '=', 'p.id')
            ->select(
                DB::raw("'Proyek' as transaction_type"),
                'p.id as transaction_id',
                'p.created_at as transaction_date',
                'p.title as description',
                'c.name as customer_name',
                'b.name as branch_name',
                DB::raw("COALESCE(ps.total, 0) as transaction_value"),
                'p.status'
            )
            ->when($startDate, fn($q) => $q->where('p.created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('p.created_at', '<=', $endDate))
            ->when($branchId, fn($q) => $q->where('p.branch_id', $branchId));

        // Retur selalu menyertai penjualan supaya angka netto tetap utuh saat difilter.
        if ($type === 'projects') {
            return $projects;
        }
        if ($type === 'sales') {
            return $sales->unionAll($refunds);
        }

        return $sales->unionAll($refunds)->unionAll($projects);
    }
}
