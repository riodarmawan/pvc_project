@extends('layouts.dashboard', ['title' => 'Laporan Stok Menyeluruh'])

@section('content')
<div class="space-y-6">

 <!-- Hero Header Section -->

 <!-- Filter Section -->
 <div class="rounded-2xl border bg-white shadow-card border-slate-200 ">
 <div class="p-6 md:p-7">
 <!-- Filter Header -->
 <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
 <div class="flex items-start gap-3">
 <div class="h-10 w-10 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center ">
 <svg width="24" height="24" class="h-5 w-5 text-slate-500 " fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"/>
 </svg>
 </div>
 <div>
 <h3 class="text-base font-semibold">Filter & Pencarian</h3>
 <p class="text-sm text-slate-500 ">Temukan data dengan mudah dan cepat</p>
 </div>
 </div>
 <div>
 <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border bg-slate-100 text-sm border-slate-200 ">
 <span>{{ $stockData->total() }} Records</span>
 </div>
 </div>
 </div>

 <!-- Filter Form -->
 <div class="mt-6">
 <form action="{{ route('reports.stock.index') }}" method="GET" class="space-y-4">
 <div class="grid md:grid-cols-2 gap-4">
 <!-- Branch Filter -->
 <div>
 <label for="branch_id" class="block text-xs uppercase tracking-wide text-slate-500 mb-2">
 <span class="inline-flex items-center gap-2">
 <svg width="16" height="16" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
 </svg>
 Filter Cabang
 </span>
 </label>
 <div class="relative">
 <select name="branch_id" id="branch_id"
 class="w-full appearance-none bg-white border border-slate-200 rounded-xl h-11 px-3 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 ">
 <option value="">🏢 Tampilkan Semua Cabang</option>
 @foreach ($branches as $branch)
 <option value="{{ $branch->id }}" @selected($selectedBranch == $branch->id)>
 {{ $branch->name }}
 </option>
 @endforeach
 </select>
 <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
 <svg width="24" height="24" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
 </svg>
 </div>
 </div>
 </div>

 <!-- Search Input (tidak digunakan) -->
 </div>

 <!-- Action Buttons -->
 <div class="flex flex-wrap items-center gap-3 pt-2">
 <button type="submit"
 class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border bg-slate-100 hover:bg-slate-200 border-slate-200 ">
 <svg width="24" height="24" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"/>
 </svg>
 Terapkan Filter
 </button>

 <a href="{{ route('reports.stock.index') }}"
 class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border hover:bg-slate-100 border-slate-200 ">
 <svg width="24" height="24" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
 </svg>
 Reset Filter
 </a>
 </div>
 </form>
 </div>
 </div>
 </div>

 <!-- Data Table -->
 <div class="rounded-2xl border bg-white shadow-card border-slate-200 ">
 <div class="p-6 md:p-7">
 <!-- Table Header -->
 <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
 <div class="flex items-start gap-3">
 <div class="h-10 w-10 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center ">
 <svg width="24" height="24" class="h-5 w-5 text-slate-500 " fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h2a2 2 0 002-2z"/>
 </svg>
 </div>
 <div>
 <h3 class="text-base font-semibold">Data Inventori</h3>
 <p class="text-sm text-slate-500 ">{{ number_format($stockData->total()) }} total item ditemukan</p>
 </div>
 </div>

 <div>
 <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border bg-slate-100 text-sm border-slate-200 ">
 <span>Halaman {{ $stockData->currentPage() }} dari {{ $stockData->lastPage() }}</span>
 </div>
 </div>
 </div>

 <!-- Responsive Table -->
 <div class="mt-6 overflow-x-auto">
 <table class="w-full text-sm">
 <thead class="text-left text-slate-600 ">
 <tr class="border-b border-slate-200 ">
 <th class="py-3 pr-4 min-w-[160px]">
 <div class="inline-flex items-center gap-2">
 <svg width="20" height="20" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
 </svg>
 SKU Produk
 </div>
 </th>
 <th class="py-3 pr-4 min-w-[220px]">
 <div class="inline-flex items-center gap-2">
 <svg width="20" height="20" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
 </svg>
 Nama Produk
 </div>
 </th>
 <th class="py-3 pr-4 min-w-[180px]">
 <div class="inline-flex items-center gap-2">
 <svg width="20" height="20" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
 </svg>
 Cabang
 </div>
 </th>
 <th class="py-3 pr-4 min-w-[180px]">
 <div class="inline-flex items-center gap-2">
 <svg width="20" height="20" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
 </svg>
 Lokasi
 </div>
 </th>
 <th class="py-3 pr-0 min-w-[220px]">
 <div class="inline-flex items-center gap-2">
 <svg width="20" height="20" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
 </svg>
 Stok Tersedia
 </div>
 </th>
 </tr>
 </thead>
 <tbody class="divide-y divide-slate-200 ">
 @forelse ($stockData as $index => $stock)
 <tr class="hover:bg-slate-50 transition-colors">
 <!-- SKU Column -->
 <td class="py-3 pr-4 align-top">
 <div class="flex items-center gap-3">
 <div class="h-8 w-8 rounded-lg bg-slate-100 border border-slate-200 text-slate-600 text-xs font-semibold grid place-items-center ">
 <span>{{ substr($stock->product_sku, 0, 2) }}</span>
 </div>
 <div class="leading-tight">
 <code class="px-2 py-1 rounded-md bg-slate-100 border border-slate-200 text-xs ">
 {{ $stock->product_sku }}
 </code>
 </div>
 </div>
 </td>

 <!-- Product Name Column -->
 <td class="py-3 pr-4 align-top">
 <div>
 <div class="font-medium">{{ $stock->product_name }}</div>
 <div class="text-xs text-slate-500 ">ID: #{{ $stock->id ?? str_pad($index + 1, 4, '0', STR_PAD_LEFT) }}</div>
 </div>
 </td>

 <!-- Branch Column -->
 <td class="py-3 pr-4 align-top">
 <div class="flex items-center gap-2">
 <div class="h-2.5 w-2.5 rounded-full bg-brand/60"></div>
 <div><span>{{ $stock->branch_name }}</span></div>
 </div>
 </td>

 <!-- Location Column -->
 <td class="py-3 pr-4 align-top">
 <div class="inline-flex items-center gap-2">
 <div class="text-slate-500 ">
 <svg width="16" height="16" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
 </svg>
 </div>
 <span>{{ $stock->location_name }}</span>
 </div>
 </td>

 <!-- Stock Column -->
 <td class="py-3 pr-0 align-top">
 <div class="space-y-2">
 <div class="flex items-baseline gap-2">
 <div class="text-base font-semibold">{{ number_format($stock->qty, 0) }}</div>
 <span class="text-xs text-slate-500 ">unit</span>
 </div>

 @php
 $stockLevel = $stock->qty > 100 ? 'high' : ($stock->qty > 10 ? 'medium' : ($stock->qty > 0 ? 'low' : 'empty'));
 $stockConfig = match($stockLevel) {
 'high' => ['🟢', 'Stok Aman'],
 'medium' => ['🟡', 'Stok Sedang'],
 'low' => ['🔴', 'Stok Rendah'],
 'empty' => ['⚫', 'Stok Habis'],
 };
 @endphp

 <div class="h-2 rounded-full bg-slate-100 border border-slate-200 overflow-hidden "
 data-qty="{{ $stock->qty }}" data-level="{{ $stockLevel }}">
 <div class="h-full w-0"></div>
 </div>

 <span class="inline-flex items-center gap-2 text-xs text-slate-600 ">
 <span>{{ $stockConfig[0] }}</span>
 {{ $stockConfig[1] }}
 </span>
 </div>
 </td>
 </tr>
 @empty
 <tr>
 <td colspan="5" class="py-12">
 <div class="max-w-xl mx-auto text-center space-y-6">
 <!-- Empty State Illustration -->
 <div class="relative mx-auto w-20 h-20">
 <div class="absolute inset-0 rounded-2xl bg-slate-50 border border-slate-200 grid place-items-center ">
 <svg width="64" height="64" class="h-10 w-10 text-slate-500 " fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 009.586 13H7"/>
 </svg>
 </div>
 <div class="absolute -top-2 -right-2 h-6 w-6 rounded-full bg-rose-500 text-white text-xs grid place-items-center">!</div>
 </div>

 <div class="space-y-2">
 <h3 class="text-lg font-semibold">Tidak Ada Data Ditemukan</h3>
 @if($selectedBranch)
 <p class="text-slate-500 ">
 Tidak ada data stok yang ditemukan untuk cabang yang dipilih. Coba ubah filter atau periksa data di cabang lain.
 </p>
 @else
 <p class="text-slate-500 ">
 Belum ada data stok di sistem. Mulai dengan menambahkan produk pertama dan atur stok awal.
 </p>
 @endif
 </div>

 <!-- Action Suggestions -->
 <div class="flex items-center justify-center gap-3">
 <a href="{{ route('products.create') }}"
 class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border bg-slate-100 hover:bg-slate-200 border-slate-200 ">
 <svg width="24" height="24" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
 </svg>
 Tambah Produk
 </a>

 @if($selectedBranch)
 <a href="{{ route('reports.stock.index') }}"
 class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border hover:bg-slate-100 border-slate-200 ">
 <svg width="24" height="24" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
 </svg>
 Lihat Semua Cabang
 </a>
 @endif
 </div>
 </div>
 </td>
 </tr>
 @endforelse
 </tbody>
 </table>
 </div>

 <!-- Pagination -->
 @if($stockData->hasPages())
 <div class="mt-6">
 <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
 <!-- Pagination Info -->
 <div class="flex items-center gap-3 text-sm">
 <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border bg-slate-100 border-slate-200 ">
 <svg width="20" height="20" class="h-4 w-4 text-slate-600 " fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
 </svg>
 <span>
 Menampilkan {{ number_format($stockData->firstItem() ?? 0) }} - {{ number_format($stockData->lastItem() ?? 0) }}
 </span>
 </div>

 <div class="text-slate-400">|</div>

 <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border bg-slate-100 border-slate-200 ">
 <svg width="20" height="20" class="h-4 w-4 text-slate-600 " fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
 </svg>
 <span> dari {{ number_format($stockData->total()) }} total item</span>
 </div>
 </div>

 <!-- Pagination Links -->
 <div class="max-w-none">
 {{ $stockData->links('pagination::tailwind') }}
 </div>
 </div>
 </div>
 @endif
 </div>
 </div>
</div>

<!-- ===== JS Fungsionalitas Halaman Ini (tanpa search) ===== -->
<script>
// Auto-submit saat ganti cabang
(function(){
 const sel = document.getElementById('branch_id');
 if (!sel) return;
 sel.addEventListener('change', () => sel.form?.submit());
})();

// Progress bar stok berdasarkan qty (0–100% cap)
(function(){
 document.querySelectorAll('[data-qty][data-level]').forEach(container => {
 const qty = Number(container.getAttribute('data-qty')) || 0;
 const level = container.getAttribute('data-level');
 const bar = container.querySelector('div');
 if (!bar) return;

 const width = Math.max(0, Math.min(100, qty));
 bar.style.width = width + '%';

 let color = 'rgba(148,163,184,.45)'; // slate (default)
 if (level === 'high') color = 'rgba(34,197,94,.85)'; // emerald
 if (level === 'medium') color = 'rgba(37,99,235,.9)'; // brand (light)
 if (level === 'low') color = 'rgba(244,63,94,.9)'; // rose

 bar.style.background = color;
 });
})();
</script>
@endsection
