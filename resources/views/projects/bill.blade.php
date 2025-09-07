@extends('layouts.app')

@section('content')
@php
  $currency = fn($n) => 'Rp '.number_format((float)$n,0,',','.');
@endphp

<div class="flex items-center justify-between mb-4">
  <h2 class="text-lg font-semibold text-gray-800">
    Pembayaran Proyek — {{ $project->code }}
  </h2>
  <div class="flex gap-2">
    <a href="{{ route('projects.print.invoice.byproject', $project->id) }}"
       class="px-3 py-2 rounded-lg text-sm bg-indigo-600 text-white hover:bg-indigo-700">
      Cetak Invoice
    </a>
    <a href="{{ route('projects.index') }}"
       class="px-3 py-2 rounded-lg text-sm bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100">
      Kembali
    </a>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <!-- Ringkasan -->
  <div class="lg:col-span-2 bg-white rounded-xl shadow-soft-lg p-5">
    <div class="mb-5">
      <div class="text-sm text-gray-700">
        <div>Kode Proyek: <span class="font-medium">{{ $project->code }}</span></div>
        <div>Judul: {{ $project->title }}</div>
        <div>Tanggal: {{ \Carbon\Carbon::parse($sale->sale_datetime)->format('d/m/Y H:i') }}</div>
        <div>Status: <span class="font-medium">{{ $sale->status }}</span></div>
        <div>Customer:
          @if($project->customer_id)
            {{ \DB::table('customers')->where('id',$project->customer_id)->value('name') }}
          @else — @endif
        </div>
      </div>
    </div>

    <h3 class="text-sm font-semibold text-gray-700 mb-2">Rincian Jasa</h3>
    <div class="overflow-hidden rounded-lg border border-gray-200 mb-6">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-2 text-left text-gray-600">Deskripsi</th>
            <th class="px-3 py-2 text-right text-gray-600">Qty</th>
            <th class="px-3 py-2 text-right text-gray-600">Harga</th>
            <th class="px-3 py-2 text-right text-gray-600">Subtotal</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          @forelse ($lines as $ln)
            @php $name = \DB::table('products')->where('id',$ln->product_id)->value('name') ?? 'Jasa'; @endphp
            <tr>
              <td class="px-3 py-2 text-gray-800">{{ $name }}</td>
              <td class="px-3 py-2 text-right text-gray-800">{{ number_format($ln->qty,0) }}</td>
              <td class="px-3 py-2 text-right text-gray-800">{{ $currency($ln->price) }}</td>
              <td class="px-3 py-2 text-right text-gray-800">{{ $currency($ln->subtotal) }}</td>
            </tr>
          @empty
            <tr><td colspan="4" class="px-3 py-4 text-center text-gray-500">Tidak ada baris jasa.</td></tr>
          @endforelse
        </tbody>
        <tfoot class="bg-gray-50">
          <tr>
            <th colspan="3" class="px-3 py-2 text-right text-gray-700">Total</th>
            <th class="px-3 py-2 text-right text-gray-900">{{ $currency($sale->total) }}</th>
          </tr>
        </tfoot>
      </table>
    </div>

    <h3 class="text-sm font-semibold text-gray-700 mb-2">Pembayaran Masuk</h3>
    <div class="overflow-hidden rounded-lg border border-gray-200">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-2 text-left text-gray-600">Metode</th>
            <th class="px-3 py-2 text-left text-gray-600">Ref</th>
            <th class="px-3 py-2 text-right text-gray-600">Jumlah</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          @forelse ($payments as $pay)
            <tr>
              <td class="px-3 py-2 text-gray-800">{{ $pay->method }}</td>
              <td class="px-3 py-2 text-gray-600">{{ $pay->ref_no ?? '—' }}</td>
              <td class="px-3 py-2 text-right text-gray-800">{{ $currency($pay->amount) }}</td>
            </tr>
          @empty
            <tr><td colspan="3" class="px-3 py-4 text-center text-gray-500">Belum ada pembayaran.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <h3 class="text-sm font-semibold text-gray-700 mb-2">Rincian Tagihan</h3>
