<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JournalController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo   = $request->get('date_to', now()->format('Y-m-d'));

        $journals = DB::table('journal_entries')
            ->join('users', 'users.id', '=', 'journal_entries.created_by')
            ->whereBetween('journal_entries.date', [$dateFrom, $dateTo])
            ->orderByDesc('journal_entries.date')
            ->orderByDesc('journal_entries.id')
            ->select('journal_entries.*', 'users.full_name as creator_name')
            ->get();

        // Attach lines to each entry
        foreach ($journals as $journal) {
            $journal->lines = DB::table('journal_entry_lines')
                ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entry_lines.account_id')
                ->where('journal_entry_lines.journal_entry_id', $journal->id)
                ->select(
                    'journal_entry_lines.*',
                    'chart_of_accounts.code as account_code',
                    'chart_of_accounts.name as account_name'
                )
                ->get();
        }

        return view('accounting.journal.index', compact('journals', 'dateFrom', 'dateTo'));
    }

    public function create()
    {
        $accounts = DB::table('chart_of_accounts')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('accounting.journal.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date'        => 'required|date',
            'description' => 'required|string|max:255',
            'lines'       => 'required|array|min:2',
            'lines.*.account_id' => 'required|integer|exists:chart_of_accounts,id',
            'lines.*.debit'      => 'required|numeric|min:0',
            'lines.*.credit'     => 'required|numeric|min:0',
            'lines.*.memo'       => 'nullable|string|max:255',
        ]);

        // Validate balanced
        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($data['lines'] as $line) {
            $totalDebit += (float) $line['debit'];
            $totalCredit += (float) $line['credit'];
        }

        if (abs($totalDebit - $totalCredit) > 0.01) {
            return back()->withErrors(['lines' => 'Total debit ('.$totalDebit.') tidak sama dengan total credit ('.$totalCredit.').'])->withInput();
        }

        $entryId = \App\Services\AccountingService::createEntry([
            'date'        => $data['date'],
            'description' => $data['description'],
            'lines'       => $data['lines'],
        ]);

        return redirect()->route('accounting.journal')->with('success', "Jurnal #{$entryId} berhasil dibuat.");
    }

    public function post(int $id)
    {
        DB::table('journal_entries')
            ->where('id', $id)
            ->update(['is_posted' => true, 'updated_at' => now()]);

        return back()->with('success', 'Jurnal berhasil diposting.');
    }
}
