{{-- Product Card for POS Grid --}}
@php
  $stock = (int) floor((float) ($p->stock ?? 0));
  $outOfStock = $stock <= 0;
@endphp
<div class="group relative bg-white rounded-xl border border-slate-200 p-3 hover:border-emerald-300 hover:shadow-md transition-all duration-200 {{ $outOfStock ? 'opacity-50' : '' }}">
  {{-- Category Badge --}}
  <div class="flex items-center justify-between mb-2">
    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[10px] font-medium uppercase tracking-wide">
      {{ $p->sku }}
    </span>
    @if($outOfStock)
      <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-red-50 text-red-600 text-[10px] font-semibold">Habis</span>
    @else
      <span class="text-[10px] text-slate-400">Stok: {{ $stock }}</span>
    @endif
  </div>

  {{-- Product Name --}}
  <h3 class="text-sm font-semibold text-slate-900 leading-snug mb-1 line-clamp-2 min-h-[2.5rem]">{{ $p->name }}</h3>

  {{-- Price --}}
  <p class="text-base font-bold text-emerald-700 tabular-nums mb-3">Rp {{ number_format($p->price, 0, ',', '.') }}</p>

  {{-- Add Button --}}
  @if($outOfStock)
    <button disabled class="w-full h-9 rounded-lg bg-slate-100 text-slate-400 text-sm font-medium cursor-not-allowed flex items-center justify-center gap-1.5">
      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
      Stok Habis
    </button>
  @else
    <button onclick="addToCart({{ $p->id }}, '{{ addslashes($p->name) }}', '{{ $p->sku }}', {{ $p->price }}, {{ $stock }})"
            class="w-full h-9 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 active:scale-[0.97] transition flex items-center justify-center gap-1.5">
      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      Tambah
    </button>
  @endif
</div>
