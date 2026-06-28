@extends('layouts.dashboard', ['title' => 'Rekonsiliasi Kas'])

@section('content')
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <h1 class="text-lg font-bold text-slate-900">Rekonsiliasi Kas</h1>
  </div>

  <form method="GET" class="flex flex-wrap items-end gap-3 bg-white rounded-xl border border-slate-200 p-4">
    <div>
      <label class="block text-xs text-slate-500 mb-1">Dari</label>
      <input type="date" name="date_from" value="{{ $dateFrom }}" class="border border-slate-300 rounded-lg p-2 text-sm">
    </div>
    <div>
      <label class="block text-xs text-slate-500 mb-1">Sampai</label>
      <input type="date" name="date_to" value="{{ $dateTo }}" class="border border-slate-300 rounded-lg p-2 text-sm">
    </div>
    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700">Terapkan</button>
  </form>

  <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-slate-600">
        <tr>
          <th class="p-3 text-left">Cabang</th>
          <th class="p-3 text-right">Buku Besar Kas (1100)</th>
          <th class="p-3 text-right">Kas Operasional</th>
          <th class="p-3 text-right">Selisih</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        @forelse ($rows as $r)
          <tr>
            <td class="p-3">{{ $r->branch_name }}</td>
            <td class="p-3 text-right">Rp {{ number_format((float) $r->gl_kas, 2, ',', '.') }}</td>
            <td class="p-3 text-right">Rp {{ number_format((float) $r->operational, 2, ',', '.') }}</td>
            <td class="p-3 text-right font-semibold {{ abs((float) $r->selisih) > 0.01 ? 'text-red-600' : 'text-emerald-600' }}">
              Rp {{ number_format((float) $r->selisih, 2, ',', '.') }}
            </td>
          </tr>
        @empty
          <tr><td colspan="4" class="p-6 text-center text-slate-400">Tidak ada cabang aktif.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <p class="text-xs text-slate-500">
    Selisih ≠ 0 menandakan ada transaksi kas yang belum terjurnal atau pencatatan kas operasional yang tidak konsisten.
  </p>
</div>
@endsection
