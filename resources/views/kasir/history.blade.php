{{-- resources/views/kasir/history.blade.php --}}
@extends('layouts.app', ['title' => 'Riwayat Transaksi'])

@section('content')
@php
  $branchName = optional(collect($branches)->firstWhere('id', (int)$branchId))->name ?? '—';
  $avgRev = $totalTxn > 0 ? $totalRev / $totalTxn : 0;
@endphp

<div class="max-w-7xl mx-auto px-4 py-6 space-y-5">

  {{-- Header --}}
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-bold text-slate-900">Riwayat Transaksi</h1>
      <p class="text-sm text-slate-500 mt-0.5">Lihat semua penjualan kasir</p>
    </div>
    <div class="text-sm text-slate-500 flex items-center gap-1.5">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      {{ now()->format('d M Y') }}
    </div>
  </div>

  {{-- Summary Cards --}}
  <div class="grid grid-cols-3 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 p-4">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center">
          <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        </div>
        <div>
          <p class="text-xs text-slate-500">Total Transaksi</p>
          <p class="text-xl font-bold text-slate-900" id="stat-txn">{{ number_format($totalTxn) }}</p>
        </div>
      </div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center">
          <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
          <p class="text-xs text-slate-500">Total Pendapatan</p>
          <p class="text-xl font-bold text-emerald-600" id="stat-rev">Rp {{ number_format($totalRev, 0, ',', '.') }}</p>
        </div>
      </div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center">
          <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
        </div>
        <div>
          <p class="text-xs text-slate-500">Rata-rata/Transaksi</p>
          <p class="text-xl font-bold text-slate-900" id="stat-avg">Rp {{ number_format($avgRev, 0, ',', '.') }}</p>
        </div>
      </div>
    </div>
  </div>

  {{-- Filter Bar --}}
  <div class="bg-white rounded-xl border border-slate-200 p-4">
    <form method="get" id="filterForm" class="flex flex-wrap items-end gap-3">
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Cabang</label>
        <select name="branch_id" class="h-9 px-3 text-sm border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
          @foreach($branches as $b)
            <option value="{{ $b->id }}" {{ (int)$branchId === (int)$b->id ? 'selected' : '' }}>[{{ $b->id }}] {{ $b->name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Dari</label>
        <input type="date" name="start_date" value="{{ $start }}" class="h-9 px-3 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Sampai</label>
        <input type="date" name="end_date" value="{{ $end }}" class="h-9 px-3 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
      </div>
      <div class="flex-1 min-w-[200px]">
        <label class="block text-xs font-medium text-slate-600 mb-1">Cari</label>
        <div class="relative">
          <input type="text" name="q" value="{{ $q }}" placeholder="ID, nama, telepon..." id="searchInput"
                 class="h-9 w-full pl-9 pr-3 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
          <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
      </div>
      <button type="submit" class="h-9 px-4 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition">Filter</button>
      <a href="{{ route('kasir.history') }}" class="h-9 px-4 text-sm font-medium text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition">Reset</a>
    </form>
  </div>

  {{-- Table --}}
  <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-slate-200 bg-slate-50/80">
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">ID</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Tanggal</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Cabang</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Pelanggan</th>
            <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Item</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Total</th>
            <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Aksi</th>
          </tr>
        </thead>
        <tbody id="historyTableBody">
          @include('kasir.partials._history_table', ['sales' => $sales])
        </tbody>
      </table>
    </div>
  </div>

  {{-- Pagination --}}
  <div id="historyPagination" class="flex justify-center">
    {{ $sales->links('vendor.pagination.tailwind') }}
  </div>

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/history.js') }}" defer></script>
@endpush
