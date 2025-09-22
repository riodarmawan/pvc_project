@extends('layouts.dashboard')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Detail Produk</h1>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Informasi lengkap produk</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('products.index') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-300 
                          hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali
                </a>
                <a href="{{ route('products.edit', $product->id) }}" 
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-600 text-white 
                          hover:bg-amber-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit Produk
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2">
            <!-- Basic Information Card -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-card mb-6">
                <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Informasi Produk</h2>
                        <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium {{ $product->is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400' }}">
                            {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">SKU</label>
                            <div class="font-mono text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $product->sku }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Produk</label>
                            <div class="text-lg font-medium text-slate-900 dark:text-slate-100">{{ $product->name }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Kategori</label>
                            <div class="text-slate-900 dark:text-slate-100">
                                {{ $product->category_name ?? '-' }}
                                @if($product->category_code)
                                <span class="text-sm text-slate-500">({{ $product->category_code }})</span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Satuan</label>
                            <div class="text-slate-900 dark:text-slate-100">
                                {{ $product->uom_name ?? '-' }}
                                @if($product->uom_code)
                                <span class="text-sm text-slate-500">({{ $product->uom_code }})</span>
                                @endif
                            </div>
                        </div>

                        @if($product->barcode)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Barcode</label>
                            <div class="font-mono text-slate-900 dark:text-slate-100">{{ $product->barcode }}</div>
                        </div>
                        @endif

                        @if($hpp)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">HPP</label>
                            <div class="text-lg font-semibold text-green-600 dark:text-green-400">Rp {{ number_format($hpp, 0, ',', '.') }}</div>
                        </div>
                        @endif

                        @if(isset($product->branch_name))
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Cabang</label>
                            <div class="text-slate-900 dark:text-slate-100">{{ $product->branch_name }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Product Attributes Card -->
            @if($product->material || $product->series || $product->pattern_code || $product->finish || $product->length_cm || $product->width_mm || $product->thickness_mm)
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-card mb-6">
                <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Atribut Produk</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @if($product->material)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Material</label>
                            <div class="text-slate-900 dark:text-slate-100">{{ $product->material }}</div>
                        </div>
                        @endif

                        @if($product->series)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Series</label>
                            <div class="text-slate-900 dark:text-slate-100">{{ $product->series }}</div>
                        </div>
                        @endif

                        @if($product->pattern_code)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Pattern Code</label>
                            <div class="text-slate-900 dark:text-slate-100">{{ $product->pattern_code }}</div>
                        </div>
                        @endif

                        @if($product->finish)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Finish</label>
                            <div class="text-slate-900 dark:text-slate-100">{{ $product->finish }}</div>
                        </div>
                        @endif
                    </div>

                    @if($product->length_cm || $product->width_mm || $product->thickness_mm)
                    <div class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-700">
                        <h3 class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">Dimensi Fisik</h3>
                        <div class="grid grid-cols-3 gap-4">
                            @if($product->length_cm)
                            <div>
                                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Panjang</label>
                                <div class="text-slate-900 dark:text-slate-100">{{ $product->length_cm }} cm</div>
                            </div>
                            @endif

                            @if($product->width_mm)
                            <div>
                                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Lebar</label>
                                <div class="text-slate-900 dark:text-slate-100">{{ $product->width_mm }} mm</div>
                            </div>
                            @endif

                            @if($product->thickness_mm)
                            <div>
                                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Ketebalan</label>
                                <div class="text-slate-900 dark:text-slate-100">{{ $product->thickness_mm }} mm</div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Notes Card -->
            @if($product->notes)
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-card">
                <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Catatan</h2>
                </div>
                <div class="p-6">
                    <div class="prose prose-slate dark:prose-invert max-w-none">
                        <p class="text-slate-700 dark:text-slate-300 whitespace-pre-wrap">{{ $product->notes }}</p>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Stock Information Card -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-card">
                <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Informasi Stok</h2>
                </div>
                <div class="p-6">
                    @if($stockInfo['total_stock'] > 0)
                        <div class="text-center mb-4">
                            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                {{ number_format($stockInfo['total_stock']) }}
                            </div>
                            <div class="text-sm text-slate-600 dark:text-slate-400">Total Stok</div>
                        </div>

                        <div class="space-y-3">
                            @foreach($stockInfo['locations'] as $location)
                            <div class="flex justify-between items-center p-3 rounded-lg bg-slate-50 dark:bg-slate-700">
                                <div>
                                    <div class="font-medium text-slate-900 dark:text-slate-100">{{ $location->location_name }}</div>
                                    @if(isset($location->branch_name))
                                    <div class="text-sm text-slate-500 dark:text-slate-400">{{ $location->branch_name }}</div>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <div class="font-bold text-slate-900 dark:text-slate-100">{{ number_format($location->qty) }}</div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4-8-4m16 0v10l-8 4-8-4V7"/>
                            </svg>
                            <div class="text-slate-600 dark:text-slate-400">
                                <div class="font-medium">Stok Kosong</div>
                                <div class="text-sm">Produk ini tidak memiliki stok tersedia</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Actions Card -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-card">
                <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Aksi</h2>
                </div>
                <div class="p-6 space-y-3">
                    <a href="{{ route('products.edit', $product->id) }}" 
                       class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-amber-600 text-white hover:bg-amber-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit Produk
                    </a>

                    <form method="POST" action="{{ route('products.toggle', $product->id) }}" class="w-full">
                        @csrf
                        @method('PATCH')
                        <button type="submit" 
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl border border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700"
                                onclick="return confirm('{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }} produk ini?')">
                            @if($product->is_active)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
                            </svg>
                            Nonaktifkan Produk
                            @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Aktifkan Produk
                            @endif
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
