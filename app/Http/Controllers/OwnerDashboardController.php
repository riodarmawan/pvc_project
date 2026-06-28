<?php

namespace App\Http\Controllers;

use App\Models\PosSale;
use App\Models\Product;
use App\Models\Project;
use App\Models\StockMove;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnerDashboardController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $request->input('branch_id');
        $dateRange = $request->input('date_range', 'today');

        // Summary cards
        $totalPenjualan = $this->getTotalPenjualan($branchId, $dateRange);
        $labaBersih = $this->getLabaBersih($branchId, $dateRange);
        $stokMenipis = Product::where('is_active', true)
            ->where('id', function ($q) {
                $q->select('product_id')
                    ->from('stock_quants')
                    ->whereColumn('stock_quants.product_id', 'products.id')
                    ->groupBy('product_id')
                    ->havingRaw('SUM(qty) < 10');
            })
            ->count();
        $proyekAktif = Project::whereIn('status', ['IN_PROGRESS', 'ALLOCATED'])->count();

        // Chart data
        $penjualan7Hari = $this->getPenjualan7Hari($branchId);
        $labaBulanan = $this->getLabaBulanan($branchId);

        // Detail data
        $proyekTerbaru = Project::with(['branch', 'customer'])
            ->latest()
            ->limit(5)
            ->get();
        $aktivitas = $this->getRecentActivity();

        // Branches for filter
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('owner.index', compact(
            'totalPenjualan', 'labaBersih', 'stokMenipis', 'proyekAktif',
            'penjualan7Hari', 'labaBulanan', 'proyekTerbaru', 'aktivitas',
            'branches', 'branchId', 'dateRange'
        ));
    }

    private function getTotalPenjualan($branchId, $dateRange)
    {
        $query = PosSale::where('status', 'PAID');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($dateRange === 'today') {
            $query->whereDate('sale_datetime', today());
        } elseif ($dateRange === '7days') {
            $query->where('sale_datetime', '>=', now()->subDays(7));
        } elseif ($dateRange === '30days') {
            $query->where('sale_datetime', '>=', now()->subDays(30));
        } elseif ($dateRange === 'month') {
            $query->whereMonth('sale_datetime', now()->month)
                  ->whereYear('sale_datetime', now()->year);
        }

        return $query->sum('total');
    }

    private function getLabaBersih($branchId, $dateRange)
    {
        // Real profit = Revenue - COGS - Expenses from journal_entry_lines
        $query = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
            ->join('chart_of_accounts as coa', 'coa.id', '=', 'jel.account_id')
            ->where('je.is_posted', true);

        if ($branchId) {
            $query->where('je.branch_id', $branchId);
        }

        if ($dateRange === 'today') {
            $query->whereDate('je.date', today());
        } elseif ($dateRange === '7days') {
            $query->where('je.date', '>=', now()->subDays(7));
        } elseif ($dateRange === '30days') {
            $query->where('je.date', '>=', now()->subDays(30));
        } elseif ($dateRange === 'month') {
            $query->whereMonth('je.date', now()->month)
                  ->whereYear('je.date', now()->year);
        }

        $result = $query->selectRaw("
            SUM(CASE WHEN coa.type = 'REVENUE' THEN jel.credit - jel.debit ELSE 0 END) as revenue,
            SUM(CASE WHEN coa.code = '5100' THEN jel.debit - jel.credit ELSE 0 END) as cogs,
            SUM(CASE WHEN coa.type = 'EXPENSE' AND coa.code != '5100' THEN jel.debit - jel.credit ELSE 0 END) as expenses
        ")->first();

        $revenue  = (float) ($result->revenue ?? 0);
        $cogs     = (float) ($result->cogs ?? 0);
        $expenses = (float) ($result->expenses ?? 0);

        return $revenue - $cogs - $expenses;
    }

    private function getPenjualan7Hari($branchId)
    {
        $query = PosSale::where('status', 'PAID')
            ->where('sale_datetime', '>=', now()->subDays(7));

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->selectRaw('DATE(sale_datetime) as tanggal, SUM(total) as total')
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();
    }

    private function getLabaBulanan($branchId)
    {
        $query = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
            ->join('chart_of_accounts as coa', 'coa.id', '=', 'jel.account_id')
            ->where('je.is_posted', true)
            ->where('je.date', '>=', now()->subMonths(6));

        if ($branchId) {
            $query->where('je.branch_id', $branchId);
        }

        $results = $query->selectRaw("
            DATE_FORMAT(je.date, '%Y-%m') as bulan,
            SUM(CASE WHEN coa.type = 'REVENUE' THEN jel.credit - jel.debit ELSE 0 END) as revenue,
            SUM(CASE WHEN coa.code = '5100' THEN jel.debit - jel.credit ELSE 0 END) as cogs,
            SUM(CASE WHEN coa.type = 'EXPENSE' AND coa.code != '5100' THEN jel.debit - jel.credit ELSE 0 END) as expenses
        ")
        ->groupBy('bulan')
        ->orderBy('bulan')
        ->get();

        return $results->map(function ($row) {
            $row->total = (float) $row->revenue - (float) $row->cogs - (float) $row->expenses;
            return $row;
        });
    }

    private function getRecentActivity()
    {
        $penjualan = PosSale::select('id', 'sale_datetime as created_at', 'total as detail')
            ->where('status', 'PAID')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $item->type = 'penjualan';
                return $item;
            });

        $stockMoves = StockMove::select('id', 'created_at', 'qty as detail')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $item->type = 'stok';
                return $item;
            });

        $projects = Project::select('id', 'created_at', 'title as detail')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $item->type = 'project';
                return $item;
            });

        return $penjualan->merge($stockMoves)->merge($projects)
            ->map(function ($item) {
                if (!($item->created_at instanceof \Carbon\Carbon)) {
                    $item->created_at = \Carbon\Carbon::parse($item->created_at);
                }
                return $item;
            })
            ->sortByDesc('created_at')
            ->take(10)
            ->values();
    }
}
