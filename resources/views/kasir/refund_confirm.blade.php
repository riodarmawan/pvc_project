{{-- resources/views/kasir/refund_confirm.blade.php --}}
@extends('layouts.app', ['title' => 'Konfirmasi Retur'])

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6">
  <h1 class="text-lg font-bold text-slate-900 mb-4">Konfirmasi Retur Penjualan #{{ $sale->id }}</h1>

  <div class="bg-white rounded-xl border border-slate-200 p-4 space-y-3">
    <div class="text-sm text-slate-600">
      Total: <span class="font-semibold">Rp {{ number_format((float) $sale->total, 2, ',', '.') }}</span>
    </div>

    <form method="POST" action="{{ route('kasir.history.refund', $sale->id) }}"
          onsubmit="return confirm('Proses retur transaksi ini?')">
      @csrf

      <p class="text-xs text-slate-500 mb-2">Atur qty per item yang mau diretur (0 = lewati item ini).</p>

      <table class="w-full text-xs">
        <thead class="bg-slate-50">
          <tr>
            <th class="p-1.5 text-left">Produk</th>
            <th class="p-1.5 text-right">Terjual</th>
            <th class="p-1.5 text-right">Sisa Bisa Diretur</th>
            <th class="p-1.5 text-right">Qty Diretur</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @foreach ($lines as $l)
            <tr>
              <td class="p-1.5">{{ $l->name }} <span class="text-slate-400">({{ $l->sku }})</span></td>
              <td class="p-1.5 text-right">{{ (int) $l->qty }}</td>
              <td class="p-1.5 text-right">{{ (int) $l->refundable }}</td>
              <td class="p-1.5 text-right">
                <input type="hidden" name="items[{{ $loop->index }}][pos_sale_line_id]" value="{{ $l->id }}">
                <input type="number" name="items[{{ $loop->index }}][qty]"
                       value="{{ (int) $l->refundable }}" min="0" max="{{ (int) $l->refundable }}"
                       class="w-20 border border-slate-300 rounded-lg p-1 text-right">
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>

      <p class="text-xs text-amber-600 mt-2">Stok item yang diretur akan dikembalikan dan jurnal penjualan & HPP terkait dibalik.</p>

      <label class="block text-xs text-slate-600 mb-1 mt-3">Alasan retur</label>
      <textarea name="reason" rows="2" required
                class="w-full border border-slate-300 rounded-lg p-2 text-sm"
                placeholder="cth: barang rusak"></textarea>
      <div class="flex gap-2 mt-3">
        <button type="submit"
                class="px-4 py-2 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700">
          Proses Retur
        </button>
        <a href="{{ route('kasir.history') }}"
           class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200">
          Batal
        </a>
      </div>
    </form>
  </div>
</div>
@endsection
