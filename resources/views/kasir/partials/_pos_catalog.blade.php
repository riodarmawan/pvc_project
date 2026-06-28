@php
  /** @var \Illuminate\Pagination\LengthAwarePaginator $products */
@endphp

@if ($products->isEmpty())
  <div class="flex flex-col items-center justify-center py-20 text-center">
    <div class="h-16 w-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
      <svg class="h-8 w-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
    </div>
    <p class="text-sm font-medium text-slate-500">Produk tidak ditemukan</p>
    <p class="text-xs text-slate-400 mt-1">Coba kata kunci lain atau ubah filter</p>
  </div>
@else
  <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-3 xl:grid-cols-4 gap-3">
    @foreach ($products as $p)
      @include('kasir.partials._pos_product_card', ['p' => $p])
    @endforeach
  </div>

  @if($products->hasPages())
    <div class="mt-4 flex justify-center">
      {{ $products->withQueryString()->links('pagination::tailwind') }}
    </div>
  @endif
@endif
