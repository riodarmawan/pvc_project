@extends('layouts.app')

@section('content')
  {{-- Toolbar (tidak ikut tercetak) --}}
  <div class="no-print flex items-center justify-between mb-4">
    <div class="flex items-center gap-2">
      <a href="{{ url()->previous() }}"
         class="px-3 py-2 rounded-lg border text-sm text-gray-700 hover:bg-gray-50">← Kembali</a>
    </div>
    <div class="flex items-center gap-2">
      <button onclick="window.print()"
              class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700 shadow">
        🖨️ Cetak Surat Jalan
      </button>
    </div>
  </div>

  <div id="print-area" class="page bg-white rounded-xl shadow-soft-lg p-6">
    <div class="flex items-start justify-between mb-6">
      <div>
        <h2 class="text-2xl font-semibold text-gray-900 leading-tight">SURAT JALAN</h2>
        <div class="mt-2 text-[13px] text-gray-700">
          <div>No./Kode Proyek: <span class="font-medium">{{ $project->code }}</span></div>
          <div>Judul Pekerjaan: {{ $project->title }}</div>
          <div class="mt-2">Tanggal: {{ $now->format('d/m/Y H:i') }}</div>
        </div>
      </div>
      <div class="text-right text-[13px] text-gray-700">
        <div class="font-medium uppercase tracking-wide">Kepada:</div>
        <div class="font-semibold">{{ $project->customer_name ?? '—' }}</div>
        @if(!empty($project->customer_address))
          <div>{{ $project->customer_address }}</div>
        @endif
        @if(!empty($project->customer_phone))
          <div>Telp: {{ $project->customer_phone }}</div>
        @endif
      </div>
    </div>

    {{-- MATERIAL KELUAR --}}
    <div class="mb-6">
      <h3 class="text-sm font-semibold text-gray-700 mb-2">Barang Keluar (Materials)</h3>
      <div class="overflow-hidden rounded-lg border border-gray-200">
        <table class="min-w-full text-sm print-table">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-3 py-2 text-left text-gray-600">SKU</th>
              <th class="px-3 py-2 text-left text-gray-600">Nama</th>
              <th class="px-3 py-2 text-left text-gray-600">UOM</th>
              <th class="px-3 py-2 text-right text-gray-600">Qty</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 bg-white">
            @forelse ($moves as $m)
              <tr>
                <td class="px-3 py-2 text-gray-800">{{ $m->sku }}</td>
                <td class="px-3 py-2 text-gray-800">{{ $m->name }}</td>
                <td class="px-3 py-2 text-gray-700">{{ $m->uom }}</td>
                <td class="px-3 py-2 text-right text-gray-800">{{ number_format($m->qty,3) }}</td>
              </tr>
            @empty
              <tr><td colspan="4" class="px-3 py-3 text-center text-gray-500">Tidak ada data material.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- POTONGAN SISA TERPAKAI --}}
    <div>
      <h3 class="text-sm font-semibold text-gray-700 mb-2">Pemakaian Potongan Sisa</h3>
      <div class="overflow-hidden rounded-lg border border-gray-200">
        <table class="min-w-full text-sm print-table">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-3 py-2 text-left text-gray-600">SKU</th>
              <th class="px-3 py-2 text-left text-gray-600">Nama</th>
              <th class="px-3 py-2 text-right text-gray-600">Panjang (m)</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 bg-white">
            @forelse ($pieces as $p)
              <tr>
                <td class="px-3 py-2 text-gray-800">{{ $p->sku }}</td>
                <td class="px-3 py-2 text-gray-800">{{ $p->name }}</td>
                <td class="px-3 py-2 text-right text-gray-800">{{ number_format($p->length_m,3) }}</td>
              </tr>
            @empty
              <tr><td colspan="3" class="px-3 py-3 text-center text-gray-500">Tidak ada potongan sisa yang dipakai.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- TTD --}}
    <div class="mt-10 grid grid-cols-2 gap-6 text-sm">
      <div class="text-center">
        <div class="mb-16">Penerima</div>
        <div class="border-t border-gray-300 pt-2">Nama & Tanda Tangan</div>
      </div>
      <div class="text-center">
        <div class="mb-16">Pengirim</div>
        <div class="border-t border-gray-300 pt-2">Nama & Tanda Tangan</div>
      </div>
    </div>
  </div>

  {{-- Style khusus print --}}
  <style>
    @media print {
      @page {
        size: A4;
        margin: 14mm 12mm;
      }
      html, body {
        background: #fff !important;
      }
      .no-print { display: none !important; }
      .page {
        box-shadow: none !important;
        border-radius: 0 !important;
        padding: 0 !important;
      }
      /* Garis tabel terlihat jelas saat print */
      table.print-table {
        border-collapse: collapse !important;
        width: 100%;
      }
      table.print-table th,
      table.print-table td {
        border: 1px solid #000 !important;
      }
      table.print-table thead tr th {
        background: #f3f4f6 !important; /* abu2 terang */
      }
    }
  </style>
@endsection
