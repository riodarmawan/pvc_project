{{-- resources/views/kasir/partials/_history_table.blade.php --}}
@if($sales->isEmpty())
  <tr>
    <td colspan="7" class="px-6 py-16 text-center">
      <div class="flex flex-col items-center gap-3">
        <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <div>
          <p class="text-sm font-medium text-slate-900">Tidak ada data</p>
          <p class="text-xs text-slate-500 mt-1">Tidak ditemukan transaksi dengan filter yang diterapkan.</p>
        </div>
      </div>
    </td>
  </tr>
@else
  @foreach($sales as $s)
    <tr class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors history-row" data-id="{{ $s->id }}">
      <td class="px-4 py-3">
        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
          #{{ $s->id }}
        </span>
      </td>
      <td class="px-4 py-3">
        <div class="text-sm font-medium text-slate-900">{{ \Carbon\Carbon::parse($s->sale_datetime)->format('d M Y') }}</div>
        <div class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($s->sale_datetime)->format('H:i') }}</div>
      </td>
      <td class="px-4 py-3">
        <span class="text-sm text-slate-700">{{ $s->branch_name }}</span>
      </td>
      <td class="px-4 py-3">
        <div class="text-sm font-medium text-slate-900">{{ $s->customer_name ?: 'Umum' }}</div>
        @if($s->customer_phone)
          <div class="text-xs text-slate-500">{{ $s->customer_phone }}</div>
        @endif
      </td>
      <td class="px-4 py-3 text-center">
        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600">
          {{ (int)($s->items_qty ?? 0) }} item
        </span>
      </td>
      <td class="px-4 py-3 text-right whitespace-nowrap sale-total" data-amount="{{ (float)$s->total }}">
        <span class="text-sm font-semibold text-emerald-600">Rp {{ number_format((float)$s->total, 0, ',', '.') }}</span>
      </td>
      <td class="px-4 py-3 text-center">
        <div class="flex items-center justify-center gap-1">
          <button class="btn-detail inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 transition" data-sale-id="{{ $s->id }}">
            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            Detail
          </button>
          <a target="_blank" href="{{ route('kasir.history.invoice', $s->id) }}" class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-600 bg-slate-50 border border-slate-200 hover:bg-slate-100 transition">
            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Cetak
          </a>
        </div>
      </td>
    </tr>
    {{-- Detail expansion row --}}
    <tr id="row-detail-{{ $s->id }}" class="hidden detail-row">
      <td colspan="7" class="px-0 py-0">
        <div class="bg-slate-50 border-l-4 border-emerald-400 mx-4 mb-3 rounded-r-lg">
          <div class="p-5 space-y-4" data-detail-container="{{ $s->id }}">
            <div class="flex items-center gap-2 text-slate-500 text-sm">
              <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
              Memuat detail...
            </div>
          </div>
        </div>
      </td>
    </tr>
  @endforeach
@endif
