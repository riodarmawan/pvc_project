@extends('layouts.app', ['title' => 'Kas Kasir'])

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

  {{-- Filter Periode --}}
  <form method="get" class="bg-white rounded-xl shadow p-4 grid grid-cols-1 md:grid-cols-5 gap-3">
    <div class="md:col-span-2">
      <label class="block text-xs text-gray-500 mb-1">Cabang</label>
      <input type="text" disabled
             value="ID Cabang: {{ $branchId }}"
             class="w-full rounded-lg border-gray-300 bg-gray-50">
    </div>
    <div>
      <label class="block text-xs text-gray-500 mb-1">Dari</label>
      <input type="date" name="start_date" value="{{ $start }}" class="w-full rounded-lg border-gray-300">
    </div>
    <div>
      <label class="block text-xs text-gray-500 mb-1">Sampai</label>
      <input type="date" name="end_date" value="{{ $end }}" class="w-full rounded-lg border-gray-300">
    </div>
    <div class="flex items-end">
      <button class="px-4 py-2 rounded-lg bg-gray-900 text-white w-full">Terapkan</button>
    </div>
  </form>

  {{-- Summary --}}
  <div id="summary-panel">
    @include('kasir.cash._summary', ['summary' => $summary, 'branchId' => $branchId, 'start'=>$start, 'end'=>$end])
  </div>

  {{-- Form Input --}}
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
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
