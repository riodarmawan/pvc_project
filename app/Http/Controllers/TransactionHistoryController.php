<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class TransactionHistoryController extends Controller
{
    /**
     * Menampilkan laporan riwayat transaksi penjualan saja dengan total pendapatan.
     */
    public function index(Request $request)
    {
        // 1. Validasi & Ambil Input Filter
        $filters = $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'branch_id'  => 'nullable|integer|exists:branches,id',
        ]);
        
        $startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : null;
        $endDate   = isset($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : null;
        $branchId  = $filters['branch_id'] ?? null;

        // 2. Base Query Builder untuk Penjualan
        $salesQuery = DB::table('pos_sales as s')
            ->join('branches as b', 's.branch_id', '=', 'b.id')
            ->leftJoin('customers as c', 's.customer_id', '=', 'c.id')
            ->where('s.status', 'PAID')
            ->whereNull('s.project_id'); // Hanya penjualan biasa

        // 3. Apply Filters ke Base Query
        if ($startDate) {
            $salesQuery->where('s.sale_datetime', '>=', $startDate);
        }
        if ($endDate) {
            $salesQuery->where('s.sale_datetime', '<=', $endDate);
        }
        if ($branchId) {
            $salesQuery->where('s.branch_id', $branchId);
        }

        // 4. Hitung Total Pendapatan dengan Query Terpisah
        $totalRevenue = (clone $salesQuery)->sum('s.total');

        // 5. Query untuk Data Transaksi dengan Pagination
        $transactions = $salesQuery
            ->select(
                's.id as transaction_id',
                's.sale_datetime as transaction_date',
                DB::raw("CONCAT('Penjualan POS #', s.id) as description"),
                'c.name as customer_name',
                'b.name as branch_name',
                's.total as transaction_value',
                's.status'
            )
            ->orderBy('s.sale_datetime', 'desc')
            ->paginate(30)
            ->withQueryString();

        // 6. Ambil data untuk dropdown filter
        $branches = DB::table('branches')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();

        // 7. Format Total Revenue untuk Display
        $formattedTotalRevenue = number_format($totalRevenue, 0, ',', '.');
        
        // 8. Hitung statistik tambahan dengan FIX untuk Carbon 3.x float issue
        $statistics = [];
        if ($startDate && $endDate) {
            // FIX: Gunakan abs() dan ceil() untuk mengatasi float precision issue
            $daysDiff = abs(ceil($startDate->diffInDays($endDate, false))) + 1; // +1 untuk inclusive
            
            // Alternatif yang lebih akurat menggunakan pure date calculation
            // $daysDiff = $startDate->copy()->startOfDay()->diffInDays($endDate->copy()->endOfDay()) + 1;
            
            $avgDailyRevenue = $daysDiff > 0 ? $totalRevenue / $daysDiff : 0;
            $transactionCount = (clone $salesQuery)->count();
            $avgTransactionValue = $transactionCount > 0 ? $totalRevenue / $transactionCount : 0;
            
            $statistics = [
                'total_transactions' => $transactionCount,
                'avg_daily_revenue' => number_format($avgDailyRevenue, 0, ',', '.'),
                'avg_transaction_value' => number_format($avgTransactionValue, 0, ',', '.'),
                'period_days' => (int) $daysDiff // Cast ke integer untuk display
            ];
        }

        return view('reports.transactions.index', [
            'title' => 'Laporan Riwayat Penjualan',
            'transactions' => $transactions,
            'branches' => $branches,
            'filters' => $filters,
            'totalRevenue' => $totalRevenue,
            'formattedTotalRevenue' => $formattedTotalRevenue,
            'statistics' => $statistics,
        ]);
    }
}
