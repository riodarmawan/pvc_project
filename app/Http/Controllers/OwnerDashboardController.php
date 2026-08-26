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

    /**
     * Omset BERSIH: penjualan dikurangi retur pada periode & cabang yang sama.
     * Retur tidak mengurangi pos_sales.total (nota yang sudah dicetak harus tetap
     * cocok), jadi pengurangnya diambil dari pos_refunds. Status REFUND ikut dihitung
     * penjualannya karena transaksinya memang terjadi — pengurangannya sudah lewat retur.
     */
    private function getTotalPenjualan($branchId, $dateRange)
    {
        $penjualan = PosSale::whereIn('status', ['PAID', 'REFUND']);
        if ($branchId) {
            $penjualan->where('branch_id', $branchId);
        }
        $this->applyDateRange($penjualan, $dateRange, 'sale_datetime');

        // Retur dihitung pada TANGGAL RETUR-nya, bukan tanggal nota asli.
        $retur = DB::table('pos_refunds as r')
            ->join('pos_sales as s', 's.id', '=', 'r.sale_id');
        if ($branchId) {
            $retur->where('s.branch_id', $branchId);
        }
        $this->applyDateRange($retur, $dateRange, 'r.created_at');

        return (float) $penjualan->sum('total') - (float) $retur->sum('r.amount');
    }

    /** Filter periode yang dipakai bersama oleh query penjualan & retur. */
    private function applyDateRange($query, $dateRange, string $column)
    {
        if ($dateRange === 'today') {
            $query->whereDate($column, today());
        } elseif ($dateRange === '7days') {
            $query->where($column, '>=', now()->subDays(7));
        } elseif ($dateRange === '30days') {
            $query->where($column, '>=', now()->subDays(30));
        } elseif ($dateRange === 'month') {
            $query->whereMonth($column, now()->month)
                  ->whereYear($column, now()->year);
        }

        return $query;
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

    /** Grafik 7 hari, netto setelah retur — konsisten dengan kartu Total Penjualan. */
    private function getPenjualan7Hari($branchId)
    {
        $sejak = now()->subDays(7);

        $penjualan = PosSale::whereIn('status', ['PAID', 'REFUND'])
            ->where('sale_datetime', '>=', $sejak);
        if ($branchId) {
            $penjualan->where('branch_id', $branchId);
        }
        $penjualan = $penjualan
            ->selectRaw('DATE(sale_datetime) as tanggal, SUM(total) as total')
            ->groupBy('tanggal')->pluck('total', 'tanggal');

        $retur = DB::table('pos_refunds as r')
            ->join('pos_sales as s', 's.id', '=', 'r.sale_id')
            ->where('r.created_at', '>=', $sejak);
        if ($branchId) {
            $retur->where('s.branch_id', $branchId);
        }
        $retur = $retur
            ->selectRaw('DATE(r.created_at) as tanggal, SUM(r.amount) as total')
            ->groupBy('tanggal')->pluck('total', 'tanggal');

        // Gabungkan tanggalnya: hari yang isinya cuma retur pun harus ikut tampil.
        return $penjualan->keys()->merge($retur->keys())->unique()->sort()->values()
            ->map(fn ($tanggal) => (object) [
                'tanggal' => $tanggal,
                'total'   => (float) ($penjualan[$tanggal] ?? 0) - (float) ($retur[$tanggal] ?? 0),
            ]);
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
