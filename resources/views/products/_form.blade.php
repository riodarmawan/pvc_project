{{-- Form field produk — dipakai bersama oleh products.create & products.edit.
     $product tidak didefinisikan saat create (null-safe lewat ?? di setiap value). --}}

<!-- Bagian Informasi Dasar -->
<div class="rounded-2xl border bg-white shadow-card border-slate-200
            dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
    <div class="p-6 md:p-7 space-y-4">
        <div>
            <h2 class="text-base font-semibold">Informasi Dasar & Harga</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">Informasi utama dan harga beli pertama (HPP) untuk produk ini.</p>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <div class="space-y-2">
                <label for="sku" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                    SKU (Kode Unik Produk)
                </label>
                <input type="text" name="sku" id="sku" value="{{ old('sku', $product->sku ?? '') }}" required
                       class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                              focus:outline-none focus:ring-2 focus:ring-brand/40
                              dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            </div>

            <div class="space-y-2">
                <label for="name" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                    Nama Produk
                </label>
                <input type="text" name="name" id="name" value="{{ old('name', $product->name ?? '') }}" required
                       class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                              focus:outline-none focus:ring-2 focus:ring-brand/40
                              dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            </div>

            <div class="space-y-2">
                <label for="uom_id" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                    Satuan Unit (UOM)
                </label>
                <select id="uom_id" name="uom_id" required
                        class="w-full appearance-none bg-white border border-slate-200 rounded-xl h-11 px-3 pr-9 text-sm
                               focus:outline-none focus:ring-2 focus:ring-brand/40
                               dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                    <option value="">Pilih Satuan</option>
                    @foreach($uoms as $uom)
                        <option value="{{ $uom->id }}" @selected(old('uom_id', $product->uom_id ?? null) == $uom->id)>{{ $uom->name }} ({{$uom->code}})</option>
                    @endforeach
                </select>
            </div>

            {{-- ===== INPUT HPP & HARGA JUAL ===== --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label for="hpp" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                        Harga Beli (HPP)
                    </label>
                    <input type="number" name="hpp" id="hpp" value="{{ old('hpp', $product->hpp ?? '') }}" placeholder="Contoh: 55000" step="1"
                           class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-brand/40
                                  dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                </div>
                <div class="space-y-2">
                    <label for="selling_price" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                        Harga Jual
                    </label>
                    <input type="number" name="selling_price" id="selling_price" value="{{ old('selling_price', $product->selling_price ?? '') }}" placeholder="Contoh: 75000" step="1"
                           class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-brand/40
                                  dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                </div>
            </div>
            {{-- ================================== --}}
        </div>
    </div>
</div>

<!-- Bagian Kategori -->
<div class="rounded-2xl border bg-white shadow-card border-slate-200
            dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
    <div class="p-6 md:p-7 space-y-4">
        <div>
            <h2 class="text-base font-semibold">Kategori</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">Pilih kategori yang sudah ada, atau buat yang baru.</p>
        </div>

        <div id="category-selection" class="space-y-3">
            <div class="space-y-2">
                <label for="category_id" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                    Kategori yang Tersedia
                </label>
                <select id="category_id" name="category_id"
                        class="w-full appearance-none bg-white border border-slate-200 rounded-xl h-11 px-3 pr-9 text-sm
                               focus:outline-none focus:ring-2 focus:ring-brand/40
                               dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                    <option value="">Pilih Kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id ?? null) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">
                Tidak menemukan kategori?
                <button type="button" id="show-new-category-btn"
                        class="inline-flex items-center h-8 px-3 rounded-lg border hover:bg-slate-100 border-slate-200
                               dark:hover:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
                    Buat Baru
                </button>
            </p>
        </div>

        <div id="new-category-form" class="space-y-3">
            <div class="grid md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label for="new_category_name" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                        Nama Kategori Baru
                    </label>
                    <input type="text" name="new_category_name" id="new_category_name" value="{{ old('new_category_name') }}"
                           class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-brand/40
                                  dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                </div>
                <div class="space-y-2">
                    <label for="new_category_code" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                        Kode Kategori Baru
                    </label>
                    <input type="text" name="new_category_code" id="new_category_code" value="{{ old('new_category_code') }}"
                           class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-brand/40
                                  dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                </div>
            </div>
            <button type="button" id="cancel-new-category-btn"
                    class="inline-flex items-center h-10 px-4 rounded-xl border hover:bg-slate-100 border-slate-200
                           dark:hover:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
                Batal
            </button>
        </div>
    </div>
</div>

<!-- Bagian Atribut Tambahan -->
<div class="rounded-2xl border bg-white shadow-card border-slate-200
            dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
    <div class="p-6 md:p-7 space-y-4">
        <h2 class="text-base font-semibold">Atribut Tambahan (Opsional)</h2>

        <div class="grid md:grid-cols-2 gap-4">
            <div class="space-y-2">
                <label for="material" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Material</label>
                <input type="text" name="material" id="material" value="{{ old('material', $product->material ?? '') }}"
                       class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                              focus:outline-none focus:ring-2 focus:ring-brand/40
                              dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            </div>

            <div class="space-y-2">
                <label for="series" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Series</label>
                <input type="text" name="series" id="series" value="{{ old('series', $product->series ?? '') }}"
                       class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                              focus:outline-none focus:ring-2 focus:ring-brand/40
                              dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            </div>

            <div class="space-y-2">
                <label for="length_cm" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Panjang (cm)</label>
                <input type="number" name="length_cm" id="length_cm" value="{{ old('length_cm', $product->length_cm ?? '') }}"
                       class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                              focus:outline-none focus:ring-2 focus:ring-brand/40
                              dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            </div>

            <div class="space-y-2">
                <label for="width_mm" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Lebar (mm)</label>
                <input type="number" name="width_mm" id="width_mm" value="{{ old('width_mm', $product->width_mm ?? '') }}"
                       class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                              focus:outline-none focus:ring-2 focus:ring-brand/40
                              dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            </div>

            <div class="space-y-2">
                <label for="thickness_mm" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Tebal (mm)</label>
                <input type="number" step="0.01" name="thickness_mm" id="thickness_mm" value="{{ old('thickness_mm', $product->thickness_mm ?? '') }}"
                       class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                              focus:outline-none focus:ring-2 focus:ring-brand/40
                              dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            </div>

            <div class="space-y-2">
                <label for="barcode" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Barcode</label>
                <input type="text" name="barcode" id="barcode" value="{{ old('barcode', $product->barcode ?? '') }}"
                       class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                              focus:outline-none focus:ring-2 focus:ring-brand/40
                              dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            </div>

            <div class="md:col-span-2 space-y-2">
                <label for="notes" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Catatan Tambahan</label>
                <textarea id="notes" name="notes" rows="3"
                          class="w-full bg-white border border-slate-200 rounded-xl p-3 text-sm
                                 focus:outline-none focus:ring-2 focus:ring-brand/40
                                 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">{{ old('notes', $product->notes ?? '') }}</textarea>
            </div>
        </div>
    </div>
</div>
