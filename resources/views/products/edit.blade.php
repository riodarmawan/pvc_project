@extends('layouts.dashboard', ['title' => 'Edit Produk'])

@section('content')
<div class="space-y-6">
    <div class="space-y-2">
        <h1 class="text-xl md:text-2xl font-semibold">Edit Produk</h1>
        <p class="text-slate-600">Perbarui data produk "{{ $product->name }}".</p>
    </div>

    {{-- Notifikasi Sukses dan Error --}}
    @if (session('success'))
        <div role="alert"
             class="rounded-xl border px-4 py-3 text-sm bg-emerald-50 border-emerald-200 text-emerald-700">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if (session('error') || $errors->any())
        <div role="alert"
             class="rounded-xl border px-4 py-3 text-sm bg-rose-50 border-rose-200 text-rose-700">
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

    <form action="{{ route('products.update', $product->id) }}" method="POST" id="formProduct">
        @csrf
        @method('PUT')

        @include('products._form')

        <!-- Tombol Aksi -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('products.create') }}"
               class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border hover:bg-slate-100 border-slate-200">
                Batal
            </a>
            <button type="submit" id="btnSubmitProduct"
                    class="inline-flex items-center gap-2 h-11 px-5 rounded-xl border text-white bg-brand hover:bg-brand/90 border-transparent">
                Simpan Perubahan
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

  const hasOldNewCat = !!("{{ old('new_category_name') }}" || "{{ old('new_category_code') }}");
  function showNewCatForm(){
    selWrap?.classList.add('hidden');
    newWrap?.classList.remove('hidden');
  }
  function showSelectForm(){
    newWrap?.classList.add('hidden');
    selWrap?.classList.remove('hidden');
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
    if (btn) {
      btn.disabled = true;
      btn.classList.add('opacity-70','cursor-not-allowed');
    }
  });
})();
</script>
@endsection
