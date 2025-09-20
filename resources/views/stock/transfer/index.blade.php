@extends('layouts.dashboard', ['title' => 'History Transfer Stok'])

@section('content')
<div class="space-y-6">
  <!-- Success Toast -->
  @if (session('success'))
  <div id="successToast" class="fixed top-20 right-6 z-40">
    <div class="rounded-xl border bg-emerald-50 border-emerald-200 text-emerald-700 shadow-card dark:bg-emerald-500/15 dark:border-emerald-500/30 dark:text-emerald-200">
      <div class="px-4 py-3 flex items-start gap-3">
        <div class="h-8 w-8 rounded-lg grid place-items-center bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200">
          <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
          </svg>
        </div>
        <div>
          <h4 class="font-semibold">Transfer Berhasil!</h4>
          <p class="text-sm">{{ session('success') }}</p>
        </div>
        <button id="closeSuccessToast" class="ml-4 p-1 rounded-md hover:bg-emerald-100/70 dark:hover:bg-white/10">
          <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
          </svg>
        </button>
      </div>
    </div>
  </div>
  @endif

  <!-- Header Section -->
  <div class="flex items-center justify-between">
    <div class="flex items-start gap-3">
      <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-brand/20 to-brandDark/20 border border-brand/20 grid place-items-center">
        <svg class="h-5 w-5 text-brand dark:text-brandDark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
      </div>
      <div>
        <h1 class="text-xl md:text-2xl font-semibold">History Transfer Stok</h1>
        <p class="text-slate-600 dark:text-slate-400">History surat jalan transfer dengan filter rentang waktu</p>
      </div>
    </div>
    <a href="{{ route('stock.transfer.create') }}" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl text-white bg-brand hover:bg-brand/90 border-transparent">
      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
      </svg>
      <span>Transfer Baru</span>
    </a>
  </div>

  <!-- Filter Card -->
  <div class="rounded-2xl border bg-white shadow-card border-slate-200 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
    <div class="p-6">
      <form method="GET" action="{{ route('stock.transfer.index') }}" class="space-y-4">
        <div class="flex items-center gap-3 mb-4">
          <div class="h-8 w-8 rounded-lg bg-brand/10 border border-brand/20 grid place-items-center">
            <svg class="h-4 w-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"/>
            </svg>
          </div>
          <h3 class="font-semibold">Filter Rentang Waktu</h3>
        </div>
        
        <div class="grid md:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs uppercase tracking-wide mb-2 text-slate-600 dark:text-slate-400">Tanggal Mulai</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" 
                   class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
          </div>
          <div>
            <label class="block text-xs uppercase tracking-wide mb-2 text-slate-600 dark:text-slate-400">Tanggal Akhir</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" 
                   class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
          </div>
          <div class="flex items-end">
            <button type="submit" class="h-11 px-6 rounded-xl text-white bg-brand hover:bg-brand/90 border-transparent">
              <span class="flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Filter
              </span>
            </button>
          </div>
        </div>
        
        <!-- Quick Filter Buttons -->
        <div class="flex flex-wrap gap-2 pt-3">
          <a href="{{ route('stock.transfer.index', ['date_from' => now()->format('Y-m-d'), 'date_to' => now()->format('Y-m-d')]) }}" 
             class="px-3 py-1.5 text-xs rounded-lg border hover:bg-brand hover:text-white transition-colors {{ request('date_from') == now()->format('Y-m-d') && request('date_to') == now()->format('Y-m-d') ? 'bg-brand text-white' : 'bg-white border-slate-200' }}">
            Hari Ini
          </a>
          <a href="{{ route('stock.transfer.index', ['date_from' => now()->subDays(7)->format('Y-m-d'), 'date_to' => now()->format('Y-m-d')]) }}" 
             class="px-3 py-1.5 text-xs rounded-lg border hover:bg-brand hover:text-white transition-colors bg-white border-slate-200">
            7 Hari Terakhir
          </a>
          <a href="{{ route('stock.transfer.index', ['date_from' => now()->startOfMonth()->format('Y-m-d'), 'date_to' => now()->format('Y-m-d')]) }}" 
             class="px-3 py-1.5 text-xs rounded-lg border hover:bg-brand hover:text-white transition-colors bg-white border-slate-200">
            Bulan Ini
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- Transfer List -->
  <div class="rounded-2xl border bg-white shadow-card border-slate-200 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
    <div class="p-6">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <div class="h-8 w-8 rounded-lg bg-slate-100 border border-slate-200 grid place-items-center dark:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
          </div>
          <h3 class="font-semibold">Daftar Transfer</h3>
        </div>
        <div class="text-sm text-slate-600 dark:text-slate-400">
          Total: {{ $totalTransfers }} transfer
        </div>
      </div>

      @if($transfers->count() > 0)
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-200 dark:border-white/10">
                <th class="text-left py-3 font-semibold">ID Transfer</th>
                <th class="text-left py-3 font-semibold">Tanggal</th>
                <th class="text-left py-3 font-semibold">Cabang Asal</th>
                <th class="text-left py-3 font-semibold">Cabang Tujuan</th>
                <th class="text-center py-3 font-semibold">Total Item</th>
                <th class="text-center py-3 font-semibold">Total Qty</th>
                <th class="text-center py-3 font-semibold">Status</th>
                <th class="text-center py-3 font-semibold">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @foreach($transfers as $transfer)
              <tr class="border-b border-slate-100 hover:bg-slate-50 dark:border-white/5 dark:hover:bg-white/5">
                <td class="py-4">
                  <div class="font-semibold text-brand">SJ-{{ date('ymd', strtotime($transfer->created_at)) }}-{{ str_pad($transfer->id, 4, '0', STR_PAD_LEFT) }}</div>
                  <div class="text-xs text-slate-500">ID: {{ $transfer->id }}</div>
                </td>
                <td class="py-4">
                  <div class="font-medium">{{ date('d/m/Y H:i', strtotime($transfer->created_at)) }}</div>
                  <div class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($transfer->created_at)->diffForHumans() }}</div>
                </td>
                <td class="py-4">{{ $transfer->branch_from_name }}</td>
                <td class="py-4">{{ $transfer->branch_to_name }}</td>
                <td class="py-4 text-center">{{ $transfer->total_items ?? 0 }}</td>
                <td class="py-4 text-center">{{ number_format($transfer->total_qty ?? 0, 2) }}</td>
                <td class="py-4 text-center">
                  <span class="px-2 py-1 text-xs rounded-full 
                    {{ $transfer->status === 'CLOSED' ? 'bg-emerald-100 text-emerald-700' : 'bg-yellow-100 text-yellow-700' }}">
                    {{ $transfer->status }}
                  </span>
                </td>
                <td class="py-4 text-center">
                  <div class="flex items-center justify-center gap-2">
                    <a href="{{ route('stock.transfer.delivery-note', $transfer->id) }}" target="_blank"
                       class="p-2 rounded-lg hover:bg-blue-100 text-blue-600 dark:hover:bg-blue-500/20" title="Cetak Surat Jalan">
                      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                      </svg>
                    </a>
                  </div>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        
        <!-- Pagination -->
        <div class="mt-6 border-t border-slate-200 dark:border-white/10 pt-6">
          {{ $transfers->links() }}
        </div>
      @else
        <div class="text-center py-12">
          <div class="h-20 w-20 mx-auto mb-4 rounded-lg bg-slate-100 border border-slate-200 grid place-items-center dark:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
            <svg class="h-8 w-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
          </div>
          <h3 class="font-semibold mb-2">Tidak Ada Transfer</h3>
          <p class="text-slate-600 dark:text-slate-400">Tidak ada transfer stok pada rentang waktu yang dipilih</p>
        </div>
      @endif
    </div>
  </div>
</div>

<script>
// Auto hide toast
setTimeout(() => document.getElementById('successToast')?.remove(), 5000);

document.getElementById('closeSuccessToast')?.addEventListener('click', () => {
    document.getElementById('successToast')?.remove();
});
</script>
@endsection