<div class="overflow-hidden rounded-lg border border-gray-200">
  <table class="min-w-full text-sm">
    <thead class="bg-gray-50">
      <tr>
        <th class="px-3 py-2 text-left text-gray-600">Deskripsi</th>
        <th class="px-3 py-2 text-right text-gray-600">Qty</th>
        <th class="px-3 py-2 text-right text-gray-600">Harga</th>
        <th class="px-3 py-2 text-right text-gray-600">Subtotal</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-100 bg-white">
      @php $sum = 0; @endphp
      @forelse ($lines as $ln)
        @php
          $name = \DB::table('products')->where('id',$ln->product_id)->value('name') ?? 'Item';
          $sum += (float)$ln->subtotal;
        @endphp
        <tr>
          <td class="px-3 py-2 text-gray-800">{{ $name }}</td>
          <td class="px-3 py-2 text-right text-gray-800">{{ rtrim(rtrim(number_format($ln->qty,3,',','.'), '0'), ',') }}</td>
          <td class="px-3 py-2 text-right text-gray-800">{{ $currency($ln->price) }}</td>
          <td class="px-3 py-2 text-right text-gray-800">{{ $currency($ln->subtotal) }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="4" class="px-3 py-3 text-center text-gray-500">Tidak ada baris tagihan.</td>
        </tr>
      @endforelse
    </tbody>
    <tfoot class="bg-gray-50">
      <tr>
        <th colspan="3" class="px-3 py-2 text-right text-gray-700">Total</th>
        <th class="px-3 py-2 text-right text-gray-900">{{ $currency($sale->total) }}</th>
      </tr>
    </tfoot>
  </table>
</div>

  </div>
{{-- ... header yang sudah ada ... --}}



{{-- Rincian Jasa (seperti sebelumnya) --}}

  <!-- Panel Tambah Pembayaran -->
  <div class="bg-white rounded-xl shadow-soft-lg p-5">
    <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 mb-5">
      <div class="flex items-center justify-between text-sm mb-1">
        <span class="text-gray-700">Total</span>
        <span class="font-medium text-gray-900">{{ $currency($sale->total) }}</span>
      </div>
      <div class="flex items-center justify-between text-sm mb-1">
        <span class="text-gray-700">Terbayar</span>
        <span class="font-medium text-gray-900">{{ $currency($paid) }}</span>
      </div>
      <div class="flex items-center justify-between text-sm">
        <span class="text-gray-700">Sisa</span>
        <span class="font-semibold text-rose-600">{{ $currency($due) }}</span>
      </div>
    </div>

    <form method="POST" action="{{ route('projects.bill.pay.add', $project->id) }}" class="space-y-3">
      @csrf
      <div>
        <label class="block text-xs text-gray-600 mb-1">Metode</label>
        <select name="method" class="w-full rounded-lg border-gray-300">
          <option value="CASH">CASH</option>
          <option value="CARD">CARD</option>
          <option value="QR">QR</option>
          <option value="TRANSFER">TRANSFER</option>
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-600 mb-1">Jumlah</label>
        <input type="number" name="amount" step="1" min="0" required class="w-full rounded-lg border-gray-300" />
      </div>
      <div>
        <label class="block text-xs text-gray-600 mb-1">Ref / No. Transaksi (opsional)</label>
        <input type="text" name="ref_no" class="w-full rounded-lg border-gray-300" />
      </div>
      <div class="flex gap-2">
        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700">
          Tambah Pembayaran
        </button>
        <form method="POST" action="{{ route('projects.bill.pay.clear', $project->id) }}">
          @csrf
          <button type="submit" class="px-4 py-2 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 text-sm hover:bg-rose-100">
            Bersihkan Pembayaran
          </button>
        </form>
      </div>
    </form>
  </div>
</div>
@endsection
