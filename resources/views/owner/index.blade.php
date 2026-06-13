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

{{-- Content will be added in next tasks --}}
@endsection
