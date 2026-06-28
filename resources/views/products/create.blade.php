@extends('layouts.dashboard', ['title' => 'Daftarkan Produk Baru']) 

@section('content')
<div class="space-y-6">
    <div class="space-y-2">
        <h1 class="text-xl md:text-2xl font-semibold">Formulir Produk Baru</h1>
        <p class="text-slate-600 dark:text-slate-400">Isi detail produk dengan lengkap untuk mendaftarkannya ke dalam sistem.</p>
    </div>

    {{-- Notifikasi Sukses dan Error --}}
    @if (session('success'))
        <div role="alert"
             class="rounded-xl border px-4 py-3 text-sm bg-emerald-50 border-emerald-200 text-emerald-700
                    dark:bg-emerald-500/15 dark:border-emerald-500/30 dark:text-emerald-200">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if (session('error') || $errors->any())
        <div role="alert"
             class="rounded-xl border px-4 py-3 text-sm bg-rose-50 border-rose-200 text-rose-700
                    dark:bg-rose-500/15 dark:border-rose-500/30 dark:text-rose-200">
            <p class="font-semibold">Terjadi Kesalahan</p>
            @if(session('error'))
                <p class="mt-1">{{ session('error') }}</p>
            @else
                <ul class="list-disc pl-5 mt-1 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li class="leading-6">{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    <form action="{{ route('products.store') }}" method="POST" id="formProduct">
        @csrf

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
                        <input type="text" name="sku" id="sku" value="{{ old('sku') }}" required
                               class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-brand/40
                                      dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                    </div>

                    <div class="space-y-2">
                        <label for="name" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                            Nama Produk
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
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
                                <option value="{{ $uom->id }}" @selected(old('uom_id') == $uom->id)>{{ $uom->name }} ({{$uom->code}})</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- ===== INPUT HPP & HARGA JUAL ===== --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label for="hpp" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                                Harga Beli (HPP)
                            </label>
                            <input type="number" name="hpp" id="hpp" value="{{ old('hpp') }}" placeholder="Contoh: 55000" step="1"
                                   class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-brand/40
                                          dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                        </div>
                        <div class="space-y-2">
                            <label for="selling_price" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                                Harga Jual
                            </label>
                            <input type="number" name="selling_price" id="selling_price" value="{{ old('selling_price') }}" placeholder="Contoh: 75000" step="1"
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
                                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
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
                        <input type="text" name="material" id="material" value="{{ old('material') }}"
                               class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-brand/40
                                      dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                    </div>

                    <div class="space-y-2">
                        <label for="series" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Series</label>
                        <input type="text" name="series" id="series" value="{{ old('series') }}"
                               class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-brand/40
                                      dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                    </div>

                    <div class="space-y-2">
                        <label for="length_cm" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Panjang (cm)</label>
                        <input type="number" name="length_cm" id="length_cm" value="{{ old('length_cm') }}"
                               class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-brand/40
                                      dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                    </div>

                    <div class="space-y-2">
                        <label for="width_mm" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Lebar (mm)</label>
                        <input type="number" name="width_mm" id="width_mm" value="{{ old('width_mm') }}"
                               class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-brand/40
                                      dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                    </div>

                    <div class="space-y-2">
                        <label for="thickness_mm" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Tebal (mm)</label>
                        <input type="number" step="0.01" name="thickness_mm" id="thickness_mm" value="{{ old('thickness_mm') }}"
                               class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-brand/40
                                      dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                    </div>

                    <div class="space-y-2">
                        <label for="barcode" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Barcode</label>
                        <input type="text" name="barcode" id="barcode" value="{{ old('barcode') }}"
                               class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-brand/40
                                      dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                    </div>

                    <div class="md:col-span-2 space-y-2">
                        <label for="notes" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Catatan Tambahan</label>
                        <textarea id="notes" name="notes" rows="3"
                                  class="w-full bg-white border border-slate-200 rounded-xl p-3 text-sm
                                         focus:outline-none focus:ring-2 focus:ring-brand/40
                                         dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tombol Aksi -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="#"
               class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border hover:bg-slate-100 border-slate-200
                      dark:hover:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
                Batal
            </a>
            <button type="submit" id="btnSubmitProduct"
                    class="inline-flex items-center gap-2 h-11 px-5 rounded-xl border text-white bg-brand hover:bg-brand/90 border-transparent
                           dark:bg-brandDark dark:hover:bg-brandDark/90">
                Simpan Produk
            </button>
        </div>
    </form>
</div>

{{-- JS: toggle kategori baru, normalisasi angka, trim, cegah submit ganda --}}
<script>
(function(){
  const selWrap = document.getElementById('category-selection');
  const newWrap = document.getElementById('new-category-form');
  const btnShow = document.getElementById('show-new-category-btn');
  const btnCancel = document.getElementById('cancel-new-category-btn');

  // tampilkan form kategori baru jika ada old value
  const hasOldNewCat = !!("{{ old('new_category_name') }}" || "{{ old('new_category_code') }}");
  function showNewCatForm(){
    selWrap?.classList.add('hidden');
    newWrap?.classList.remove('hidden');
  }
  function showSelectForm(){
    newWrap?.classList.add('hidden');
    selWrap?.classList.remove('hidden');
    // kosongkan field kategori baru saat membatalkan
    const name = document.getElementById('new_category_name');
    const code = document.getElementById('new_category_code');
    if (name) name.value = '';
    if (code) code.value = '';
  }
  if (hasOldNewCat) showNewCatForm(); else showSelectForm();
  btnShow?.addEventListener('click', showNewCatForm);
  btnCancel?.addEventListener('click', showSelectForm);

  // normalisasi angka & trim + cegah submit ganda
  const form = document.getElementById('formProduct');
  const btn  = document.getElementById('btnSubmitProduct');
  form?.addEventListener('submit', () => {
    // trim text fields
    ['sku','name','new_category_name','new_category_code','material','series','barcode','notes'].forEach(id => {
      const el = document.getElementById(id);
      if (el && typeof el.value === 'string') el.value = el.value.trim();
    });
    // normalisasi angka: koma -> titik
    ['hpp','length_cm','width_mm','thickness_mm'].forEach(id => {
      const el = document.getElementById(id);
      if (el && typeof el.value === 'string') el.value = el.value.replace(',', '.');
    });
    // cegah submit ganda
    if (btn) {
      btn.disabled = true;
      btn.classList.add('opacity-70','cursor-not-allowed');
    }
  });
})();
</script>
@endsection
