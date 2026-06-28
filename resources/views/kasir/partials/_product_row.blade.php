@php
  $isOutOfStock = (int)($p->stock ?? 0) <= 0;
  $stock = (int)($p->stock ?? 0);
@endphp

<div class="product-card group relative bg-white rounded-xl border border-slate-200 p-3 hover:border-emerald-300 hover:shadow-md transition-all duration-200 {{ $isOutOfStock ? 'opacity-60' : '' }}">
  {{-- Top: SKU Badge + Stock --}}
  <div class="flex items-center justify-between mb-2">
    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-500 text-[10px] font-medium uppercase tracking-wide">
      {{ $p->sku }}
    </span>
    @if($isOutOfStock)
      <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-red-50 text-red-600 text-[10px] font-semibold">Habis</span>
    @else
      <span class="text-[10px] text-slate-400">Stok: {{ $stock }}</span>
    @endif
  </div>

  {{-- Product Name --}}
  <h3 class="text-sm font-semibold text-slate-900 leading-snug mb-1 line-clamp-2 min-h-[2.5rem]">{{ $p->name }}</h3>

  {{-- Price --}}
  <p class="text-base font-bold text-emerald-700 tabular-nums mb-3">Rp {{ number_format($p->price ?? 0, 0, ',', '.') }}</p>

  {{-- Add to Cart --}}
  @if($isOutOfStock)
    <button disabled class="w-full h-9 rounded-lg bg-slate-100 text-slate-400 text-xs font-medium cursor-not-allowed flex items-center justify-center gap-1.5">
      <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
      Stok Habis
    </button>
  @else
    <div class="flex items-center gap-1.5">
      <input type="number" min="1" value="1"
             class="qty-input w-14 h-9 text-center text-xs font-semibold text-slate-900 border border-slate-200 rounded-lg focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
      <button onclick="catalogAddToCart({{ $p->id }}, this)"
              class="flex-1 h-9 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700 active:bg-emerald-800 transition flex items-center justify-center gap-1">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Tambah
      </button>
    </div>
  @endif
</div>
