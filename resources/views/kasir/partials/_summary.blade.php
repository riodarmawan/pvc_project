@php
  $cart     = $cart ?? [];
  $total    = (float)($total ?? 0);
  $discount = (float)($discount ?? 0);
  $netTotal = (float)($netTotal ?? max(0, $total - $discount));
  $paid     = (float)($paid ?? 0);
  $due      = (float)($due ?? max(0, $netTotal - $paid));
  $change   = $paid > $netTotal ? $paid - $netTotal : 0;
  $canFinalize = $due <= 0.0001 && count($cart) > 0;
@endphp

<div class="p-4 space-y-2">
  <div class="flex justify-between text-sm">
    <span class="text-slate-500">Item</span>
    <span class="font-medium text-slate-900">{{ count($cart) }} produk</span>
  </div>
  <div class="flex justify-between text-sm">
    <span class="text-slate-500">Subtotal</span>
    <span class="font-semibold text-slate-900 tabular-nums">Rp {{ number_format($total, 0, ',', '.') }}</span>
  </div>

  <div>
    <label for="discount-input" class="block text-xs text-slate-500 mb-1">Diskon Nota (opsional)</label>
    <div class="flex gap-1.5">
      <div class="relative flex-1">
        <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400 text-xs">Rp</span>
        <input type="number" id="discount-input" min="0" max="{{ $total }}" value="{{ $discount > 0 ? $discount : '' }}"
               placeholder="0"
               class="w-full h-9 pl-8 pr-2 rounded-lg border border-slate-200 text-sm tabular-nums focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
      </div>
      <button type="button" onclick="checkoutSetDiscount()" class="h-9 px-3 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50 transition">Terapkan</button>
    </div>
  </div>

  @if($discount > 0)
    <div class="flex justify-between text-sm">
      <span class="text-slate-500">Diskon</span>
      <span class="font-medium text-amber-600 tabular-nums">- Rp {{ number_format($discount, 0, ',', '.') }}</span>
    </div>
    <div class="flex justify-between text-sm">
      <span class="text-slate-500">Total setelah diskon</span>
      <span class="font-semibold text-slate-900 tabular-nums">Rp {{ number_format($netTotal, 0, ',', '.') }}</span>
    </div>
  @endif

  <div class="flex justify-between text-sm">
    <span class="text-slate-500">Terbayar</span>
    <span class="font-medium text-emerald-600 tabular-nums">Rp {{ number_format($paid, 0, ',', '.') }}</span>
  </div>
  @if($change > 0)
    <div class="flex justify-between text-sm">
      <span class="text-slate-500">Kembalian</span>
      <span class="font-medium text-blue-600 tabular-nums">Rp {{ number_format($change, 0, ',', '.') }}</span>
    </div>
  @endif

  <div class="border-t border-slate-200 pt-2">
    <div class="flex justify-between items-center">
      <span class="text-sm font-medium {{ $due > 0 ? 'text-red-600' : 'text-emerald-600' }}">
        {{ $due > 0 ? 'Sisa Bayar' : 'Lunas' }}
      </span>
      <span class="text-lg sm:text-xl font-bold {{ $due > 0 ? 'text-red-600' : 'text-emerald-600' }} tabular-nums">
        Rp {{ number_format($due, 0, ',', '.') }}
      </span>
    </div>
  </div>

  {{-- Finalize Button --}}
  <button onclick="checkoutFinalize()" id="btn-finalize"
          {{ !$canFinalize ? 'disabled' : '' }}
          class="w-full h-11 sm:h-12 rounded-lg font-semibold text-sm transition flex items-center justify-center gap-2
                 {{ $canFinalize
                    ? 'bg-emerald-600 text-white hover:bg-emerald-700 active:bg-emerald-800'
                    : 'bg-slate-100 text-slate-400 cursor-not-allowed' }}">
    @if($canFinalize)
      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      Finalisasi Transaksi
    @else
      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
      @if(count($cart) == 0) Keranjang Kosong @else Pembayaran Belum Lunas @endif
    @endif
  </button>
</div>
