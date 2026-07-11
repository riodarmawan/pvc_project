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

        @include('products._form')

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
