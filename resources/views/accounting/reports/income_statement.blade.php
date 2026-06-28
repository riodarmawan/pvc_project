@extends('layouts.dashboard', ['title' => 'Laporan Laba Rugi'])

@section('content')
<div class="space-y-6">

 {{-- Filter --}}
 <div class="rounded-2xl border bg-white shadow-card border-slate-200">
 <div class="p-6 md:p-7">
 <div class="flex items-start gap-3 mb-6">
 <div class="h-10 w-10 rounded-lg bg-slate-100 border border-slate-200 grid place-items-center">
 <svg width="24" height="24" class="h-5 w-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"/>
 </svg>
 </div>
 <div>
 <h3 class="text-base font-semibold">Filter Periode</h3>
 <p class="text-sm text-slate-500">Pilih rentang tanggal untuk laporan</p>
 </div>
 </div>

 <form method="GET" action="{{ route('reports.income_statement') }}">
 <div class="grid md:grid-cols-4 gap-4">
 <div class="md:col-span-2">
 <label class="block text-xs uppercase tracking-wide text-slate-500 mb-2">Rentang Tanggal</label>
 <div class="flex items-center gap-2">
 <input type="date" name="date_from" value="{{ request('date_from', date('Y-01-01')) }}"
 class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40">
 <span class="text-slate-500">s/d</span>
 <input type="date" name="date_to" value="{{ request('date_to', date('Y-m-d')) }}"
 class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40">
 </div>
 </div>
 <div class="flex items-end gap-2">
 <button type="submit"
 class="inline-flex items-center gap-2 h-11 px-5 rounded-xl border bg-slate-100 hover:bg-slate-200 border-slate-200 text-sm font-medium transition">
 <svg width="20" height="20" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"/>
 </svg>
 Terapkan
 </button>
 <a href="{{ route('reports.income_statement') }}"
 class="inline-flex items-center gap-2 h-11 px-4 rounded-xl border hover:bg-slate-100 border-slate-200 text-sm transition">
 Reset
 </a>
 </div>
 </div>
 </form>
 </div>
 </div>

 {{-- Income Statement --}}
 <div class="rounded-2xl border bg-white shadow-card border-slate-200">
 <div class="p-6 md:p-7">
 <div class="text-center mb-8">
 <h2 class="text-lg font-bold text-slate-900">Laporan Laba Rugi</h2>
 <p class="text-sm text-slate-500 mt-1">
 Periode: {{ \Carbon\Carbon::parse(request('date_from', date('Y-01-01')))->isoFormat('D MMM Y') }}
 — {{ \Carbon\Carbon::parse(request('date_to', date('Y-m-d')))->isoFormat('D MMM Y') }}
 </p>
 </div>

 <div class="space-y-8">
 {{-- Pendapatan --}}
 <div>
 <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-3 flex items-center gap-2">
 <span class="h-2 w-2 rounded-full bg-green-500"></span>
 Pendapatan
 </h3>
 <div class="rounded-xl border border-slate-200 overflow-hidden">
 <table class="w-full text-sm">
 <thead>
 <tr class="bg-slate-50 border-b border-slate-200">
 <th class="py-2.5 px-4 text-left text-slate-600">Akun</th>
 <th class="py-2.5 px-4 text-right text-slate-600">Nominal</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-slate-100">
 @forelse ($revenueAccounts as $account)
 <tr class="hover:bg-slate-50">
 <td class="py-2.5 px-4">
 <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded mr-2">{{ $account->code }}</code>
 {{ $account->name }}
 </td>
 <td class="py-2.5 px-4 text-right font-medium">Rp {{ number_format($account->total, 0, ',', '.') }}</td>
 </tr>
 @empty
 <tr><td colspan="2" class="py-4 text-center text-slate-400">Tidak ada data</td></tr>
 @endforelse
 </tbody>
 <tfoot>
 <tr class="bg-green-50 border-t border-green-200 font-semibold">
 <td class="py-2.5 px-4 text-green-800">Total Pendapatan</td>
 <td class="py-2.5 px-4 text-right text-green-800">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</td>
 </tr>
 </tfoot>
 </table>
 </div>
 </div>

 {{-- HPP --}}
 <div>
 <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-3 flex items-center gap-2">
 <span class="h-2 w-2 rounded-full bg-amber-500"></span>
 Harga Pokok Penjualan (HPP)
 </h3>
 <div class="rounded-xl border border-slate-200 overflow-hidden">
 <table class="w-full text-sm">
 <thead>
 <tr class="bg-slate-50 border-b border-slate-200">
 <th class="py-2.5 px-4 text-left text-slate-600">Akun</th>
 <th class="py-2.5 px-4 text-right text-slate-600">Nominal</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-slate-100">
 @forelse ($cogsAccounts as $account)
 <tr class="hover:bg-slate-50">
 <td class="py-2.5 px-4">
 <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded mr-2">{{ $account->code }}</code>
 {{ $account->name }}
 </td>
 <td class="py-2.5 px-4 text-right font-medium">Rp {{ number_format($account->total, 0, ',', '.') }}</td>
 </tr>
 @empty
 <tr><td colspan="2" class="py-4 text-center text-slate-400">Tidak ada data</td></tr>
 @endforelse
 </tbody>
 <tfoot>
 <tr class="bg-amber-50 border-t border-amber-200 font-semibold">
 <td class="py-2.5 px-4 text-amber-800">Total HPP</td>
 <td class="py-2.5 px-4 text-right text-amber-800">Rp {{ number_format($totalCogs, 0, ',', '.') }}</td>
 </tr>
 </tfoot>
 </table>
 </div>
 </div>

 {{-- Laba Kotor --}}
 <div class="rounded-xl border-2 border-slate-300 bg-slate-50 p-4">
 <div class="flex items-center justify-between">
 <span class="text-sm font-semibold text-slate-700">Laba Kotor</span>
 <span class="text-base font-bold {{ $grossProfit >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
 Rp {{ number_format($grossProfit, 0, ',', '.') }}
 </span>
 </div>
 </div>

 {{-- Beban Operasional --}}
 <div>
 <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-3 flex items-center gap-2">
 <span class="h-2 w-2 rounded-full bg-orange-500"></span>
 Beban Operasional
 </h3>
 <div class="rounded-xl border border-slate-200 overflow-hidden">
 <table class="w-full text-sm">
 <thead>
 <tr class="bg-slate-50 border-b border-slate-200">
 <th class="py-2.5 px-4 text-left text-slate-600">Akun</th>
 <th class="py-2.5 px-4 text-right text-slate-600">Nominal</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-slate-100">
 @forelse ($expenseAccounts as $account)
 <tr class="hover:bg-slate-50">
 <td class="py-2.5 px-4">
 <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded mr-2">{{ $account->code }}</code>
 {{ $account->name }}
 </td>
 <td class="py-2.5 px-4 text-right font-medium">Rp {{ number_format($account->total, 0, ',', '.') }}</td>
 </tr>
 @empty
 <tr><td colspan="2" class="py-4 text-center text-slate-400">Tidak ada data</td></tr>
 @endforelse
 </tbody>
 <tfoot>
 <tr class="bg-orange-50 border-t border-orange-200 font-semibold">
 <td class="py-2.5 px-4 text-orange-800">Total Beban</td>
 <td class="py-2.5 px-4 text-right text-orange-800">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</td>
 </tr>
 </tfoot>
 </table>
 </div>
 </div>

 {{-- Laba Bersih --}}
 <div class="rounded-xl border-2 {{ $netIncome >= 0 ? 'border-emerald-400 bg-emerald-50' : 'border-rose-400 bg-rose-50' }} p-5">
 <div class="flex items-center justify-between">
 <div>
 <span class="text-sm font-semibold {{ $netIncome >= 0 ? 'text-emerald-800' : 'text-rose-800' }}">LABA BERSIH</span>
 <p class="text-xs {{ $netIncome >= 0 ? 'text-emerald-600' : 'text-rose-600' }} mt-1">Laba Kotor − Beban Operasional</p>
 </div>
 <span class="text-xl font-bold {{ $netIncome >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
 Rp {{ number_format($netIncome, 0, ',', '.') }}
 </span>
 </div>
 </div>
 </div>
 </div>
 </div>
</div>
@endsection