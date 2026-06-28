@extends('layouts.app')
@section('title', 'POS — Point of Sale')

@push('head')
<style>
  /* Hide scrollbar for cart items */
  .cart-scroll::-webkit-scrollbar { width: 4px; }
  .cart-scroll::-webkit-scrollbar-track { background: transparent; }
  .cart-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 9999px; }
  /* Skeleton shimmer */
  .skeleton { background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
  @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
  /* Toast animation */
  .toast-enter { animation: toastIn .25s ease-out; }
  @keyframes toastIn { from { opacity: 0; transform: translateX(1rem); } to { opacity: 1; transform: translateX(0); } }
</style>
@endpush

@push('modals')
  {{-- Payment Modal --}}
  <div id="payment-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" id="payment-backdrop"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
      <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between rounded-t-2xl">
          <h3 class="text-lg font-semibold text-slate-900">Pembayaran</h3>
          <button onclick="closePaymentModal()" class="h-8 w-8 rounded-lg hover:bg-slate-100 flex items-center justify-center">
            <svg class="h-5 w-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <div id="payment-content" class="p-6"></div>
      </div>
    </div>
  </div>

  {{-- Invoice Modal --}}
  <div id="invoice-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" id="invoice-backdrop"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
      <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between rounded-t-2xl">
          <h3 class="text-lg font-semibold text-slate-900">Struk Pembayaran</h3>
          <button onclick="closeInvoiceModal()" class="h-8 w-8 rounded-lg hover:bg-slate-100 flex items-center justify-center">
            <svg class="h-5 w-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <div id="invoice-content" class="p-6"></div>
        <div class="border-t border-slate-200 px-6 py-4 flex gap-3">
          <button onclick="printInvoice()" class="flex-1 h-11 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition flex items-center justify-center gap-2">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Cetak
          </button>
          <button onclick="closeInvoiceModal()" class="flex-1 h-11 rounded-xl border border-slate-200 text-slate-700 font-medium hover:bg-slate-50 transition">Tutup</button>
        </div>
      </div>
    </div>
  </div>

  {{-- Customer Quick Add Modal --}}
  <div id="customer-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" id="customer-backdrop"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
      <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="border-b border-slate-200 px-6 py-4 flex items-center justify-between rounded-t-2xl">
          <h3 class="text-lg font-semibold text-slate-900">Tambah Pelanggan</h3>
          <button onclick="closeCustomerModal()" class="h-8 w-8 rounded-lg hover:bg-slate-100 flex items-center justify-center">
            <svg class="h-5 w-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <form id="quick-customer-form" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nama <span class="text-red-500">*</span></label>
            <input type="text" name="name" required class="w-full h-11 px-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm" placeholder="Nama pelanggan">
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Telepon</label>
            <input type="text" name="phone" class="w-full h-11 px-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm" placeholder="08xxxxxxxxxx">
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Alamat</label>
            <textarea name="address" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm resize-none" placeholder="Alamat (opsional)"></textarea>
          </div>
          <div class="flex gap-3 pt-2">
            <button type="button" onclick="closeCustomerModal()" class="flex-1 h-11 rounded-xl border border-slate-200 text-slate-700 font-medium hover:bg-slate-50 transition">Batal</button>
            <button type="submit" class="flex-1 h-11 rounded-xl bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endpush

@section('content')
<div class="flex flex-col lg:flex-row gap-4 h-[calc(100vh-8rem)]">

  {{-- ========== LEFT: CATALOG ========== --}}
  <div class="flex-1 flex flex-col min-w-0">
    {{-- Search & Filter Bar --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-3 flex flex-col sm:flex-row gap-3">
      {{-- Search --}}
      <div class="relative flex-1">
        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
          <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <input type="text" id="search-input" placeholder="Cari produk..."
               class="w-full h-11 pl-10 pr-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm"
               value="{{ $q }}" autocomplete="off">
      </div>
      {{-- Category Filter --}}
      <select id="category-filter" class="h-11 px-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm bg-white">
        <option value="">Semua Kategori</option>
        @foreach($categories as $cat)
          <option value="{{ $cat->id }}" {{ $catId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
        @endforeach
      </select>
    </div>

    {{-- Product Grid --}}
    <div class="flex-1 overflow-y-auto mt-3" id="product-scroll">
      <div id="product-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-3 xl:grid-cols-4 gap-3">
        @each('kasir.partials._pos_product_card', $products, 'p')
      </div>
      {{-- Empty State --}}
      @if($products->isEmpty())
        <div class="flex flex-col items-center justify-center py-20 text-center">
          <div class="h-16 w-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
          </div>
          <p class="text-slate-500 font-medium">Produk tidak ditemukan</p>
          <p class="text-sm text-slate-400 mt-1">Coba kata kunci lain atau ubah filter</p>
        </div>
      @endif
      {{-- Pagination --}}
      @if($products->hasPages())
        <div class="mt-4 flex justify-center">
          {{ $products->withQueryString()->links('pagination::tailwind') }}
        </div>
      @endif
    </div>
  </div>

  {{-- ========== RIGHT: CART SIDEBAR ========== --}}
  <div class="w-full lg:w-[380px] flex-shrink-0 flex flex-col bg-white rounded-2xl border border-slate-200 overflow-hidden">
    {{-- Cart Header --}}
    <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between bg-emerald-50">
      <div class="flex items-center gap-2">
        <div class="h-8 w-8 rounded-lg bg-emerald-600 flex items-center justify-center">
          <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
        </div>
        <span class="font-semibold text-slate-900">Keranjang</span>
        <span id="cart-count-badge" class="inline-flex items-center justify-center h-5 min-w-[20px] px-1.5 rounded-full bg-emerald-600 text-white text-xs font-bold">{{ count($cart) }}</span>
      </div>
      @if(count($cart) > 0)
        <button onclick="clearCart()" class="text-xs text-red-500 hover:text-red-700 font-medium flex items-center gap-1">
          <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
          Kosongkan
        </button>
      @endif
    </div>

    {{-- Cart Items --}}
    <div id="cart-items" class="flex-1 overflow-y-auto cart-scroll p-3 space-y-2">
      @if(empty($cart))
        <div class="flex flex-col items-center justify-center py-12 text-center" id="cart-empty">
          <div class="h-14 w-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-3">
            <svg class="h-7 w-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
          </div>
          <p class="text-sm text-slate-400">Keranjang kosong</p>
          <p class="text-xs text-slate-300 mt-1">Tambah produk dari katalog</p>
        </div>
      @else
        @foreach($cart as $item)
          <div class="flex items-center gap-3 p-2.5 rounded-xl bg-slate-50 border border-slate-100" data-product-id="{{ $item['product_id'] }}">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-slate-900 truncate">{{ $item['name'] }}</p>
              <p class="text-xs text-slate-500">{{ $item['sku'] }} · Rp {{ number_format($item['price'], 0, ',', '.') }}</p>
            </div>
            <div class="flex items-center gap-1.5">
              <button onclick="updateCartQty({{ $item['product_id'] }}, {{ $item['qty'] - 1 }})" class="h-7 w-7 rounded-lg border border-slate-300 flex items-center justify-center hover:bg-slate-100 transition text-slate-600">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
              </button>
              <span class="w-8 text-center text-sm font-semibold text-slate-900">{{ $item['qty'] }}</span>
              <button onclick="updateCartQty({{ $item['product_id'] }}, {{ $item['qty'] + 1 }})" class="h-7 w-7 rounded-lg border border-slate-300 flex items-center justify-center hover:bg-slate-100 transition text-slate-600">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              </button>
            </div>
            <div class="text-right w-24">
              <p class="text-sm font-semibold text-slate-900 tabular-nums">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</p>
              <button onclick="removeCartItem({{ $item['product_id'] }})" class="text-xs text-red-500 hover:text-red-700">Hapus</button>
            </div>
          </div>
        @endforeach
      @endif
    </div>

    {{-- Customer --}}
    <div class="px-4 py-3 border-t border-slate-200">
      <div id="customer-section">
        @if(!empty($selectedCustomer))
          <div class="flex items-center justify-between bg-emerald-50 rounded-xl px-3 py-2">
            <div class="flex items-center gap-2 min-w-0">
              <svg class="h-4 w-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              <span class="text-sm font-medium text-emerald-800 truncate">{{ $selectedCustomer->name }}</span>
            </div>
            <button onclick="clearCustomer()" class="text-emerald-600 hover:text-emerald-800">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
        @else
          <div class="flex gap-2">
            <div class="relative flex-1">
              <input type="text" id="customer-search" placeholder="Cari pelanggan (nama/telepon)..."
                     class="w-full h-9 px-3 pr-8 rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm" autocomplete="off">
              <div id="customer-results" class="absolute top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg z-10 hidden max-h-48 overflow-y-auto"></div>
            </div>
            <button onclick="openCustomerModal()" class="h-9 px-3 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition flex items-center gap-1 flex-shrink-0">
              <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              Baru
            </button>
          </div>
        @endif
      </div>
    </div>

    {{-- Summary & Pay --}}
    <div class="border-t border-slate-200 px-4 py-3 bg-slate-50">
      <div class="space-y-1.5 text-sm mb-3">
        <div class="flex justify-between text-slate-600">
          <span>Subtotal</span>
          <span id="summary-total" class="tabular-nums font-medium text-slate-900">Rp {{ number_format($total ?? 0, 0, ',', '.') }}</span>
        </div>
        <div class="flex justify-between text-slate-600">
          <span>Dibayar</span>
          <span id="summary-paid" class="tabular-nums font-medium text-emerald-600">Rp {{ number_format($paid ?? 0, 0, ',', '.') }}</span>
        </div>
        <div class="flex justify-between text-slate-600">
          <span>Sisa Bayar</span>
          <span id="summary-due" class="tabular-nums font-bold {{ ($due ?? 0) > 0 ? 'text-red-600' : 'text-emerald-600' }}">Rp {{ number_format($due ?? 0, 0, ',', '.') }}</span>
        </div>
      </div>
      <button onclick="openPaymentModal()" id="btn-pay"
              class="w-full h-12 rounded-xl bg-emerald-600 text-white font-semibold text-base hover:bg-emerald-700 transition flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
              {{ empty($cart) ? 'disabled' : '' }}>
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        Bayar
      </button>
    </div>
  </div>
</div>

{{-- Toast Container --}}
<div id="toast-container" class="fixed right-4 top-20 z-[60] space-y-2"></div>
@endsection

@push('scripts')
<script>
  const CSRF = '{{ csrf_token() }}';
  const ROUTES = {
    cartAdd:    '{{ route("kasir.cart.add") }}',
    cartUpdate: '{{ route("kasir.cart.update") }}',
    cartRemove: '{{ route("kasir.cart.remove") }}',
    cartClear:  '{{ route("kasir.cart.clear") }}',
    customerSelect: '{{ route("kasir.customer.select") }}',
    customerQuick:  '{{ route("kasir.customer.quick") }}',
    paymentAdd: '{{ route("kasir.pay.add") }}',
    paymentClear: '{{ route("kasir.pay.clear") }}',
    finalize:   '{{ route("kasir.finalize") }}',
    posPage:    '{{ route("kasir.pos") }}',
  };
</script>
<script src="{{ asset('js/pos-new.js') }}"></script>
@endpush
