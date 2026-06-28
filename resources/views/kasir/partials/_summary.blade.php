@php
  $cart   = $cart ?? [];
  $total  = (float)($total ?? 0);
  $paid   = (float)($paid ?? 0);
  $due    = (float)($due ?? max(0, $total - $paid));
  $change = $paid > $total ? $paid - $total : 0;
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
