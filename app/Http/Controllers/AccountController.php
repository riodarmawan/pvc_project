<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function index()
    {
        $accounts = DB::table('chart_of_accounts')
            ->orderBy('code')
            ->get();

        return view('accounting.chart.index', compact('accounts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:10|unique:chart_of_accounts,code',
            'name' => 'required|string|max:100',
            'type' => 'required|in:ASSET,LIABILITY,EQUITY,REVENUE,EXPENSE',
        ]);

        DB::table('chart_of_accounts')->insert(array_merge($data, [
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return back()->with('success', 'Akun berhasil ditambahkan.');
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:ASSET,LIABILITY,EQUITY,REVENUE,EXPENSE',
        ]);

        DB::table('chart_of_accounts')
            ->where('id', $id)
            ->update(array_merge($data, ['updated_at' => now()]));

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Akun berhasil diupdate.']);
        }

        return back()->with('success', 'Akun berhasil diupdate.');
    }

    public function destroy(int $id)
    {
        $used = DB::table('journal_entry_lines')
            ->where('account_id', $id)
            ->exists();

        if ($used) {
            return back()->with('error', 'Akun tidak bisa dihapus karena sudah digunakan di jurnal.');
        }

        DB::table('chart_of_accounts')->where('id', $id)->delete();
        return back()->with('success', 'Akun berhasil dihapus.');
    }
}
