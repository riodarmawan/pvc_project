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
}
