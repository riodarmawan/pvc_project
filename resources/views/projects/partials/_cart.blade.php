{{-- Keranjang Project: Materials + Pemakaian Potongan --}}
<div class="bg-white rounded-xl shadow-soft-lg p-4">
  <h3 class="text-sm font-semibold text-gray-700 mb-3">Keranjang Proyek</h3>

  {{-- MATERIALS --}}
  <div class="mb-6">
    <div class="flex items-center justify-between mb-2">
      <span class="text-xs font-semibold text-gray-600">Materials</span>
    </div>
    <div class="overflow-hidden rounded-lg border border-gray-200">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-2 text-left text-gray-600">Produk</th>
            <th class="px-3 py-2 text-left text-gray-600">UOM</th>
            <th class="px-3 py-2 text-right text-gray-600">Qty</th>
            <th class="px-3 py-2 text-right text-gray-600">Harga (HPP)</th>
            <th class="px-3 py-2 text-right text-gray-600">Subtotal</th>
            <th class="px-3 py-2 w-20"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          @php $matSum = 0; @endphp
          @forelse (($cart['materials'] ?? []) as $m)
            @php
              $price = (float)($m['price'] ?? 0);
              $sub   = (float)($m['qty'] ?? 0) * $price;
              $matSum += $sub;
            @endphp
            <tr>
              <td class="px-3 py-2 text-gray-800">{{ $m['name'] }}</td>
              <td class="px-3 py-2 text-gray-700">{{ $m['uom'] }}</td>
              
              {{-- Qty (display only) --}}
              <td class="px-3 py-2 text-right text-gray-800">{{ $m['qty'] }}</td>
              
              {{-- Harga HPP --}}
              <td class="px-3 py-2 text-right">Rp {{ number_format($price,0,',','.') }}</td>
              <td class="px-3 py-2 text-right">Rp {{ number_format($sub,0,',','.') }}</td>

              {{-- Remove --}}
              <td class="px-3 py-2 text-right">
                <form action="{{ route('projects.cart.remove') }}" method="POST" class="inline-block">
                  @csrf
                  <input type="hidden" name="kind" value="material">
                  <input type="hidden" name="row_id" value="{{ $m['row_id'] }}">
                  <button class="px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 text-xs hover:bg-rose-100">
                    Hapus
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="px-3 py-3 text-center text-gray-500">Belum ada material.</td>
            </tr>
          @endforelse
        </tbody>
        @if(($cart['materials'] ?? []))
          <tfoot class="bg-gray-50">
            <tr>
              <th colspan="4" class="px-3 py-2 text-right text-gray-700">Total Materials</th>
              <th class="px-3 py-2 text-right text-gray-900">Rp {{ number_format($matSum,0,',','.') }}</th>
              <th></th>
            </tr>
          </tfoot>
        @endif
      </table>
    </div>
  </div>

  {{-- LEFTOVERS (pemakaian meter + harga per meter) --}}
  <div>
    <div class="flex items-center justify-between mb-2">
      <span class="text-xs font-semibold text-gray-600">Pemakaian Potongan</span>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-2 text-left text-gray-600">Produk</th>
            <th class="px-3 py-2 text-right text-gray-600">Harga (per m)</th>
            <th class="px-3 py-2 text-right text-gray-600">Pakai (m)</th>
            <th class="px-3 py-2 text-right text-gray-600">Tersedia (m)</th>
            <th class="px-3 py-2 w-20"></th>
          </tr>
        </thead>

        <tbody class="divide-y divide-gray-100 bg-white">
          @forelse (($cart['leftovers'] ?? []) as $l)
            <tr>
              <td class="px-3 py-2 text-gray-800">{{ $l['name'] }}</td>

              {{-- Harga per meter (display only) --}}
              <td class="px-3 py-2 text-right text-gray-800">
                {{ number_format($l['price_m'] ?? ($l['price'] ?? 0), 0) }}
              </td>

              {{-- Pakai meter (display only) --}}
              <td class="px-3 py-2 text-right text-gray-800">
                {{ $l['used_length_m'] }}
              </td>

              <td class="px-3 py-2 text-right text-gray-700">
                {{ $l['available_m'] !== null ? number_format($l['available_m'], 0) : '—' }}
              </td>

              <td class="px-3 py-2 text-right">
                {{-- Form HAPUS leftover saja --}}
                <form action="{{ route('projects.cart.remove') }}" method="POST" class="inline-block">
                  @csrf
                  <input type="hidden" name="kind" value="leftover">
                  <input type="hidden" name="row_id" value="{{ $l['row_id'] }}">
                  <button class="px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 text-xs hover:bg-rose-100">
                    Hapus
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="px-3 py-3 text-center text-gray-500">Belum ada pemakaian potongan.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
