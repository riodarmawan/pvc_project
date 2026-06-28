@extends('layouts.dashboard', ['title' => 'Jurnal Harian'])

@section('content')
<div class="space-y-6">

 {{-- Filter & Action Bar --}}
 <div class="rounded-2xl border bg-white shadow-card border-slate-200">
 <div class="p-6 md:p-7">
 <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
 <div class="flex items-start gap-3">
 <div class="h-10 w-10 rounded-lg bg-slate-100 border border-slate-200 grid place-items-center">
 <svg width="24" height="24" class="h-5 w-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"/>
 </svg>
 </div>
 <div>
 <h3 class="text-base font-semibold">Filter Jurnal</h3>
 <p class="text-sm text-slate-500">Filter berdasarkan rentang tanggal</p>
 </div>
 </div>
 <a href="{{ route('accounting.journal.create') }}"
 class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-slate-900 text-white hover:bg-slate-800 text-sm font-medium transition">
 <svg width="20" height="20" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
 </svg>
 Buat Jurnal Baru
 </a>
 </div>

 <form method="GET" action="{{ route('accounting.journal') }}" class="mt-6">
 <div class="grid md:grid-cols-4 gap-4">
 <div class="md:col-span-2">
 <label class="block text-xs uppercase tracking-wide text-slate-500 mb-2">Rentang Tanggal</label>
 <div class="flex items-center gap-2">
 <input type="date" name="date_from" value="{{ request('date_from') }}"
 class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40">
 <span class="text-slate-500">s/d</span>
 <input type="date" name="date_to" value="{{ request('date_to') }}"
 class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40">
 </div>
 </div>
 <div class="flex items-end gap-2">
 <button type="submit"
 class="inline-flex items-center gap-2 h-11 px-5 rounded-xl border bg-slate-100 hover:bg-slate-200 border-slate-200 text-sm font-medium transition">
 <svg width="20" height="20" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"/>
 </svg>
 Filter
 </button>
 <a href="{{ route('accounting.journal') }}"
 class="inline-flex items-center gap-2 h-11 px-4 rounded-xl border hover:bg-slate-100 border-slate-200 text-sm transition">
 Reset
 </a>
 </div>
 </div>
 </form>
 </div>
 </div>

 {{-- Journal Entries --}}
 <div class="space-y-4">
 @forelse ($journals as $journal)
 <div class="journal-entry rounded-2xl border bg-white shadow-card border-slate-200 overflow-hidden">
 {{-- Entry Header --}}
 <div class="p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
 <div class="flex items-start gap-4">
 <div class="h-10 w-10 rounded-lg bg-cyan-50 border border-cyan-200 grid place-items-center shrink-0">
 <svg width="24" height="24" class="h-5 w-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
 </svg>
 </div>
 <div>
 <div class="flex items-center gap-3 flex-wrap">
 <span class="font-semibold text-slate-900">#{{ $journal->entry_no }}</span>
 @if($journal->is_posted)
 <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-emerald-100 text-emerald-700">Diposting</span>
 @else
 <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-amber-100 text-amber-700">Draft</span>
 @endif
 </div>
 <p class="text-sm text-slate-600 mt-1">{{ $journal->description }}</p>
 <div class="flex items-center gap-4 mt-2 text-xs text-slate-500">
 <span>{{ \Carbon\Carbon::parse($journal->date)->isoFormat('D MMM Y') }}</span>
 <span>Oleh: {{ $journal->creator_name ?? '-' }}</span>
 </div>
 </div>
 </div>

 <button type="button" onclick="this.closest('.journal-entry').querySelector('.journal-lines').classList.toggle('hidden')"
 class="inline-flex items-center gap-2 h-9 px-3 rounded-lg border border-slate-200 hover:bg-slate-100 text-xs font-medium text-slate-700 transition">
 <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="toggle-icon transition-transform">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
 </svg>
 Detail
 </button>
 </div>

 {{-- Journal Lines (expandable) --}}
 <div class="journal-lines hidden">
 <div class="border-t border-slate-200 px-5 pb-5">
 <table class="w-full text-sm mt-4">
 <thead class="text-left text-slate-500">
 <tr class="border-b border-slate-200">
 <th class="py-2 pr-4">Kode</th>
 <th class="py-2 pr-4">Nama Akun</th>
 <th class="py-2 pr-4 text-right">Debit</th>
 <th class="py-2 pr-0 text-right">Kredit</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-slate-100">
 @foreach($journal->lines as $line)
 <tr>
 <td class="py-2 pr-4">
 <code class="px-2 py-0.5 rounded bg-slate-100 text-xs">{{ $line->account_code ?? '-' }}</code>
 </td>
 <td class="py-2 pr-4">{{ $line->account_name ?? '-' }}</td>
 <td class="py-2 pr-4 text-right font-medium">
 @if($line->debit > 0)
 Rp {{ number_format($line->debit, 0, ',', '.') }}
 @else
 <span class="text-slate-400">—</span>
 @endif
 </td>
 <td class="py-2 pr-0 text-right font-medium">
 @if($line->credit > 0)
 Rp {{ number_format($line->credit, 0, ',', '.') }}
 @else
 <span class="text-slate-400">—</span>
 @endif
 </td>
 </tr>
 @endforeach
 </tbody>
 <tfoot>
 <tr class="border-t-2 border-slate-300 font-semibold">
 <td colspan="2" class="py-2 pr-4 text-right text-slate-600">Total</td>
 <td class="py-2 pr-4 text-right">Rp {{ number_format($journal->lines->sum('debit'), 0, ',', '.') }}</td>
 <td class="py-2 pr-0 text-right">Rp {{ number_format($journal->lines->sum('credit'), 0, ',', '.') }}</td>
 </tr>
 </tfoot>
 </table>
 </div>
 </div>
 </div>
 @empty
 <div class="rounded-2xl border bg-white shadow-card border-slate-200 p-12">
 <div class="text-center space-y-2">
 <p class="font-medium">Belum ada jurnal</p>
 <p class="text-slate-500">Buat jurnal pertama dengan klik tombol "Buat Jurnal Baru".</p>
 </div>
 </div>
 @endforelse
 </div>

</div>
@endsection