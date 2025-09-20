@extends('layouts.app', ['title' => 'Katalog Produk'])

@section('content')
<div class="space-y-6">

  {{-- Flash / Errors --}}
  @if (session('ok'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 shadow-card">
      {{ session('ok') }}
    </div>
  @endif
  @if (session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 shadow-card">
      {{ session('success') }}
    </div>
  @endif
  @if ($errors->any())
    <div class="rounded-xl border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3 shadow-card">
      {{ $errors->first() }}
    </div>
  @endif

  <div class="flex items-center justify-between">
    <h1 class="text-xl md:text-2xl font-semibold">Katalog Produk</h1>
    <div class="flex items-center gap-2">
      <button type="button"
              class="btn-go-checkout inline-flex items-center h-10 px-4 rounded-xl text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition">
        Ke Keranjang / Checkout
      </button>
    </div>
  </div>

  {{-- Filter/Search --}}
  <form id="form-filter" method="get" action="{{ route('kasir.home') }}"
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
      <div class="md:col-span-2">
        <input type="text" name="q" id="q" value="{{ $q ?? '' }}"
               class="w-full h-11 px-4 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-200"
               placeholder="Cari SKU / nama produk">
      </div>
      <div class="flex gap-2">
        <select name="cat_id" id="cat_id"
                class="flex-1 h-11 px-3 pr-9 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-200">
          <option value="">Semua Kategori</option>
          @foreach ($categories as $c)
            <option value="{{ $c->id }}" @selected(($catId ?? null) == $c->id)>{{ $c->name }}</option>
          @endforeach
        </select>
        <button class="h-11 px-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 shadow-sm transition">
          Filter
        </button>
      </div>
    </div>
  </form>

  {{-- Daftar Produk (grid) --}}
  @include('kasir.partials._catalog', ['products' => $products])


</div>

{{-- Debounced auto-submit untuk filter --}}
<script>
(function(){
  const form = document.getElementById('form-filter');
  const q = document.getElementById('q');
  const cat = document.getElementById('cat_id');
  if (!form) return;

  // Submit hanya ketika menekan Enter
  q?.addEventListener('keypress', (event) => {
    if (event.key === 'Enter') {
      event.preventDefault(); // Mencegah submit default
      form.submit(); // Submit manual
    }
  });

  // Tetap submit ketika kategori berubah
  cat?.addEventListener('change', () => form.submit());
})();
</script>


<script src="{{ asset('js/pos.js') }}" defer></script>
@endsection
