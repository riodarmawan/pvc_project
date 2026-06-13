@extends('layouts.dashboard', ['title' => 'Dashboard'])
@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

{{-- Filter Bar --}}
<div class="mb-6 flex flex-wrap items-center gap-4">
    <h2 class="text-lg font-semibold text-slate-800">Dashboard Overview</h2>
    
    <div class="flex items-center gap-3 ml-auto">
        {{-- Branch Filter --}}
        <select id="branchFilter" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            <option value="">Semua Cabang</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                    {{ $branch->name }}
                </option>
            @endforeach
        </select>

        {{-- Date Range Filter --}}
        <select id="dateRangeFilter" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            <option value="today" {{ $dateRange == 'today' ? 'selected' : '' }}>Hari Ini</option>
            <option value="7days" {{ $dateRange == '7days' ? 'selected' : '' }}>7 Hari</option>
            <option value="30days" {{ $dateRange == '30days' ? 'selected' : '' }}>30 Hari</option>
            <option value="month" {{ $dateRange == 'month' ? 'selected' : '' }}>Bulan Ini</option>
        </select>

        {{-- Refresh Button --}}
        <button onclick="location.reload()" class="rounded-xl border border-slate-200 bg-white p-2 hover:bg-slate-50">
            <svg class="h-5 w-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </button>
    </div>
</div>

{{-- Summary Cards --}}
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    {{-- Total Penjualan --}}
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
        <div class="flex items-center gap-3">
            <div class="rounded-xl bg-emerald-100 p-3">
                <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm text-emerald-600">Total Penjualan</p>
                <p class="text-2xl font-bold text-emerald-700">Rp {{ number_format($totalPenjualan, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    {{-- Laba Bersih --}}
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
        <div class="flex items-center gap-3">
            <div class="rounded-xl bg-emerald-100 p-3">
                <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm text-emerald-600">Laba Bersih</p>
                <p class="text-2xl font-bold text-emerald-700">Rp {{ number_format($labaBersih, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    {{-- Stok Menipis --}}
    <div class="rounded-2xl border {{ $stokMenipis > 0 ? 'border-amber-200 bg-amber-50' : 'border-emerald-200 bg-emerald-50' }} p-5">
        <div class="flex items-center gap-3">
            <div class="rounded-xl {{ $stokMenipis > 0 ? 'bg-amber-100' : 'bg-emerald-100' }} p-3">
                <svg class="h-6 w-6 {{ $stokMenipis > 0 ? 'text-amber-600' : 'text-emerald-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <div>
                <p class="text-sm {{ $stokMenipis > 0 ? 'text-amber-600' : 'text-emerald-600' }}">Stok Menipis</p>
                <p class="text-2xl font-bold {{ $stokMenipis > 0 ? 'text-amber-700' : 'text-emerald-700' }}">{{ $stokMenipis }} item</p>
            </div>
        </div>
    </div>

    {{-- Proyek Aktif --}}
    <div class="rounded-2xl border {{ $proyekAktif > 0 ? 'border-blue-200 bg-blue-50' : 'border-emerald-200 bg-emerald-50' }} p-5">
        <div class="flex items-center gap-3">
            <div class="rounded-xl {{ $proyekAktif > 0 ? 'bg-blue-100' : 'bg-emerald-100' }} p-3">
                <svg class="h-6 w-6 {{ $proyekAktif > 0 ? 'text-blue-600' : 'text-emerald-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <div>
                <p class="text-sm {{ $proyekAktif > 0 ? 'text-blue-600' : 'text-emerald-600' }}">Proyek Aktif</p>
                <p class="text-2xl font-bold {{ $proyekAktif > 0 ? 'text-blue-700' : 'text-emerald-700' }}">{{ $proyekAktif }} proyek</p>
            </div>
        </div>
    </div>
</div>

@endsection
