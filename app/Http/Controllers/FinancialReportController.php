<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinancialReportController extends Controller
{
    /**
     * Laporan Laba Rugi sederhana
     */
    public function incomeStatement(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo   = $request->get('date_to', now()->format('Y-m-d'));

        // Get posted journal entry line totals per account type
        $lines = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.is_posted', true)
            ->whereBetween('journal_entries.date', [$dateFrom, $dateTo])
            ->select(
                'chart_of_accounts.code',
                'chart_of_accounts.name',
                'chart_of_accounts.type',
                DB::raw('SUM(journal_entry_lines.debit) as total_debit'),
                DB::raw('SUM(journal_entry_lines.credit) as total_credit')
            )
            ->groupBy('chart_of_accounts.code', 'chart_of_accounts.name', 'chart_of_accounts.type')
            ->orderBy('chart_of_accounts.code')
            ->get();

        // Build income statement - match view expectations
        $revenueAccounts = [];
        $cogsAccounts = [];
        $expenseAccounts = [];
        $totalCogs = 0;

        foreach ($lines as $line) {
            $net = abs($line->total_credit - $line->total_debit);

            if ($line->type === 'REVENUE') {
                $revenueAccounts[] = (object) [
                    'code'  => $line->code,
                    'name'  => $line->name,
                    'total' => $net,
                ];
            } elseif ($line->code === '5100') {
                $totalCogs = abs($line->total_debit - $line->total_credit);
                $cogsAccounts[] = (object) [
                    'code'  => $line->code,
                    'name'  => $line->name,
                    'total' => $totalCogs,
                ];
            } elseif ($line->type === 'EXPENSE' && $line->code !== '5100') {
                $expenseAccounts[] = (object) [
                    'code'  => $line->code,
                    'name'  => $line->name,
                    'total' => abs($line->total_debit - $line->total_credit),
                ];
            }
        }

        $totalRevenue  = array_sum(array_map(fn($a) => $a->total, $revenueAccounts));
        $totalExpenses = array_sum(array_map(fn($a) => $a->total, $expenseAccounts));
        $grossProfit   = $totalRevenue - $totalCogs;
        $netIncome     = $grossProfit - $totalExpenses;

        return view('accounting.reports.income_statement', compact(
            'dateFrom', 'dateTo',
            'revenueAccounts', 'cogsAccounts', 'expenseAccounts',
            'totalRevenue', 'totalCogs', 'totalExpenses',
            'grossProfit', 'netIncome'
        ));
    }

    /**
     * Rekonsiliasi kas: saldo buku besar Kas (1100) vs kas operasional
     * (POS tunai + kas masuk - kas keluar) per cabang, untuk mendeteksi selisih.
     */
    public function cashReconciliation(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo   = $request->get('date_to', now()->format('Y-m-d'));
        $startAt  = "{$dateFrom} 00:00:00";
        $endAt    = "{$dateTo} 23:59:59";

        $kasAccountId = DB::table('chart_of_accounts')->where('code', '1100')->value('id');

        $rows = [];
        foreach (DB::table('branches')->where('is_active', 1)->orderBy('name')->get() as $b) {
            $glKas = (float) DB::table('journal_entry_lines as l')
                ->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
                ->where('e.is_posted', true)
                ->where('e.branch_id', $b->id)
                ->where('l.account_id', $kasAccountId)
                ->whereBetween('e.date', [$dateFrom, $dateTo])
                ->selectRaw('COALESCE(SUM(l.debit - l.credit), 0) v')->value('v');

            $posCash = (float) DB::table('pos_payments as p')
                ->join('pos_sales as s', 's.id', '=', 'p.pos_sale_id')
                ->where('s.branch_id', $b->id)->where('s.status', 'PAID')->where('p.method', 'CASH')
                ->whereBetween('s.sale_datetime', [$startAt, $endAt])->sum('p.amount');
            $cashIn = (float) DB::table('cash_movements')->where('branch_id', $b->id)
                ->where('direction', 'IN')->whereBetween('created_at', [$startAt, $endAt])->sum('amount');
            $cashOut = (float) DB::table('cash_movements')->where('branch_id', $b->id)
                ->where('direction', 'OUT')->whereBetween('created_at', [$startAt, $endAt])->sum('amount');
            $operational = $posCash + $cashIn - $cashOut;

            $rows[] = (object) [
                'branch_id'   => (int) $b->id,
                'branch_name' => $b->name,
                'gl_kas'      => round($glKas, 2),
                'operational' => round($operational, 2),
                'selisih'     => round($glKas - $operational, 2),
            ];
        }

        return view('accounting.reports.cash_reconciliation', compact('rows', 'dateFrom', 'dateTo'));
    }
}
