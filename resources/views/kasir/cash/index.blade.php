@extends('layouts.app', ['title' => 'Kas Kasir'])

@section('content')
<div class="space-y-4">

  {{-- Filter Periode --}}
  <form method="get" class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col sm:flex-row items-end gap-3">
    <div class="flex-1 w-full sm:w-auto">
      <label class="block text-xs font-medium text-slate-500 mb-1">Dari</label>
      <input type="date" name="start_date" value="{{ $start }}"
             class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
    </div>
    <div class="flex-1 w-full sm:w-auto">
      <label class="block text-xs font-medium text-slate-500 mb-1">Sampai</label>
      <input type="date" name="end_date" value="{{ $end }}"
             class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
    </div>
    <button class="h-10 px-5 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition flex-shrink-0">
      Terapkan
    </button>
  </form>

  {{-- Summary --}}
  <div id="summary-panel">
    @include('kasir.cash._summary', ['summary' => $summary, 'branchId' => $branchId, 'start'=>$start, 'end'=>$end])
  </div>

  {{-- Form Input --}}
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    @include('kasir.cash._form_out', ['branchId' => $branchId, 'start'=>$start, 'end'=>$end])
    @include('kasir.cash._form_adjust', ['branchId' => $branchId, 'start'=>$start, 'end'=>$end])
  </div>

  {{-- Tabel Mutasi --}}
  <div id="table-panel">
    @include('kasir.cash._table', ['moves' => $moves])
  </div>
</div>
@endsection

@push('scripts')
  <script src="{{ asset('js/cash.js') }}" defer></script>
@endpush
