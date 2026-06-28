{{-- resources/views/kasir/refund_confirm.blade.php --}}
@extends('layouts.app', ['title' => 'Konfirmasi Retur'])

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6">
  <h1 class="text-lg font-bold text-slate-900 mb-4">Konfirmasi Retur Penjualan #{{ $sale->id }}</h1>

  <div class="bg-white rounded-xl border border-slate-200 p-4 space-y-3">
    <div class="text-sm text-slate-600">
      Total: <span class="font-semibold">Rp {{ number_format((float) $sale->total, 2, ',', '.') }}</span>
    </div>

    <table class="w-full text-xs">
      <thead class="bg-slate-50">
        <tr>
          <th class="p-1.5 text-left">Produk</th>
          <th class="p-1.5 text-right">Qty</th>
          <th class="p-1.5 text-right">Subtotal</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        @foreach ($lines as $l)
          <tr>
            <td class="p-1.5">{{ $l->name }} <span class="text-slate-400">({{ $l->sku }})</span></td>
            <td class="p-1.5 text-right">{{ (int) $l->qty }}</td>
            <td class="p-1.5 text-right">Rp {{ number_format((float) $l->subtotal, 2, ',', '.') }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <p class="text-xs text-amber-600">Stok akan dikembalikan dan jurnal penjualan & HPP dibalik.</p>

    <form method="POST" action="{{ route('kasir.history.refund', $sale->id) }}"
          onsubmit="return confirm('Proses retur transaksi ini?')">
      @csrf
      <label class="block text-xs text-slate-600 mb-1">Alasan retur</label>
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
