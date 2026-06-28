@extends('layouts.dashboard', ['title' => 'Laporan Riwayat Transaksi'])

@section('content')
<div class="space-y-6">
 <div class="space-y-2">
 <h1 class="text-xl md:text-2xl font-semibold">Laporan Riwayat Transaksi</h1>
 <p class="text-slate-600 ">Jejak audit gabungan dari penjualan dan invoice proyek.</p>
 </div>

 @if (session('error'))
 <div class="rounded-xl border px-4 py-3 text-sm bg-rose-50 border-rose-200 text-rose-700 ">
 <p>{{ session('error') }}</p>
 </div>
 @endif

 <!-- Filter Section -->
 <div class="rounded-2xl border bg-white shadow-card border-slate-200 ">
 <form action="{{ route('reports.transactions.index') }}" method="GET" class="p-6 md:p-7">
 <div class="grid md:grid-cols-4 gap-4">
 {{-- Rentang Tanggal --}}
 <div class="md:col-span-2">
 <label class="block text-xs uppercase tracking-wide mb-2 text-slate-600 ">Rentang Tanggal</label>
 <div class="flex items-center gap-2">
 <input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}"
 class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 ">
 <span class="text-slate-500 ">s/d</span>
 <input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}"
 class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 ">
 </div>
 </div>

 {{-- Cabang --}}
 <div>
 <label for="branch_id" class="block text-xs uppercase tracking-wide mb-2 text-slate-600 ">Cabang</label>
 <select name="branch_id" id="branch_id"
 class="w-full appearance-none bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 ">
 <option value="">Semua Cabang</option>
 @foreach ($branches as $branch)
 <option value="{{ $branch->id }}" @selected(isset($filters['branch_id']) && $filters['branch_id'] == $branch->id)>{{ $branch->name }}</option>
 @endforeach
 </select>
 </div>

 {{-- Tipe Transaksi --}}
 <div>
 <label for="type" class="block text-xs uppercase tracking-wide mb-2 text-slate-600 ">Tipe</label>
 <select name="type" id="type"
 class="w-full appearance-none bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 ">
 <option value="">Semua Tipe</option>
 <option value="sales" @selected(isset($filters['type']) && $filters['type'] == 'sales')>Penjualan</option>
 <option value="projects" @selected(isset($filters['type']) && $filters['type'] == 'projects')>Proyek</option>
 </select>
 </div>
 </div>

 {{-- Tombol Aksi --}}
 <div class="flex flex-wrap items-center gap-3 pt-5">
 <button type="submit"
 class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border bg-slate-100 hover:bg-slate-200 border-slate-200 ">
 Filter
 </button>
 <a href="{{ route('reports.transactions.index') }}" title="Reset Filter"
 class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border hover:bg-slate-100 border-slate-200 ">
 <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="opacity-80">
 <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 011.601-1.019 7.002 7.002 0 019.999 3.585 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
 </svg>
 Reset
 </a>
 </div>
 </form>
 </div>

 <!-- Tabel Laporan -->
 <div class="rounded-2xl border bg-white shadow-card border-slate-200 ">
 <div class="p-6 md:p-7">
 <div class="overflow-x-auto">
 <table class="w-full text-sm">
 <thead class="text-left text-slate-600 ">
 <tr class="border-b border-slate-200 ">
 <th class="py-3 pr-4">Tanggal</th>
 <th class="py-3 pr-4">Tipe</th>
 <th class="py-3 pr-4">Referensi</th>
 <th class="py-3 pr-4">Pelanggan</th>
 <th class="py-3 pr-4">Cabang</th>
 <th class="py-3 pr-0 text-right">Nilai Transaksi</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-slate-200 ">
 @forelse ($transactions as $tx)
 <tr class="hover:bg-slate-50 transition-colors">
 <td class="py-3 pr-4 align-top">
 {{ \Carbon\Carbon::parse($tx->transaction_date)->isoFormat('D MMM Y, HH:mm') }}
 </td>
 <td class="py-3 pr-4 align-top">
 @if($tx->transaction_type == 'Penjualan')
 <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-emerald-100 text-emerald-700 ">
 {{ $tx->transaction_type }}
 </span>
 @else
 <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-indigo-100 text-indigo-700 ">
 {{ $tx->transaction_type }}
 </span>
 @endif
 </td>
 <td class="py-3 pr-4 align-top">
 <div class="font-medium">#{{ $tx->transaction_id }}</div>
 <div class="text-slate-600 ">{{ Str::limit($tx->description, 40) }}</div>
 </td>
 <td class="py-3 pr-4 align-top">{{ $tx->customer_name ?? '-' }}</td>
 <td class="py-3 pr-4 align-top">{{ $tx->branch_name }}</td>
 <td class="py-3 pr-0 align-top text-right">
 Rp {{ number_format($tx->transaction_value, 0, ',', '.') }}
 </td>
 </tr>
 @empty
 <tr>
 <td colspan="6" class="py-10">
 <div class="text-center space-y-2">
 <p class="font-medium">Tidak ada data transaksi</p>
 <p class="text-slate-600 ">Coba ubah atau reset filter yang Anda gunakan.</p>
 </div>
 </td>
 </tr>
 @endforelse
 </tbody>
 </table>
 </div>
 </div>
 </div>

 <!-- Paginasi -->
 <div class="flex justify-end">
 {{ $transactions->links() }}
 </div>
</div>

<!-- JS kecil: auto-submit saat filter berubah -->
<script>
(function(){
 const form = document.querySelector('form[action="{{ route('reports.transactions.index') }}"]');
 if (!form) return;

 const start = form.querySelector('input[name="start_date"]');
 const end = form.querySelector('input[name="end_date"]');
 const selBranch = form.querySelector('#branch_id');
 const selType = form.querySelector('#type');

 // submit saat select berubah
 [selBranch, selType].forEach(el => el && el.addEventListener('change', () => form.submit()));

 // jika kedua tanggal terisi lalu blur salah satu -> submit
 [start, end].forEach(el => el && el.addEventListener('change', () => {
 if ((start?.value || '') !== '' && (end?.value || '') !== '') form.submit();
 }));
})();
</script>
@endsection
