@php $cart = $cart ?? []; @endphp

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
  {{-- Header --}}
  <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-semibold text-gray-800 flex items-center">
        <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
          <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"></path>
        </svg>
        Keranjang Belanja
      </h3>
      @if (count($cart))
        <form class="js-ajax inline" method="post" action="{{ route('kasir.cart.clear') }}">
          @csrf
          <button class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all duration-200">
            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            Kosongkan
          </button>
        </form>
      @endif
    </div>
  </div>

  {{-- Content --}}
  <div class="p-6">
    @if (count($cart))
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-100">
              <th class="text-left py-3 px-2 text-xs font-semibold text-gray-600 uppercase tracking-wider">Produk</th>
              <th class="text-center py-3 px-2 w-24 text-xs font-semibold text-gray-600 uppercase tracking-wider">Qty</th>
              <th class="text-right py-3 px-2 w-32 text-xs font-semibold text-gray-600 uppercase tracking-wider">Harga</th>
              <th class="text-right py-3 px-2 w-32 text-xs font-semibold text-gray-600 uppercase tracking-wider">Subtotal</th>
              <th class="py-3 px-2 w-28 text-xs font-semibold text-gray-600 uppercase tracking-wider text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            @foreach ($cart as $row)
              <tr class="hover:bg-gray-50 transition-colors duration-200">
                <td class="py-4 px-2">
                  <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                      <span class="text-white text-xs font-bold">{{ strtoupper(substr($row['name'] ?? '-', 0, 2)) }}</span>
                    </div>
                    <div>
                      <div class="font-medium text-gray-900">{{ $row['name'] ?? '-' }}</div>
                      <div class="text-xs text-gray-500">SKU: {{ $row['sku'] ?? '' }}</div>
                    </div>
                  </div>
                </td>
                <td class="py-4 px-2 text-center">
                  <form class="js-ajax inline-flex items-center gap-1"
                        method="post" action="{{ route('kasir.cart.update') }}">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $row['product_id'] }}">
                    <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden">
                      <input type="number" name="qty" min="0"
                             value="{{ (int)($row['qty'] ?? 0) }}"
                             class="w-16 px-2 py-1 text-center border-none focus:ring-0 text-sm">
                      <button class="px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors duration-200">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                      </button>
                    </div>
                  </form>
                </td>
                <td class="py-4 px-2 text-right font-medium text-gray-900">
                  Rp {{ number_format((float)($row['price'] ?? 0), 0, ',', '.') }}
                </td>
                <td class="py-4 px-2 text-right font-semibold text-green-600">
                  Rp {{ number_format((float)($row['subtotal'] ?? 0), 0, ',', '.') }}
                </td>
                <td class="py-4 px-2 text-center">
                  <form class="js-ajax inline" method="post" action="{{ route('kasir.cart.remove') }}">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $row['product_id'] }}">
                    <button class="inline-flex items-center justify-center w-8 h-8 text-red-600 bg-red-50 rounded-lg hover:bg-red-100 focus:ring-2 focus:ring-red-500 focus:ring-offset-1 transition-all duration-200">
                      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                      </svg>
                    </button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @else
      <div class="text-center py-12">
        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-1">Keranjang Kosong</h3>
        <p class="text-gray-500">Tambahkan produk untuk memulai transaksi</p>
      </div>
    @endif
  </div>
</div>