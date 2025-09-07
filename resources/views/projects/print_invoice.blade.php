@extends('layouts.app')

@section('content')
  @php
    $currency = fn($n) => 'Rp '.number_format((float)$n,0,',','.');
  @endphp

  {{-- Toolbar cetak --}}
  <div class="no-print flex items-center justify-between mb-4">
    <a href="{{ url()->previous() }}" class="px-3 py-2 rounded-lg border text-sm text-gray-700 hover:bg-gray-50">← Kembali</a>
    <button onclick="window.print()" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">
      🖨️ Cetak Invoice
    </button>
  </div>

  <div class="bg-white rounded-xl shadow-soft-lg p-6 page">
    {{-- KOP INVOICE --}}
    <div class="flex items-start justify-between mb-6">
      <div>
        <h2 class="text-xl font-semibold text-gray-900">INVOICE</h2>
        <div class="mt-2 text-sm text-gray-700">
          <div>Kode Proyek: <span class="font-medium">{{ $project->code }}</span></div>
          <div>Judul: {{ $project->title }}</div>
          <div>Tanggal: {{ \Carbon\Carbon::parse($sale->sale_datetime)->format('d/m/Y H:i') }}</div>
          <div>Status: <span class="font-medium">{{ $sale->status }}</span></div>
        </div>
      </div>
      <div class="text-right text-sm text-gray-700">
        <div class="font-medium">Customer:</div>
        <div>
          @if($project->customer_id)
            {{ \DB::table('customers')->where('id',$project->customer_id)->value('name') }}
          @else
            —
          @endif
        </div>
      </div>
    </div>

    {{-- BAGIAN TAGIHAN UTAMA --}}
    <div class="mb-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-3">Rincian Biaya</h3>

      {{-- 1. Tabel Khusus Jasa --}}
      <div class="mb-4">
        <h4 class="text-sm font-semibold text-gray-700 mb-2">Jasa / Services</h4>
        <div class="overflow-hidden rounded-lg border border-gray-200">
          <table class="min-w-full text-sm print-table">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-2 text-left text-gray-600">Deskripsi</th>
                <th class="px-3 py-2 text-right text-gray-600">Qty</th>
                <th class="px-3 py-2 text-right text-gray-600">Harga</th>
                <th class="px-3 py-2 text-right text-gray-600">Subtotal</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
              @forelse ($serviceLines as $line)
                <tr>
                  <td class="px-3 py-2 text-gray-800">{{ $line->product_name }}</td>
                  <td class="px-3 py-2 text-right text-gray-800">{{ number_format($line->qty,0) }}</td>
                  <td class="px-3 py-2 text-right text-gray-800">{{ $currency($line->price) }}</td>
                  <td class="px-3 py-2 text-right text-gray-800">{{ $currency($line->subtotal) }}</td>
                </tr>
              @empty
                <tr><td colspan="4" class="px-3 py-3 text-center text-gray-500">Tidak ada jasa.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      {{-- 2. Tabel Khusus Material yang Ditagihkan --}}
      <div>
        <h4 class="text-sm font-semibold text-gray-700 mb-2">Material</h4>
        <div class="overflow-hidden rounded-lg border border-gray-200">
          <table class="min-w-full text-sm print-table">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-2 text-left text-gray-600">Deskripsi</th>
                <th class="px-3 py-2 text-right text-gray-600">Qty</th>
                <th class="px-3 py-2 text-right text-gray-600">Harga</th>
                <th class="px-3 py-2 text-right text-gray-600">Subtotal</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
              @forelse ($billedMaterialLines as $line)
                <tr>
                  <td class="px-3 py-2 text-gray-800">{{ $line->product_name }}</td>
                  <td class="px-3 py-2 text-right text-gray-800">{{ number_format($line->qty, 3) }}</td>
                  <td class="px-3 py-2 text-right text-gray-800">{{ $currency($line->price) }}</td>
                  <td class="px-3 py-2 text-right text-gray-800">{{ $currency($line->subtotal) }}</td>
                </tr>
              @empty
                <tr><td colspan="4" class="px-3 py-3 text-center text-gray-500">Tidak ada material yang ditagihkan.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- BAGIAN PEMBAYARAN & TOTAL --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <h3 class="text-sm font-semibold text-gray-700 mb-2">Pembayaran</h3>
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
                <tr><td colspan="3" class="px-3 py-3 text-center text-gray-500">Belum ada pembayaran.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="md:text-right">
        <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 inline-block min-w-[260px]">
          <div class="flex items-center justify-between text-sm mb-1">
            <span class="text-gray-700">Total Tagihan</span>
            <span class="font-medium text-gray-900">{{ $currency($sale->total) }}</span>
          </div>
          <div class="flex items-center justify-between text-sm mb-1">
            <span class="text-gray-700">Terbayar</span>
            <span class="font-medium text-gray-900">{{ $currency($paid) }}</span>
          </div>
          
          {{-- LOGIKA KEMBALIAN (TANPA DATABASE) --}}
          @if ((float)$sale->total > (float)$paid)
            <div class="flex items-center justify-between text-sm border-t border-gray-300 mt-1 pt-1">
              <span class="font-semibold text-gray-700">Sisa</span>
              <span class="font-semibold text-rose-600">{{ $currency($due) }}</span>
            </div>
          @else
          @endif
        </div>
      </div>
    </div>


  </div>

  {{-- Style print ringan --}}
  <style>
    @media print {
      @page { size: A4; margin: 14mm 12mm; }
      .no-print { display:none !important; }
      .page { box-shadow:none !important; border-radius:0 !important; padding:0 !important; }
      .break-before-page { page-break-before: always; }
      table.print-table { border-collapse: collapse !important; width:100%; }
      table.print-table th, table.print-table td { border:1px solid #000 !important; }
      table.print-table thead tr th { background:#f3f4f6 !important; color: #000 !important; -webkit-print-color-adjust: exact;}
    }
  </style>
@endsection