@extends('layouts.app', ['title' => 'Daftarkan Produk Baru'])

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6 space-y-5">
    <div class="flex items-center gap-3 mb-2">
        <a href="{{ route('kasir.home') }}"
           class="h-9 w-9 rounded-lg bg-white border border-slate-200 flex items-center justify-center hover:bg-slate-50 transition">
            <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-lg font-bold text-slate-900">Produk Baru</h1>
            <p class="text-xs text-slate-500">Daftarkan produk baru ke sistem</p>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-xl border px-4 py-3 text-sm bg-emerald-50 border-emerald-200 text-emerald-700">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if (session('error') || $errors->any())
        <div class="rounded-xl border px-4 py-3 text-sm bg-rose-50 border-rose-200 text-rose-700">
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

    <form action="{{ route('kasir.products.store') }}" method="POST" id="formProduct">
        @csrf

        <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-4">
            <h2 class="text-sm font-semibold text-slate-900">Informasi Dasar</h2>

            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">SKU *</label>
                    <input type="text" name="sku" id="sku" value="{{ old('sku') }}" required
                           class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Nama Produk *</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                           class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Satuan (UOM) *</label>
                    <select name="uom_id" id="uom_id" required
                            class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">Pilih Satuan</option>
                        @foreach($uoms as $uom)
                            <option value="{{ $uom->id }}" @selected(old('uom_id') == $uom->id)>{{ $uom->name }} ({{ $uom->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Harga Beli (HPP)</label>
                        <input type="number" name="hpp" id="hpp" value="{{ old('hpp') }}" placeholder="0" step="1" min="0"
                               class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Harga Jual</label>
                        <input type="number" name="selling_price" id="selling_price" value="{{ old('selling_price') }}" placeholder="0" step="1" min="0"
                               class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-4 mt-4">
            <h2 class="text-sm font-semibold text-slate-900">Kategori</h2>

            <div id="category-selection" class="space-y-3">
                <select name="category_id" id="category_id"
                        class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">Pilih Kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-500">
                    Tidak ada? <button type="button" id="show-new-category-btn"
                        class="text-emerald-600 hover:underline font-medium">Buat baru</button>
                </p>
            </div>

            <div id="new-category-form" class="space-y-3 hidden">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Nama Kategori</label>
                        <input type="text" name="new_category_name" id="new_category_name" value="{{ old('new_category_name') }}"
                               class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Kode Kategori</label>
                        <input type="text" name="new_category_code" id="new_category_code" value="{{ old('new_category_code') }}"
                               class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                </div>
                <button type="button" id="cancel-new-category-btn"
                        class="text-xs text-slate-500 hover:text-slate-700">Batal</button>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-4 mt-4">
            <h2 class="text-sm font-semibold text-slate-900">Atribut Tambahan <span class="text-slate-400 font-normal">(Opsional)</span></h2>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Material</label>
                    <input type="text" name="material" id="material" value="{{ old('material') }}"
                           class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Series</label>
                    <input type="text" name="series" id="series" value="{{ old('series') }}"
                           class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Panjang (cm)</label>
                    <input type="number" name="length_cm" id="length_cm" value="{{ old('length_cm') }}"
                           class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Lebar (mm)</label>
                    <input type="number" name="width_mm" id="width_mm" value="{{ old('width_mm') }}"
                           class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Tebal (mm)</label>
                    <input type="number" step="0.01" name="thickness_mm" id="thickness_mm" value="{{ old('thickness_mm') }}"
                           class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Barcode</label>
                    <input type="text" name="barcode" id="barcode" value="{{ old('barcode') }}"
                           class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Catatan</label>
                    <textarea name="notes" id="notes" rows="2"
                              class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex gap-3 mt-5">
            <a href="{{ route('kasir.home') }}"
               class="flex-1 h-11 rounded-xl border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition flex items-center justify-center">
                Batal
            </a>
            <button type="submit" id="btnSubmitProduct"
                    class="flex-1 h-11 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition flex items-center justify-center gap-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Simpan Produk
            </button>
        </div>
    </form>
</div>

<script>
(function(){
  const selWrap = document.getElementById('category-selection');
  const newWrap = document.getElementById('new-category-form');
  const btnShow = document.getElementById('show-new-category-btn');
  const btnCancel = document.getElementById('cancel-new-category-btn');

  const hasOldNewCat = !!( "{{ old('new_category_name') }}" || "{{ old('new_category_code') }}" );
  function showNewCatForm(){ selWrap?.classList.add('hidden'); newWrap?.classList.remove('hidden'); }
  function showSelectForm(){
    newWrap?.classList.add('hidden'); selWrap?.classList.remove('hidden');
    const name = document.getElementById('new_category_name');
    const code = document.getElementById('new_category_code');
    if (name) name.value = '';
    if (code) code.value = '';
  }
  if (hasOldNewCat) showNewCatForm(); else showSelectForm();
  btnShow?.addEventListener('click', showNewCatForm);
  btnCancel?.addEventListener('click', showSelectForm);

  const form = document.getElementById('formProduct');
  const btn  = document.getElementById('btnSubmitProduct');
  form?.addEventListener('submit', () => {
    ['sku','name','new_category_name','new_category_code','material','series','barcode','notes'].forEach(id => {
      const el = document.getElementById(id);
      if (el && typeof el.value === 'string') el.value = el.value.trim();
    });
    ['hpp','length_cm','width_mm','thickness_mm'].forEach(id => {
      const el = document.getElementById(id);
      if (el && typeof el.value === 'string') el.value = el.value.replace(',', '.');
    });
    if (btn) { btn.disabled = true; btn.classList.add('opacity-70','cursor-not-allowed'); }
  });
})();
</script>
@endsection
