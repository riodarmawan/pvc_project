@php
  $sum = $summary ?? [
    'saldo'=>0,'pos_cash'=>0,'pos_non_cash'=>0,'in'=>0,'out'=>0,'net'=>0
  ];
@endphp

<div class="grid grid-cols-2 md:grid-cols-5 gap-3">
  {{-- Saldo Kas Saat Ini --}}
  <div class="col-span-2 md:col-span-1 bg-white rounded-xl border border-slate-200 p-4">
    <div class="flex items-center gap-2 mb-2">
      <div class="h-8 w-8 rounded-lg bg-emerald-50 flex items-center justify-center">
        <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
      </div>
      <span class="text-xs font-medium text-slate-500">Saldo Kas</span>
    </div>
    <p class="text-lg font-bold text-slate-900 tabular-nums">Rp {{ number_format($sum['saldo'],0,',','.') }}</p>
    <p class="text-[10px] text-slate-400 mt-1">Total keseluruhan</p>
  </div>

  {{-- POS Tunai --}}
  <div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="flex items-center gap-2 mb-2">
      <div class="h-8 w-8 rounded-lg bg-blue-50 flex items-center justify-center">
        <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
      </div>
      <span class="text-xs font-medium text-slate-500">POS Tunai</span>
    </div>
    <p class="text-lg font-bold text-blue-700 tabular-nums">Rp {{ number_format($sum['pos_cash'],0,',','.') }}</p>
    <p class="text-[10px] text-slate-400 mt-1">{{ $start }} → {{ $end }}</p>
  </div>

  {{-- POS Non-Tunai --}}
  <div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="flex items-center gap-2 mb-2">
      <div class="h-8 w-8 rounded-lg bg-violet-50 flex items-center justify-center">
        <svg class="h-4 w-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
      </div>
      <span class="text-xs font-medium text-slate-500">POS Non-Tunai</span>
    </div>
    <p class="text-lg font-bold text-violet-700 tabular-nums">Rp {{ number_format($sum['pos_non_cash'],0,',','.') }}</p>
    <p class="text-[10px] text-slate-400 mt-1">Kartu / QR / Transfer</p>
  </div>

  {{-- Kas Masuk --}}
  <div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="flex items-center gap-2 mb-2">
      <div class="h-8 w-8 rounded-lg bg-emerald-50 flex items-center justify-center">
        <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/></svg>
      </div>
      <span class="text-xs font-medium text-slate-500">Kas Masuk</span>
    </div>
    <p class="text-lg font-bold text-emerald-700 tabular-nums">Rp {{ number_format($sum['in'],0,',','.') }}</p>
    <p class="text-[10px] text-slate-400 mt-1">Setoran / penyesuaian</p>
  </div>

  {{-- Kas Keluar + Net --}}
  <div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="flex items-center gap-2 mb-2">
      <div class="h-8 w-8 rounded-lg bg-rose-50 flex items-center justify-center">
        <svg class="h-4 w-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/></svg>
      </div>
      <span class="text-xs font-medium text-slate-500">Kas Keluar</span>
    </div>
    <p class="text-lg font-bold text-rose-700 tabular-nums">Rp {{ number_format($sum['out'],0,',','.') }}</p>
    <p class="text-[10px] text-slate-400 mt-1">Net: <span class="font-semibold text-slate-700">Rp {{ number_format($sum['net'],0,',','.') }}</span></p>
  </div>
</div>
