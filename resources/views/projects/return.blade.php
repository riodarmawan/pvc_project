@extends('layouts.app')

@section('content')
<div class="bg-white rounded-xl shadow-soft-lg p-4 mb-5">
  <h2 class="text-lg font-semibold text-gray-800">Pengembalian / Sisa Proyek</h2>
  <div class="mt-1 text-sm text-gray-700">
    <div>Kode: <span class="font-semibold">{{ $project->code }}</span></div>
    <div>Judul: {{ $project->title }}</div>
    <div>Customer: {{ $project->customer_name ?? '—' }}</div>
  </div>
</div>

<form action="{{ route('projects.return.process', $project->id) }}" method="POST">
  @csrf
  <div class="bg-white rounded-xl shadow-soft-lg overflow-hidden">
    <div class="px-4 py-3 border-b bg-gray-50">
      <h3 class="text-sm font-semibold text-gray-700">Produk</h3>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
<thead class="bg-gray-50">
    <tr>
        <th class="px-3 py-2 text-left text-gray-600">Produk</th>
        <th class="px-3 py-2 text-right text-gray-600">Jumlah Dikeluarkan</th>
        <th class="px-3 py-2 text-right text-gray-600">Sisa Dikembalikan</th>
        <th class="px-3 py-2 text-left text-gray-600">Kondisi</th>
    </tr>
</thead>
<tbody class="divide-y divide-gray-100 bg-white">
    @forelse($issuedMaterials as $idx => $item)
        <tr>
            <td class="px-3 py-2 text-gray-800">
                {{ $item['name'] }}
                <span class="text-gray-500 text-xs">({{ $item['sku'] }})</span>
                <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $item['product_id'] }}">
                <input type="hidden" name="items[{{ $idx }}][uom_id]" value="{{ $item['uom_id'] }}">
            </td>

            {{-- Menampilkan jumlah keluar yang sudah cerdas --}}
            <td class="px-3 py-2 text-right text-gray-500">
                {{ number_format($item['display_qty_issued'], 0, ',', '.') }} {{ $item['display_uom'] }}
            </td>

            {{-- Input sisa dikembalikan (tetap dinamis) --}}
            <td class="px-3 py-2">
                @if ($item['length_cm'] !== null && $item['length_cm'] > 0)
                    <div class="relative">
                        <input type="number" name="items[{{ $idx }}][length_m]" step="0" min="0"
                               class="w-full text-right px-3 py-2 border rounded-lg pr-8" placeholder="0">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs">m</span>
                    </div>
                @else
                    <div class="relative">
                        <input type="number" name="items[{{ $idx }}][return_qty]" step="0" min="0"
                               class="w-full text-right px-3 py-2 border rounded-lg pr-12" placeholder="0">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs">{{ $item['display_uom'] }}</span>
                    </div>
                @endif
            </td>

            {{-- Kondisi --}}
            <td class="px-3 py-2">
                <select name="items[{{ $idx }}][condition]" class="w-full px-3 py-2 border rounded-lg">
                    <option value="GOOD" selected>GOOD</option>
                    <option value="DAMAGED">DAMAGED</option>
                </select>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="4" class="px-3 py-4 text-center text-gray-500">
                Tidak ada material yang tercatat dikeluarkan untuk proyek ini.
            </td>
        </tr>
    @endforelse
</tbody>

      </table>
    </div>

    <div class="px-4 py-3 border-t bg-gray-50 flex items-center justify-end">
      <button class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700">
        Simpan Sisa
      </button>
    </div>
  </div>
</form>
@endsection