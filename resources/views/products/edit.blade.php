@extends('layouts.dashboard')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Edit Produk</h1>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Ubah informasi produk</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('products.show', $product->id) }}" 
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-300 
                          hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    Lihat Detail
                </a>
                <a href="{{ route('products.index') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-300 
                          hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali ke Daftar
                </a>
            </div>
        </div>
    </div>

    <!-- Transaction Warning -->
    @if(isset($transactionCheck) && $transactionCheck['has_transactions'])
    <div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200 dark:bg-amber-900/20 dark:border-amber-800">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            <div>
                <h3 class="font-semibold text-amber-800 dark:text-amber-400">Perhatian - Produk Memiliki Transaksi</h3>
                <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                    Produk ini memiliki riwayat transaksi: <strong>{{ implode(', ', $transactionCheck['transaction_types']) }}</strong>.
                    SKU dan Satuan (UOM) tidak dapat diubah untuk menjaga integritas data.
                </p>
            </div>
        </div>
    </div>
    @endif

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

    <!-- Form Card -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-card">
        <div class="p-6">
            <form action="{{ route('products.update', $product->id) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Basic Information Section -->
                <div class="border-b border-slate-200 dark:border-slate-700 pb-6">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">Informasi Dasar</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- SKU (Read-only) -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                SKU <span class="text-slate-500">(Tidak dapat diubah)</span>
                            </label>
                            <input type="text" value="{{ $product->sku }}" readonly
                                   class="w-full px-4 py-3 rounded-xl border border-slate-300 bg-slate-50 text-slate-500
                                          dark:border-slate-600 dark:bg-slate-700 dark:text-slate-400">
                        </div>

                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Nama Produk <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="name" name="name" value="{{ old('name', $product->name) }}" required
                                   class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                          dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 
                                          @error('name') border-red-500 @enderror">
                            @error('name')
                            <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Category -->
                        <div>
                            <label for="category_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Kategori <span class="text-red-500">*</span>
                            </label>
                            <select id="category_id" name="category_id" required
                                    class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                           dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 
                                           @error('category_id') border-red-500 @enderror">
                                <option value="">Pilih Kategori</option>
                                @foreach($categories as $category)
                                <option value="{{ $category->id }}" 
                                        {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('category_id')
                            <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- UOM -->
                        <div>
                            <label for="uom_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Satuan (UOM) <span class="text-red-500">*</span>
                                @if(isset($transactionCheck) && $transactionCheck['has_transactions'])
                                <span class="text-amber-600 dark:text-amber-400 text-xs">(Dibatasi karena ada transaksi)</span>
                                @endif
                            </label>
                            <select id="uom_id" name="uom_id" required
                                    @if(isset($transactionCheck) && $transactionCheck['has_transactions']) disabled @endif
                                    class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                           dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 
                                           @if(isset($transactionCheck) && $transactionCheck['has_transactions']) bg-slate-50 dark:bg-slate-600 @endif
                                           @error('uom_id') border-red-500 @enderror">
                                <option value="">Pilih Satuan</option>
                                @foreach($uoms as $uom)
                                <option value="{{ $uom->id }}" 
                                        {{ old('uom_id', $product->uom_id) == $uom->id ? 'selected' : '' }}>
                                    {{ $uom->name }} ({{ $uom->code }})
                                </option>
                                @endforeach
                            </select>
                            @if(isset($transactionCheck) && $transactionCheck['has_transactions'])
                            <input type="hidden" name="uom_id" value="{{ $product->uom_id }}">
                            @endif
                            @error('uom_id')
                            <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Barcode -->
                        <div>
                            <label for="barcode" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Barcode
                            </label>
                            <input type="text" id="barcode" name="barcode" value="{{ old('barcode', $product->barcode) }}"
                                   class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                          dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 
                                          @error('barcode') border-red-500 @enderror">
                            @error('barcode')
                            <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- HPP -->
                        <div>
                            <label for="hpp" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                HPP (Harga Pokok Penjualan)
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-500">Rp</span>
                                <input type="number" id="hpp" name="hpp" step="0.01" min="0" 
                                       value="{{ old('hpp', $hpp) }}"
                                       class="w-full pl-12 pr-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                              dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 
                                              @error('hpp') border-red-500 @enderror">
                            </div>
                            @error('hpp')
                            <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Product Attributes Section -->
                <div class="border-b border-slate-200 dark:border-slate-700 pb-6">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">Atribut Produk</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Material -->
                        <div>
                            <label for="material" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Material
                            </label>
                            <input type="text" id="material" name="material" value="{{ old('material', $product->material) }}"
                                   class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                          dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 
                                          @error('material') border-red-500 @enderror">
                            @error('material')
                            <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Series -->
                        <div>
                            <label for="series" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Series
                            </label>
                            <input type="text" id="series" name="series" value="{{ old('series', $product->series) }}"
                                   class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                          dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 
                                          @error('series') border-red-500 @enderror">
                            @error('series')
                            <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Pattern Code -->
                        <div>
                            <label for="pattern_code" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Pattern Code
                            </label>
                            <input type="text" id="pattern_code" name="pattern_code" value="{{ old('pattern_code', $product->pattern_code) }}"
                                   class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                          dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 
                                          @error('pattern_code') border-red-500 @enderror">
                            @error('pattern_code')
                            <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Finish -->
                        <div>
                            <label for="finish" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Finish
                            </label>
                            <input type="text" id="finish" name="finish" value="{{ old('finish', $product->finish) }}"
                                   class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                          dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 
                                          @error('finish') border-red-500 @enderror">
                            @error('finish')
                            <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Dimensions Section -->
                <div class="border-b border-slate-200 dark:border-slate-700 pb-6">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">Dimensi Fisik</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Length -->
                        <div>
                            <label for="length_cm" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Panjang (cm)
                            </label>
                            <input type="number" id="length_cm" name="length_cm" min="0" 
                                   value="{{ old('length_cm', $product->length_cm) }}"
                                   class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                          dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 
                                          @error('length_cm') border-red-500 @enderror">
                            @error('length_cm')
                            <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Width -->
                        <div>
                            <label for="width_mm" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Lebar (mm)
                            </label>
                            <input type="number" id="width_mm" name="width_mm" min="0" 
                                   value="{{ old('width_mm', $product->width_mm) }}"
                                   class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                          dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 
                                          @error('width_mm') border-red-500 @enderror">
                            @error('width_mm')
                            <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Thickness -->
                        <div>
                            <label for="thickness_mm" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Ketebalan (mm)
                            </label>
                            <input type="number" id="thickness_mm" name="thickness_mm" min="0" step="0.1" 
                                   value="{{ old('thickness_mm', $product->thickness_mm) }}"
                                   class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                          dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 
                                          @error('thickness_mm') border-red-500 @enderror">
                            @error('thickness_mm')
                            <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Notes Section -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Catatan
                    </label>
                    <textarea id="notes" name="notes" rows="4" 
                              class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                     dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 
                                     @error('notes') border-red-500 @enderror"
                              placeholder="Catatan tambahan tentang produk...">{{ old('notes', preg_replace('/hpp\s*:\s*[0-9\.]+\s*/i', '', $product->notes)) }}</textarea>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">HPP akan ditambahkan otomatis ke catatan jika diisi</p>
                    @error('notes')
                    <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between pt-6 border-t border-slate-200 dark:border-slate-700">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('products.show', $product->id) }}" 
                           class="text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100">
                            Lihat Detail Produk
                        </a>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <a href="{{ route('products.show', $product->id) }}" 
                           class="px-6 py-3 rounded-xl border border-slate-300 hover:bg-slate-50 
                                  dark:border-slate-600 dark:hover:bg-slate-700">
                            Batal
                        </a>
                        <button type="submit" 
                                class="px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-medium
                                       focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 
                                       dark:focus:ring-offset-slate-800">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
