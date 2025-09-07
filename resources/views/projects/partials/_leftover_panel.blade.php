{{-- Panel Potongan Sisa yang tersedia → tambah pemakaian (meter + harga) --}}
<div class="bg-white rounded-xl shadow-soft-lg p-4">
  <h3 class="text-sm font-semibold text-gray-700 mb-3">Potongan Sisa Tersedia</h3>

  <div class="overflow-hidden rounded-lg border border-gray-200">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-3 py-2 text-left font-medium text-gray-600">SKU</th>
          <th class="px-3 py-2 text-left font-medium text-gray-600">Produk</th>
          <th class="px-3 py-2 text-right font-medium text-gray-600">Panjang (m)</th>
          <th class="px-3 py-2 text-right font-medium text-gray-600">Harga (per m)</th>
          <th class="px-3 py-2 text-right font-medium text-gray-600">Pakai (m)</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>

      <tbody class="divide-y divide-gray-100 bg-white">
        @forelse ($leftovers as $lp)
          <tr>
            <td class="px-3 py-2 text-gray-700">{{ $lp->sku }}</td>
            <td class="px-3 py-2 text-gray-800">{{ $lp->product_name }}</td>
            <td class="px-3 py-2 text-right text-gray-700">{{ number_format($lp->length_m, 3) }}</td>

            {{-- Harga per meter (ikut terkirim lewat form di kolom berikutnya) --}}
            <td class="px-3 py-2">
              <input
                form="lf-{{ $lp->id }}"
                name="price"
                type="number"
                min="0"
                step="0.01"
                inputmode="decimal"
                class="w-28 text-right px-2 py-1 border rounded-lg"
                placeholder="0.00">
            </td>

            {{-- Form tambah pemakaian untuk potongan ini --}}
            <td class="px-3 py-2">
              <form
                id="lf-{{ $lp->id }}"
                action="{{ route('projects.cart.add') }}"
                method="POST"
                class="flex items-center justify-end gap-2">
                @csrf
                <input type="hidden" name="type" value="leftover">
                <input type="hidden" name="piece_id" value="{{ $lp->id }}">
                <input
                  type="number"
                  step="0.001"
                  min="0.001"
                  max="{{ $lp->length_m }}"
                  name="used_length_m"
                  inputmode="decimal"
                  class="w-28 text-right px-2 py-1 border rounded-lg"
                  value="{{ $lp->length_m }}">
              </form>
            </td>

            <td class="px-3 py-2">
              <button
                form="lf-{{ $lp->id }}"
                class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs hover:bg-emerald-700">
                Pakai
              </button>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="px-3 py-4 text-center text-gray-500">Tidak ada potongan sisa.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
