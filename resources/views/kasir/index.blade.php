@extends('layouts.app', ['title' => 'Katalog Produk'])

@push('head')
<style>
  .catalog-scroll::-webkit-scrollbar { height: 4px; }
  .catalog-scroll::-webkit-scrollbar-track { background: transparent; }
  .catalog-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 9999px; }
  .toast-enter { animation: toastIn .25s ease-out; }
  @keyframes toastIn { from { opacity: 0; transform: translateY(-0.5rem); } to { opacity: 1; transform: translateY(0); } }
</style>
@endpush

@section('content')
<div class="space-y-4">

  {{-- Top Bar: Search + Cart --}}
  <div class="flex flex-col sm:flex-row gap-3">
    {{-- Search --}}
    <div class="relative flex-1">
      <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      </div>
      <input type="text" id="q" value="{{ $q ?? '' }}"
             class="w-full h-11 pl-10 pr-4 rounded-xl border border-slate-200 bg-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
             placeholder="Cari SKU atau nama produk..." autocomplete="off">
    </div>

    {{-- Cart Button --}}
    <a href="{{ route('kasir.checkout') }}"
       class="inline-flex items-center justify-center gap-2 h-11 px-5 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 active:bg-emerald-800 transition shadow-sm flex-shrink-0">
      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
      Checkout
    </a>
  </div>

  {{-- Category Chips --}}
  <div class="flex gap-2 overflow-x-auto catalog-scroll pb-1 -mb-1">
    <a href="{{ route('kasir.home') }}"
       class="inline-flex items-center h-9 px-4 rounded-full text-xs font-semibold transition flex-shrink-0
              {{ empty($catId) ? 'bg-emerald-600 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
      Semua
    </a>
    @foreach ($categories as $cat)
      <a href="{{ route('kasir.home', ['cat_id' => $cat->id] + (request('q') ? ['q' => request('q')] : [])) }}"
         class="inline-flex items-center h-9 px-4 rounded-full text-xs font-semibold transition flex-shrink-0
                {{ ($catId ?? null) == $cat->id ? 'bg-emerald-600 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
        {{ $cat->name }}
      </a>
    @endforeach
  </div>

  {{-- Product Grid --}}
  <div id="catalog-grid">
    @include('kasir.partials._catalog', ['products' => $products])
  </div>

</div>

{{-- Toast Container --}}
<div id="toast-container" class="fixed right-4 top-20 z-[60] space-y-2"></div>

<script>
  const CSRF = '{{ csrf_token() }}';
  const ROUTES = {
    cartAdd: '{{ route("kasir.cart.add") }}',
    checkout: '{{ route("kasir.checkout") }}',
  };

  function toast(msg, type = 'success') {
    const c = document.getElementById('toast-container');
    if (!c) return;
    const colors = {
      success: 'bg-emerald-50 border-emerald-200 text-emerald-800',
      error: 'bg-red-50 border-red-200 text-red-800',
    };
    const el = document.createElement('div');
    el.className = `toast-enter rounded-lg border px-3 py-2 shadow-lg text-sm font-medium ${colors[type] || colors.info}`;
    el.textContent = msg;
    c.appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .3s'; setTimeout(() => el.remove(), 300); }, 2500);
  }

  // AJAX Search
  (function(){
    const q = document.getElementById('q');
    const catalogContainer = document.getElementById('catalog-grid');
    if (!q || !catalogContainer) return;
    let t;
    const catId = '{{ $catId ?? "" }}';

    q.addEventListener('input', () => {
      clearTimeout(t);
      t = setTimeout(async () => {
        const params = new URLSearchParams({ q: q.value, ajax_catalog: 1 });
        if (catId) params.set('cat_id', catId);
        try {
          const res = await fetch('{{ route("kasir.home") }}?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
          });
          const data = await res.json();
          if (data.html) {
            catalogContainer.innerHTML = data.html;
          }
        } catch(e) {
          console.error('Search error:', e);
        }
      }, 350);
    });
  })();

  // AJAX Add to Cart
  async function catalogAddToCart(productId, btn) {
    if (btn.disabled) return;
    btn.disabled = true;
    btn.classList.add('opacity-60');

    const qtyInput = btn.closest('.product-card')?.querySelector('input[type="number"]');
    const qty = qtyInput ? parseInt(qtyInput.value) || 1 : 1;

    try {
      const res = await fetch(ROUTES.cartAdd, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': CSRF,
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ product_id: productId, qty: qty }),
      });
      const data = await res.json();
      if (data.ok) {
        toast(data.message || 'Ditambahkan ke keranjang');
      } else {
        toast(data.message || 'Gagal menambahkan', 'error');
      }
    } catch {
      toast('Gagal menghubungi server', 'error');
    } finally {
      btn.disabled = false;
      btn.classList.remove('opacity-60');
    }
  }
</script>
@endsection
