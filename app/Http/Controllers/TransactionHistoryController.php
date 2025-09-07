<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class TransactionHistoryController extends Controller
{
    /**
     * Menampilkan laporan riwayat transaksi gabungan dari Penjualan dan Proyek.
     */
    public function index(Request $request)
    {
        // 1. Validasi & Ambil Input Filter
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

        // 2. Query untuk Penjualan (POS Sales Biasa)
        $salesQuery = DB::table('pos_sales as s')
            ->join('branches as b', 's.branch_id', '=', 'b.id')
            ->leftJoin('customers as c', 's.customer_id', '=', 'c.id')
            ->where('s.status', 'PAID')
            ->whereNull('s.project_id') // <-- PENTING: Hanya ambil penjualan biasa, bukan dari proyek
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

        // 3. Query untuk Proyek - LOGIKA BARU SESUAI INVOICE
        // Kita berasumsi setiap proyek memiliki 1 pos_sale yang merepresentasikan total tagihannya.
        $projectsQuery = DB::table('projects as p')
            ->join('branches as b', 'p.branch_id', '=', 'b.id')
            ->leftJoin('customers as c', 'p.customer_id', '=', 'c.id')
            // Bergabung dengan pos_sales yang terkait dengan proyek
            ->leftJoin('pos_sales as ps', 'ps.project_id', '=', 'p.id')
            ->select(
                DB::raw("'Proyek' as transaction_type"),
                'p.id as transaction_id',
                'p.created_at as transaction_date',
                'p.title as description',
                'c.name as customer_name',
                'b.name as branch_name',
                // Ambil nilai total dari pos_sale terkait. Jika tidak ada, anggap 0.
                DB::raw("COALESCE(ps.total, 0) as transaction_value"),
                'p.status'
            )
            ->when($startDate, fn($q) => $q->where('p.created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('p.created_at', '<=', $endDate))
            ->when($branchId, fn($q) => $q->where('p.branch_id', $branchId));
            
        // 4. Gabungkan Query
        if ($type === 'sales') {
            $finalQuery = $salesQuery;
        } elseif ($type === 'projects') {
            $finalQuery = $projectsQuery;
        } else {
            $finalQuery = $salesQuery->unionAll($projectsQuery);
        }
        
        // 5. Urutkan dan paginasi
        $transactions = DB::query()
            ->fromSub($finalQuery, 'transactions')
            ->orderBy('transaction_date', 'desc')
            ->paginate(30)
            ->withQueryString();

        // 6. Ambil data untuk dropdown filter
        $branches = DB::table('branches')->where('is_active', 1)->orderBy('name')->get();

        return view('reports.transactions.index', [
            'title' => 'Laporan Riwayat Transaksi',
            'transactions' => $transactions,
            'branches'     => $branches,
            'filters'      => $filters,
        ]);
    }
}
