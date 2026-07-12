@extends('layouts.app', ['title' => 'Checkout'])

@push('head')
<style>
  .checkout-scroll::-webkit-scrollbar { width: 4px; }
  .checkout-scroll::-webkit-scrollbar-track { background: transparent; }
  .checkout-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 9999px; }
  .qty-input::-webkit-inner-spin-button,
  .qty-input::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
  .qty-input { -moz-appearance: textfield; }
  .toast-enter { animation: toastIn .25s ease-out; }
  @keyframes toastIn { from { opacity: 0; transform: translateY(-0.5rem); } to { opacity: 1; transform: translateY(0); } }
  /* Mobile: force right column to full width and no flex stretch */
  @media (max-width: 1023px) {
    .checkout-right { width: 100% !important; flex-shrink: unset !important; }
    .checkout-left { min-height: auto !important; }
  }
</style>
@endpush

@section('content')
<div class="flex flex-col lg:flex-row gap-4">

  {{-- ========== LEFT: CART + CUSTOMER ========== --}}
  <div class="checkout-left flex-1 flex flex-col min-w-0 gap-4">

    {{-- Cart --}}
    <div id="cart-panel" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
      {{-- Cart Header --}}
      <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between bg-slate-50">
        <div class="flex items-center gap-2">
          <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
          <span class="text-sm font-semibold text-slate-900">Keranjang</span>
          <span id="cart-count" class="inline-flex items-center justify-center h-5 min-w-[20px] px-1.5 rounded-full bg-slate-200 text-slate-700 text-xs font-bold">{{ count($cart) }}</span>
        </div>
        <div class="flex items-center gap-2">
          <a href="{{ route('kasir.pos') }}" class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-slate-200 text-xs font-medium text-slate-700 hover:bg-slate-50 transition">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span class="hidden sm:inline">Tambah Produk</span>
            <span class="sm:hidden">Tambah</span>
          </a>
          @if(count($cart) > 0)
            <button onclick="checkoutClearCart()" class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-red-200 text-xs font-medium text-red-600 hover:bg-red-50 transition">
              <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              Kosongkan
            </button>
          @endif
        </div>
      </div>

      {{-- Cart Content --}}
      <div id="cart-items" class="overflow-y-auto checkout-scroll">
        @include('kasir.partials._cart', ['cart' => $cart])
      </div>
    </div>

    {{-- Customer Panel --}}
    <div id="customer-panel" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
      @include('kasir.partials._customer', [
        'customerId'       => $customerId,
        'selectedCustomer' => $selectedCustomer,
        'customerResults'  => $customerResults ?? [],
      ])
    </div>
  </div>

  {{-- ========== RIGHT: PAYMENT + SUMMARY ========== --}}
  <div class="checkout-right w-full lg:w-[380px] flex-shrink-0 flex flex-col gap-4">

    {{-- Payment Panel --}}
    <div id="payments-panel" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
      @include('kasir.partials._payments', ['payments' => $payments])
    </div>

    {{-- Summary Panel --}}
    <div id="summary-panel" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
      @include('kasir.partials._summary', [
        'cart'     => $cart,
        'total'    => $total,
        'discount' => $discount,
        'netTotal' => $netTotal,
        'paid'     => $paid,
        'due'      => $due,
      ])
    </div>
  </div>
</div>

{{-- Toast Container --}}
<div id="toast-container" class="fixed right-4 top-20 z-[60] space-y-2"></div>

{{-- Modals --}}
@include('kasir.modals._modal_customer')
@include('kasir.modals._modal_invoice')

<script>
  const CSRF = '{{ csrf_token() }}';
  const ROUTES = {
    cartUpdate:    '{{ route("kasir.cart.update") }}',
    cartRemove:    '{{ route("kasir.cart.remove") }}',
    cartClear:     '{{ route("kasir.cart.clear") }}',
    customerSelect:'{{ route("kasir.customer.select") }}',
    customerQuick: '{{ route("kasir.customer.quick") }}',
    customerSearch:'{{ url("/kasir/checkout") }}',
    paymentAdd:    '{{ route("kasir.pay.add") }}',
    paymentClear:  '{{ route("kasir.pay.clear") }}',
    discountSet:   '{{ route("kasir.discount.set") }}',
    finalize:      '{{ route("kasir.finalize") }}',
  };
</script>
<script src="{{ asset('js/checkout.js') }}"></script>
@endsection
