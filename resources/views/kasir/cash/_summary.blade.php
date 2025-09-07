@php
  $sum = $summary ?? [
    'saldo'=>0,'pos_cash'=>0,'pos_non_cash'=>0,'in'=>0,'out'=>0,'net'=>0
  ];
@endphp

<div class="grid grid-cols-1 md:grid-cols-5 gap-4">
  {{-- Saldo Kas Saat Ini (uang fisik) --}}
  <div class="bg-white rounded-xl shadow p-4">
    <div class="text-xs text-gray-500">Saldo Kas Saat Ini</div>
    <div class="text-2xl font-semibold mt-1">
      Rp {{ number_format($sum['saldo'],2,',','.') }}
    </div>
    <div class="text-xs text-gray-500 mt-1">Cabang #{{ $branchId }}</div>
  </div>

  {{-- POS (Tunai) — Periode --}}
  <div class="bg-white rounded-xl shadow p-4">
    <div class="text-xs text-gray-500">POS (Tunai) — Periode</div>
    <div class="text-xl font-semibold mt-1">
      Rp {{ number_format($sum['pos_cash'],2,',','.') }}
    </div>
    <div class="text-xs text-gray-500 mt-1">{{ $start }} → {{ $end }}</div>
  </div>

  {{-- POS (Non-Tunai) — Periode (CARD/QR/TRANSFER) --}}
  <div class="bg-white rounded-xl shadow p-4">
    <div class="text-xs text-gray-500">POS (Non-Tunai) — Periode</div>
    <div class="text-xl font-semibold mt-1">
      Rp {{ number_format($sum['pos_non_cash'],2,',','.') }}
    </div>
    <div class="text-xs text-gray-500 mt-1">Kartu • QR • Transfer</div>
  </div>

  {{-- Kas Masuk — Periode (manual/penyesuaian) --}}
  <div class="bg-white rounded-xl shadow p-4">
    <div class="text-xs text-gray-500">Kas Masuk — Periode</div>
    <div class="text-xl font-semibold mt-1 text-emerald-700">
      Rp {{ number_format($sum['in'],2,',','.') }}
    </div>
  </div>

  {{-- Kas Keluar — Periode + Net kas --}}
  <div class="bg-white rounded-xl shadow p-4">
    <div class="text-xs text-gray-500">Kas Keluar — Periode</div>
    <div class="text-xl font-semibold mt-1 text-rose-700">
      Rp {{ number_format($sum['out'],2,',','.') }}
    </div>
    <div class="text-xs text-gray-500 mt-1">
      Net (Tunai): <b>Rp {{ number_format($sum['net'],2,',','.') }}</b>
    </div>
  </div>
</div>
