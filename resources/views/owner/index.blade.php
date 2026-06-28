@extends('layouts.dashboard', ['title' => 'Dashboard'])
@section('content')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

{{-- Welcome + Filter --}}
<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
  <div>
    <h2 class="text-2xl font-bold text-slate-900">Selamat Datang, {{ explode(' ', Auth::user()->full_name)[0] }}</h2>
    <p class="text-sm text-slate-500 mt-1">{{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}</p>
  </div>

  <div class="flex items-center gap-2">
    <select id="branchFilter" class="h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
      <option value="">Semua Cabang</option>
      @foreach($branches as $branch)
        <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
      @endforeach
    </select>

    <select id="dateRangeFilter" class="h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
      <option value="today" {{ $dateRange == 'today' ? 'selected' : '' }}>Hari Ini</option>
      <option value="7days" {{ $dateRange == '7days' ? 'selected' : '' }}>7 Hari</option>
      <option value="30days" {{ $dateRange == '30days' ? 'selected' : '' }}>30 Hari</option>
      <option value="month" {{ $dateRange == 'month' ? 'selected' : '' }}>Bulan Ini</option>
    </select>

    <button onclick="location.reload()" class="h-10 w-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center hover:bg-slate-50 transition">
      <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
      </svg>
    </button>
  </div>
</div>

{{-- Summary KPI Cards --}}
<div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
  {{-- Total Penjualan --}}
  <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-6">
    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-400 to-emerald-600"></div>
    <div class="flex items-start justify-between">
      <div>
        <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Total Penjualan</p>
        <p class="mt-2 text-2xl font-bold text-slate-900">Rp {{ number_format($totalPenjualan, 0, ',', '.') }}</p>
      </div>
      <div class="h-11 w-11 rounded-xl bg-emerald-100 flex items-center justify-center">
        <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
    </div>
  </div>

  {{-- Laba Bersih --}}
  <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-6">
    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-400 to-emerald-600"></div>
    <div class="flex items-start justify-between">
      <div>
        <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Laba Bersih</p>
        <p class="mt-2 text-2xl font-bold text-slate-900">Rp {{ number_format($labaBersih, 0, ',', '.') }}</p>
      </div>
      <div class="h-11 w-11 rounded-xl bg-emerald-100 flex items-center justify-center">
        <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
        </svg>
      </div>
    </div>
  </div>

  {{-- Stok Menipis --}}
  <div class="relative overflow-hidden rounded-2xl border {{ $stokMenipis > 0 ? 'border-amber-200' : 'border-slate-200' }} bg-white p-6">
    <div class="absolute top-0 left-0 right-0 h-1 {{ $stokMenipis > 0 ? 'bg-gradient-to-r from-amber-400 to-amber-600' : 'bg-gradient-to-r from-emerald-400 to-emerald-600' }}"></div>
    <div class="flex items-start justify-between">
      <div>
        <p class="text-xs font-medium uppercase tracking-wider {{ $stokMenipis > 0 ? 'text-amber-600' : 'text-slate-500' }}">Stok Menipis</p>
        <p class="mt-2 text-2xl font-bold {{ $stokMenipis > 0 ? 'text-amber-700' : 'text-slate-900' }}">{{ $stokMenipis }} <span class="text-sm font-normal text-slate-400">item</span></p>
      </div>
      <div class="h-11 w-11 rounded-xl {{ $stokMenipis > 0 ? 'bg-amber-100' : 'bg-slate-100' }} flex items-center justify-center">
        <svg class="h-5 w-5 {{ $stokMenipis > 0 ? 'text-amber-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
        </svg>
      </div>
    </div>
  </div>

  {{-- Proyek Aktif --}}
  <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-6">
    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-400 to-blue-600"></div>
    <div class="flex items-start justify-between">
      <div>
        <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Proyek Aktif</p>
        <p class="mt-2 text-2xl font-bold text-slate-900">{{ $proyekAktif }} <span class="text-sm font-normal text-slate-400">proyek</span></p>
      </div>
      <div class="h-11 w-11 rounded-xl bg-blue-50 flex items-center justify-center">
        <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
        </svg>
      </div>
    </div>
  </div>
</div>

{{-- Charts --}}
<div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
  <div class="rounded-2xl border border-slate-200 bg-white p-6">
    <div class="mb-6">
      <h3 class="font-semibold text-slate-900">Penjualan 7 Hari Terakhir</h3>
      <p class="text-xs text-slate-500 mt-0.5">Total: Rp {{ number_format($penjualan7Hari->sum('total'), 0, ',', '.') }}</p>
    </div>
    <div class="h-64">
      <canvas id="penjualanChart"></canvas>
    </div>
  </div>

  <div class="rounded-2xl border border-slate-200 bg-white p-6">
    <div class="mb-6">
      <h3 class="font-semibold text-slate-900">Laba Bersih Bulanan</h3>
      <p class="text-xs text-slate-500 mt-0.5">Total: Rp {{ number_format($labaBulanan->sum('total'), 0, ',', '.') }}</p>
    </div>
    <div class="h-64">
      <canvas id="labaChart"></canvas>
    </div>
  </div>
</div>

{{-- Bottom Section --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
  {{-- Projects Table (3/5) --}}
  <div class="lg:col-span-3 rounded-2xl border border-slate-200 bg-white p-6">
    <div class="flex items-center justify-between mb-5">
      <div>
        <h3 class="font-semibold text-slate-900">Proyek Terbaru</h3>
        <p class="text-xs text-slate-500 mt-0.5">5 proyek paling baru</p>
      </div>
      <a href="{{ route('projects.index') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 transition">Lihat Semua</a>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-slate-100">
            <th class="pb-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Proyek</th>
            <th class="pb-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
            <th class="pb-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider hidden sm:table-cell">Cabang</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          @forelse($proyekTerbaru as $proyek)
            <tr class="hover:bg-slate-50/50 transition">
              <td class="py-3 pr-4">
                <p class="font-medium text-slate-900">{{ $proyek->title }}</p>
                <p class="text-xs text-slate-400">{{ $proyek->code }}</p>
              </td>
              <td class="py-3 pr-4">
                @php
                  $statusMap = [
                    'IN_PROGRESS' => ['bg-amber-50 text-amber-700 ring-amber-600/20', 'Berlangsung'],
                    'DONE' => ['bg-emerald-50 text-emerald-700 ring-emerald-600/20', 'Selesai'],
                    'ALLOCATED' => ['bg-blue-50 text-blue-700 ring-blue-600/20', 'Dialokasikan'],
                    'WAITING_RETURN' => ['bg-orange-50 text-orange-700 ring-orange-600/20', 'Menunggu Return'],
                    'READY_TO_BILL' => ['bg-purple-50 text-purple-700 ring-purple-600/20', 'Siap Tagih'],
                  ];
                  $s = $statusMap[$proyek->status] ?? ['bg-slate-50 text-slate-600 ring-slate-500/20', ucfirst($proyek->status)];
                @endphp
                <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $s[0] }}">{{ $s[1] }}</span>
              </td>
              <td class="py-3 text-sm text-slate-500 hidden sm:table-cell">{{ $proyek->branch->name ?? '-' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="3" class="py-10 text-center text-sm text-slate-400">Belum ada proyek</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Activity Feed (2/5) --}}
  <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-6">
    <div class="mb-5">
      <h3 class="font-semibold text-slate-900">Aktivitas Terakhir</h3>
      <p class="text-xs text-slate-500 mt-0.5">10 aktivitas terbaru</p>
    </div>
    <div class="space-y-0">
      @forelse($aktivitas as $item)
        <div class="flex gap-3 py-3 border-b border-slate-50 last:border-0">
          <div class="relative flex items-center justify-center mt-1">
            @if($item->type === 'penjualan')
              <span class="flex h-2 w-2 rounded-full bg-emerald-500 ring-4 ring-emerald-50"></span>
            @elseif($item->type === 'stok')
              <span class="flex h-2 w-2 rounded-full bg-blue-500 ring-4 ring-blue-50"></span>
            @elseif($item->type === 'project')
              <span class="flex h-2 w-2 rounded-full bg-amber-500 ring-4 ring-amber-50"></span>
            @else
              <span class="flex h-2 w-2 rounded-full bg-slate-400 ring-4 ring-slate-100"></span>
            @endif
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-sm text-slate-700 truncate">{{ $item->detail }}</p>
            <p class="text-xs text-slate-400 mt-0.5">{{ $item->created_at->format('H:i') }}</p>
          </div>
        </div>
      @empty
        <p class="py-10 text-center text-sm text-slate-400">Belum ada aktivitas</p>
      @endforelse
    </div>
  </div>
</div>

{{-- Chart Scripts --}}
@push('scripts')
<script>
  const penjualanCtx = document.getElementById('penjualanChart').getContext('2d');
  const penjualanData = @json($penjualan7Hari);

  new Chart(penjualanCtx, {
    type: 'line',
    data: {
      labels: penjualanData.map(d => {
        const date = new Date(d.tanggal);
        return date.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' });
      }),
      datasets: [{
        label: 'Penjualan',
        data: penjualanData.map(d => d.total),
        borderColor: '#10b981',
        backgroundColor: 'rgba(16,185,129,0.06)',
        fill: true,
        tension: 0.4,
        pointBackgroundColor: '#059669',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#1e293b',
          titleColor: '#e2e8f0',
          bodyColor: '#f8fafc',
          padding: 12,
          cornerRadius: 12,
          callbacks: {
            label: ctx => 'Rp ' + ctx.parsed.y.toLocaleString('id-ID')
          }
        }
      },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11 } } },
        y: {
          beginAtZero: true,
          grid: { color: '#f1f5f9' },
          ticks: {
            color: '#94a3b8',
            font: { size: 11 },
            callback: v => 'Rp ' + (v >= 1000 ? (v/1000).toFixed(0) + 'rb' : v)
          }
        }
      }
    }
  });

  const labaCtx = document.getElementById('labaChart').getContext('2d');
  const labaData = @json($labaBulanan);

  new Chart(labaCtx, {
    type: 'bar',
    data: {
      labels: labaData.map(d => {
        const [year, month] = d.bulan.split('-');
        const date = new Date(year, month - 1);
        return date.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' });
      }),
      datasets: [{
        label: 'Laba',
        data: labaData.map(d => d.total),
        backgroundColor: labaData.map(d => d.total >= 0 ? '#10b981' : '#ef4444'),
        borderRadius: 8,
        maxBarThickness: 48
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#1e293b',
          titleColor: '#e2e8f0',
          bodyColor: '#f8fafc',
          padding: 12,
          cornerRadius: 12,
          callbacks: {
            label: ctx => 'Rp ' + ctx.parsed.y.toLocaleString('id-ID')
          }
        }
      },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11 } } },
        y: {
          beginAtZero: true,
          grid: { color: '#f1f5f9' },
          ticks: {
            color: '#94a3b8',
            font: { size: 11 },
            callback: v => 'Rp ' + (v >= 1000 ? (v/1000).toFixed(0) + 'rb' : v)
          }
        }
      }
    }
  });
</script>
@endpush

{{-- Filter Scripts --}}
@push('scripts')
<script>
  document.getElementById('branchFilter').addEventListener('change', applyFilters);
  document.getElementById('dateRangeFilter').addEventListener('change', applyFilters);

  function applyFilters() {
    const branch = document.getElementById('branchFilter').value;
    const dateRange = document.getElementById('dateRangeFilter').value;
    const params = new URLSearchParams();
    if (branch) params.append('branch_id', branch);
    if (dateRange) params.append('date_range', dateRange);
    window.location.href = '{{ route("owner.home") }}?' + params.toString();
  }
</script>
@endpush

@endsection
