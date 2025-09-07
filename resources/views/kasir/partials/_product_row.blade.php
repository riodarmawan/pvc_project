@php
  // Pastikan kolom yang dipakai sudah disiapkan controller: $p->id, sku, name, price, stock
  $inputId = 'qty-' . $p->id;
  $isOutOfStock = (int)($p->stock ?? 0) <= 0;
@endphp
<tr class="hover:bg-gray-50 transition-colors duration-200 {{ $isOutOfStock ? 'opacity-60' : '' }}">
  <td class="py-4 px-6">
    <div class="flex items-center space-x-3">
      <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg flex items-center justify-center">
        <span class="text-white text-xs font-bold">{{ strtoupper(substr($p->name ?? '-', 0, 2)) }}</span>
      </div>
      <div>
        <div class="font-medium text-gray-900">{{ $p->name }}</div>
        <div class="text-xs text-gray-500">SKU: {{ $p->sku }}</div>
      </div>
    </div>
  </td>
  <td class="py-4 px-6 text-right font-semibold text-gray-900">
    Rp {{ number_format((float)($p->price ?? 0), 0, ',', '.') }}
  </td>
  <td class="py-4 px-6 text-center">
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
      {{ $isOutOfStock ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
      {{ (int)($p->stock ?? 0) }} {{ $isOutOfStock ? 'Habis' : 'Tersedia' }}
    </span>
  </td>
  <td class="py-4 px-6">
    <div class="flex items-center gap-3 justify-center">
      <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden {{ $isOutOfStock ? 'opacity-50' : '' }}">
        <input id="{{ $inputId }}" type="number" min="1" value="1" {{ $isOutOfStock ? 'disabled' : '' }}
               class="w-16 px-2 py-2 text-center border-none focus:ring-0 text-sm {{ $isOutOfStock ? 'bg-gray-100' : '' }}">
        <button type="button" {{ $isOutOfStock ? 'disabled' : '' }}
                class="btn-add px-4 py-2 bg-purple-600 text-white hover:bg-purple-700 focus:ring-2 focus:ring-purple-500 focus:ring-offset-1 transition-all duration-200 flex items-center {{ $isOutOfStock ? 'opacity-50 cursor-not-allowed' : '' }}"
                data-product-id="{{ $p->id }}"
                data-qty-input="#{{ $inputId }}">
          <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path>
          </svg>
          {{ $isOutOfStock ? 'Habis' : 'Tambah' }}
        </button>
      </div>
    </div>
  </td>
</tr>