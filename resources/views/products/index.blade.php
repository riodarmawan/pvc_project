@extends('layouts.dashboard')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Manajemen Produk</h1>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Kelola data produk dan inventori</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.products.import.form') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-300 
                          hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    Import Excel
                </a>
            </div>
        </div>
    </div>

    <!-- Messages -->
    @if(session('success'))
    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 dark:bg-emerald-900/20 dark:border-emerald-800">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <p class="text-emerald-800 dark:text-emerald-200">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 dark:bg-red-900/20 dark:border-red-800">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            <p class="text-red-800 dark:text-red-200">{{ session('error') }}</p>
        </div>
    </div>
    @endif

    <!-- Filters -->
<!-- Filters -->
<div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-card mb-6 p-4">
    <form method="GET" class="flex flex-col md:flex-row items-stretch md:items-center gap-4">
        <div class="flex-1">
            <input type="text" name="search" value="{{ request('search') }}" 
                   placeholder="Cari SKU, nama produk, material, series, atau barcode..."
                   class="w-full px-4 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                          dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
        </div>
        
        <div class="flex gap-3">
            <!-- Status Filter - BARU -->
            <select name="status" 
                    class="px-4 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                           dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 min-w-[120px]">
                <option value="">Semua Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
            </select>
            
            <!-- Category Filter -->
            <select name="category_id" 
                    class="px-4 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                           dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 min-w-[200px]">
                <option value="">Semua Kategori</option>
                @foreach($categories as $category)
                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
                @endforeach
            </select>
        </div>
        
        <div class="flex gap-2">
            <button type="submit" class="px-6 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700">
                Cari
            </button>
            @if(request()->hasAny(['search', 'category_id', 'status']))
            <a href="{{ route('products.index') }}" class="px-4 py-2 rounded-xl border border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700">
                Reset
            </a>
            @endif
        </div>
    </form>
</div>


    <!-- Products Table -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-card">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="text-left px-6 py-4 font-semibold text-slate-900 dark:text-slate-100">SKU</th>
                        <th class="text-left px-6 py-4 font-semibold text-slate-900 dark:text-slate-100">Nama Produk</th>
                        <th class="text-left px-6 py-4 font-semibold text-slate-900 dark:text-slate-100">Kategori</th>
                        <th class="text-left px-6 py-4 font-semibold text-slate-900 dark:text-slate-100">Material</th>
                        <th class="text-left px-6 py-4 font-semibold text-slate-900 dark:text-slate-100">Satuan</th>
                        <th class="text-left px-6 py-4 font-semibold text-slate-900 dark:text-slate-100">Status</th>
                        <th class="text-center px-6 py-4 font-semibold text-slate-900 dark:text-slate-100">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr class="border-b border-slate-100 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700">
                        <td class="px-6 py-4">
                            <div class="font-mono text-sm font-medium">{{ $product->sku }}</div>
                            @if($product->barcode)
                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $product->barcode }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium">{{ $product->name }}</div>
                            @if($product->series)
                            <div class="text-sm text-slate-600 dark:text-slate-400">{{ $product->series }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                            {{ $product->category_name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                            {{ $product->material ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            {{ $product->uom_name ?? '-' }}
                            @if($product->uom_code)
                            <span class="text-slate-500">({{ $product->uom_code }})</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($product->is_active)
                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                                Aktif
                            </span>
                            @else
                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400">
                                Nonaktif
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('products.show', $product->id) }}" 
                                   class="p-2 rounded-lg hover:bg-blue-100 text-blue-600 dark:hover:bg-blue-900/30"
                                   title="Lihat Detail">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('products.edit', $product->id) }}" 
                                   class="p-2 rounded-lg hover:bg-amber-100 text-amber-600 dark:hover:bg-amber-900/30"
                                   title="Edit Produk">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <form method="POST" action="{{ route('products.toggle', $product->id) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" 
                                            class="p-2 rounded-lg hover:bg-slate-100 text-slate-600 dark:hover:bg-slate-700 dark:text-slate-400"
                                            title="{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"
                                            onclick="return confirm('{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }} produk ini?')">
                                        @if($product->is_active)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
                                        </svg>
                                        @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        @endif
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                            <div class="flex flex-col items-center gap-3">
                                <svg class="w-12 h-12 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4-8-4m16 0v10l-8 4-8-4V7"/>
                                </svg>
                                <div>
                                    <h3 class="font-medium">Tidak ada produk ditemukan</h3>
                                    <p class="text-sm mt-1">Coba ubah filter pencarian atau tambah produk baru</p>
                                </div>
                                <a href="{{ route('products.create') }}" class="mt-2 px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700">
                                    Tambah Produk Pertama
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
            {{ $products->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
