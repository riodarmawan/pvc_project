@php $cart = $cart ?? []; @endphp

@if (count($cart))
  <div class="overflow-x-auto">
    <table class="w-full text-sm min-w-[480px]">
      <thead class="bg-white border-b border-slate-200">
        <tr>
          <th class="text-left py-2.5 px-3 sm:px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Produk</th>
          <th class="text-center py-2.5 px-3 sm:px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider w-28">Qty</th>
          <th class="text-right py-2.5 px-3 sm:px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider w-24 sm:w-28 hidden sm:table-cell">Harga</th>
          <th class="text-right py-2.5 px-3 sm:px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider w-28 sm:w-32">Subtotal</th>
          <th class="py-2.5 px-3 sm:px-4 w-10"></th>
        </tr>
      </thead>
      <tbody id="cart-tbody" class="divide-y divide-slate-100">
        @foreach ($cart as $row)
          <tr class="hover:bg-slate-50 transition-colors" data-product-id="{{ $row['product_id'] }}">
            <td class="py-3 px-3 sm:px-4">
              <div class="flex items-center gap-2 sm:gap-3">
                <div class="flex-shrink-0 w-8 h-8 sm:w-9 sm:h-9 bg-slate-100 rounded-lg flex items-center justify-center">
                  <span class="text-[10px] sm:text-xs font-bold text-slate-600">{{ strtoupper(substr($row['sku'] ?? '??', 0, 2)) }}</span>
                </div>
                <div class="min-w-0">
                  <div class="font-medium text-slate-900 truncate text-xs sm:text-sm">{{ $row['name'] ?? '-' }}</div>
                  <div class="text-[10px] sm:text-xs text-slate-400">{{ $row['sku'] ?? '' }}</div>
                </div>
              </div>
            </td>
            <td class="py-3 px-3 sm:px-4">
              <div class="flex items-center justify-center gap-0.5 sm:gap-1">
                <button onclick="checkoutUpdateQty({{ $row['product_id'] }}, {{ $row['qty'] - 1 }})"
                        class="h-7 w-7 rounded-md border border-slate-200 flex items-center justify-center hover:bg-slate-100 transition text-slate-500 flex-shrink-0">
                  <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                </button>
                <input type="number" value="{{ $row['qty'] }}" min="0"
                       onchange="checkoutUpdateQty({{ $row['product_id'] }}, this.value)"
                       class="qty-input w-12 sm:w-14 h-7 text-center text-xs sm:text-sm font-semibold text-slate-900 border border-slate-200 rounded-md focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 flex-shrink-0">
                <button onclick="checkoutUpdateQty({{ $row['product_id'] }}, {{ $row['qty'] + 1 }})"
                        class="h-7 w-7 rounded-md border border-slate-200 flex items-center justify-center hover:bg-slate-100 transition text-slate-500 flex-shrink-0">
                  <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </button>
              </div>
            </td>
            <td class="py-3 px-3 sm:px-4 text-right text-slate-700 tabular-nums text-xs sm:text-sm hidden sm:table-cell">
              Rp {{ number_format($row['price'] ?? 0, 0, ',', '.') }}
            </td>
            <td class="py-3 px-3 sm:px-4 text-right font-semibold text-slate-900 tabular-nums text-xs sm:text-sm">
              <div class="sm:hidden text-[10px] text-slate-400 font-normal mb-0.5">Rp {{ number_format($row['price'] ?? 0, 0, ',', '.') }}/item</div>
              Rp {{ number_format($row['subtotal'] ?? 0, 0, ',', '.') }}
            </td>
            <td class="py-3 px-3 sm:px-4 text-center">
              <button onclick="checkoutRemoveItem({{ $row['product_id'] }})"
                      class="h-7 w-7 rounded-md text-slate-400 hover:text-red-500 hover:bg-red-50 flex items-center justify-center transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@else
  <div class="flex flex-col items-center justify-center py-12 sm:py-16 text-center px-4">
    <div class="h-12 w-12 sm:h-14 sm:w-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-3">
      <svg class="h-6 w-6 sm:h-7 sm:w-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
    </div>
    <p class="text-sm font-medium text-slate-500">Keranjang kosong</p>
    <p class="text-xs text-slate-400 mt-1">Kembali ke POS untuk menambah produk</p>
    <a href="{{ route('kasir.pos') }}" class="mt-3 inline-flex items-center gap-1.5 h-8 px-4 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700 transition">
      <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      Tambah Produk
    </a>
  </div>
@endif
