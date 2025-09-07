{{-- Katalog Produk → tambah material ke keranjang (per-baris) --}}
<div class="bg-white rounded-xl shadow-soft-lg p-4">
  <div class="flex items-center justify-between mb-3">
    <h3 class="text-sm font-semibold text-gray-700">Katalog Produk</h3>
    <form action="{{ route('projects.cart.clear') }}" method="POST" class="ml-auto">
      @csrf
      <button class="text-xs px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100">
        Kosongkan Cart
      </button>
    </form>
  </div>

  <div class="overflow-hidden rounded-lg border border-gray-200">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-3 py-2 text-left font-medium text-gray-600">SKU</th>
          <th class="px-3 py-2 text-left font-medium text-gray-600">Nama</th>
          <th class="px-3 py-2 text-left font-medium text-gray-600">UOM</th>
          <th class="px-3 py-2 text-right font-medium text-gray-600">Qty</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 bg-white">
        @forelse ($products as $p)
          <tr>
            <td class="px-3 py-2 text-gray-700">{{ $p->sku }}</td>
            <td class="px-3 py-2 text-gray-800">{{ $p->name }}</td>
            <td class="px-3 py-2 text-gray-600">
              @php
                $u = $p->uom_id ? \DB::table('uoms')->where('id',$p->uom_id)->value('code') : '-';
              @endphp
              {{ $u ?: '-' }}
            </td>
            <td class="px-3 py-2">
              <form action="{{ route('projects.cart.add') }}" method="POST" class="flex items-center justify-end gap-2">
                @csrf
                <input type="hidden" name="type" value="material">
                <input type="hidden" name="product_id" value="{{ $p->id }}">
                <input type="hidden" name="uom_id" value="{{ $p->uom_id }}">
                <input type="number" step="0.001" min="0.001" name="qty"
                       class="w-28 text-right px-2 py-1 border rounded-lg"
                       value="1">
                <button class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs hover:bg-blue-700">
                  Tambah
                </button>
              </form>
            </td>
            <td class="px-3 py-2"></td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="px-3 py-4 text-center text-gray-500">Tidak ada produk.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
