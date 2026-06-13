# Owner Dashboard Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign owner dashboard from simple "WELCOME OWNER" to professional monitoring + analysis dashboard with green business theme.

**Architecture:** Single-page dashboard with summary cards, charts (Chart.js), project table, and activity log. Uses existing `layouts/dashboard.blade.php` sidebar layout. New controller `OwnerDashboardController` fetches all data.

**Tech Stack:** Laravel, Tailwind CSS (CDN), Chart.js (CDN), MariaDB

---

## File Structure

| File | Action | Description |
|------|--------|-------------|
| `app/Http/Controllers/OwnerDashboardController.php` | CREATE | Dashboard controller with data fetching |
| `routes/web.php` | MODIFY | Update owner route to use controller |
| `resources/views/owner/index.blade.php` | REWRITE | Complete dashboard view |
| `resources/views/layouts/dashboard.blade.php` | MODIFY | Reorganize sidebar menu |

---

### Task 1: Create OwnerDashboardController

**Files:**
- Create: `app/Http/Controllers/OwnerDashboardController.php`

- [ ] **Step 1: Create the controller file**

```php
<?php

namespace App\Http\Controllers;

use App\Models\PosSale;
use App\Models\Product;
use App\Models\Project;
use App\Models\StockMove;
use App\Models\Branch;
use Illuminate\Http\Request;

class OwnerDashboardController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $request->input('branch_id');
        $dateRange = $request->input('date_range', 'today');

        // Summary cards
        $totalPenjualan = $this->getTotalPenjualan($branchId, $dateRange);
        $labaBersih = $this->getLabaBersih($branchId, $dateRange);
        $stokMenipis = Product::where('is_active', true)
            ->whereHas('category', function ($q) {
                $q->where('track_stock', true);
            })
            ->where('id', function ($q) {
                $q->select('product_id')
                    ->from('stock_quants')
                    ->whereColumn('stock_quants.product_id', 'products.id')
                    ->havingRaw('SUM(qty) < 10');
            })
            ->count();
        $proyekAktif = Project::whereIn('status', ['berlangsung', 'terlambat'])->count();

        // Chart data
        $penjualan7Hari = $this->getPenjualan7Hari($branchId);
        $labaBulanan = $this->getLabaBulanan($branchId);

        // Detail data
        $proyekTerbaru = Project::with(['branch', 'customer'])
            ->latest()
            ->limit(5)
            ->get();
        $aktivitas = $this->getRecentActivity();

        // Branches for filter
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('owner.index', compact(
            'totalPenjualan', 'labaBersih', 'stokMenipis', 'proyekAktif',
            'penjualan7Hari', 'labaBulanan', 'proyekTerbaru', 'aktivitas',
            'branches', 'branchId', 'dateRange'
        ));
    }

    private function getTotalPenjualan($branchId, $dateRange)
    {
        $query = PosSale::where('status', 'completed');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($dateRange === 'today') {
            $query->whereDate('sale_datetime', today());
        } elseif ($dateRange === '7days') {
            $query->where('sale_datetime', '>=', now()->subDays(7));
        } elseif ($dateRange === '30days') {
            $query->where('sale_datetime', '>=', now()->subDays(30));
        } elseif ($dateRange === 'month') {
            $query->whereMonth('sale_datetime', now()->month)
                  ->whereYear('sale_datetime', now()->year);
        }

        return $query->sum('total');
    }

    private function getLabaBersih($branchId, $dateRange)
    {
        // Laba = Total - COGS (simplified: using total as profit for now)
        // In real app, this would calculate from journal entries
        $query = PosSale::where('status', 'completed');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($dateRange === 'today') {
            $query->whereDate('sale_datetime', today());
        } elseif ($dateRange === '7days') {
            $query->where('sale_datetime', '>=', now()->subDays(7));
        } elseif ($dateRange === '30days') {
            $query->where('sale_datetime', '>=', now()->subDays(30));
        } elseif ($dateRange === 'month') {
            $query->whereMonth('sale_datetime', now()->month)
                  ->whereYear('sale_datetime', now()->year);
        }

        // Simplified: 30% margin assumption
        return $query->sum('total') * 0.3;
    }

    private function getPenjualan7Hari($branchId)
    {
        $query = PosSale::where('status', 'completed')
            ->where('sale_datetime', '>=', now()->subDays(7));

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->selectRaw('DATE(sale_datetime) as tanggal, SUM(total) as total')
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();
    }

    private function getLabaBulanan($branchId)
    {
        $query = PosSale::where('status', 'completed')
            ->where('sale_datetime', '>=', now()->subMonths(6));

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->selectRaw("DATE_FORMAT(sale_datetime, '%Y-%m') as bulan, SUM(total) * 0.3 as total")
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get();
    }

    private function getRecentActivity()
    {
        $penjualan = PosSale::select('id', 'sale_datetime as created_at', 'total as detail')
            ->where('status', 'completed')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $item->type = 'penjualan';
                return $item;
            });

        $stockMoves = StockMove::select('id', 'created_at', 'qty as detail')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $item->type = 'stok';
                return $item;
            });

        $projects = Project::select('id', 'updated_at as created_at', 'title as detail')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $item->type = 'project';
                return $item;
            });

        return $penjualan->merge($stockMoves, $projects)
            ->sortByDesc('created_at')
            ->take(10)
            ->values();
    }
}
```

- [ ] **Step 2: Verify controller syntax**

Run: `php -l app/Http/Controllers/OwnerDashboardController.php`
Expected: No syntax errors

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/OwnerDashboardController.php
git commit -m "feat: add OwnerDashboardController with data fetching"
```

---

### Task 2: Update Route

**Files:**
- Modify: `routes/web.php:21`

- [ ] **Step 1: Update owner route**

Change line 21 from:
```php
Route::middleware(['auth','role:1'])->get('/owner', fn() => view('owner.index'))->name('owner.home');
```

To:
```php
Route::middleware(['auth','role:1'])->get('/owner', [OwnerDashboardController::class, 'index'])->name('owner.home');
```

- [ ] **Step 2: Add import at top of file**

Add after line 14 (other use statements):
```php
use App\Http\Controllers\OwnerDashboardController;
```

- [ ] **Step 3: Verify route works**

Run: `php artisan route:list --path=owner`
Expected: Route shows OwnerDashboardController@index

- [ ] **Step 4: Commit**

```bash
git add routes/web.php
git commit -m "feat: update owner route to use OwnerDashboardController"
```

---

### Task 3: Create Dashboard View - Header & Filter

**Files:**
- Modify: `resources/views/owner/index.blade.php`

- [ ] **Step 1: Write the dashboard view header and filter bar**

```blade
@extends('layouts.dashboard', ['title' => 'Dashboard'])
@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

{{-- Filter Bar --}}
<div class="mb-6 flex flex-wrap items-center gap-4">
    <h2 class="text-lg font-semibold text-slate-800">Dashboard Overview</h2>
    
    <div class="flex items-center gap-3 ml-auto">
        {{-- Branch Filter --}}
        <select id="branchFilter" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            <option value="">Semua Cabang</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                    {{ $branch->name }}
                </option>
            @endforeach
        </select>

        {{-- Date Range Filter --}}
        <select id="dateRangeFilter" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            <option value="today" {{ $dateRange == 'today' ? 'selected' : '' }}>Hari Ini</option>
            <option value="7days" {{ $dateRange == '7days' ? 'selected' : '' }}>7 Hari</option>
            <option value="30days" {{ $dateRange == '30days' ? 'selected' : '' }}>30 Hari</option>
            <option value="month" {{ $dateRange == 'month' ? 'selected' : '' }}>Bulan Ini</option>
        </select>

        {{-- Refresh Button --}}
        <button onclick="location.reload()" class="rounded-xl border border-slate-200 bg-white p-2 hover:bg-slate-50">
            <svg class="h-5 w-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </button>
    </div>
</div>

{{-- Content will be added in next tasks --}}
@endsection
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/owner/index.blade.php
git commit -m "feat: add dashboard header and filter bar"
```

---

### Task 4: Create Dashboard View - Summary Cards

**Files:**
- Modify: `resources/views/owner/index.blade.php`

- [ ] **Step 1: Add summary cards section after filter bar**

Add before `@endsection`:
```blade
{{-- Summary Cards --}}
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    {{-- Total Penjualan --}}
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
        <div class="flex items-center gap-3">
            <div class="rounded-xl bg-emerald-100 p-3">
                <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm text-emerald-600">Total Penjualan</p>
                <p class="text-2xl font-bold text-emerald-700">Rp {{ number_format($totalPenjualan, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    {{-- Laba Bersih --}}
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
        <div class="flex items-center gap-3">
            <div class="rounded-xl bg-emerald-100 p-3">
                <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm text-emerald-600">Laba Bersih</p>
                <p class="text-2xl font-bold text-emerald-700">Rp {{ number_format($labaBersih, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    {{-- Stok Menipis --}}
    <div class="rounded-2xl border {{ $stokMenipis > 0 ? 'border-amber-200 bg-amber-50' : 'border-emerald-200 bg-emerald-50' }} p-5">
        <div class="flex items-center gap-3">
            <div class="rounded-xl {{ $stokMenipis > 0 ? 'bg-amber-100' : 'bg-emerald-100' }} p-3">
                <svg class="h-6 w-6 {{ $stokMenipis > 0 ? 'text-amber-600' : 'text-emerald-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <div>
                <p class="text-sm {{ $stokMenipis > 0 ? 'text-amber-600' : 'text-emerald-600' }}">Stok Menipis</p>
                <p class="text-2xl font-bold {{ $stokMenipis > 0 ? 'text-amber-700' : 'text-emerald-700' }}">{{ $stokMenipis }} item</p>
            </div>
        </div>
    </div>

    {{-- Proyek Aktif --}}
    <div class="rounded-2xl border {{ $proyekAktif > 0 ? 'border-blue-200 bg-blue-50' : 'border-emerald-200 bg-emerald-50' }} p-5">
        <div class="flex items-center gap-3">
            <div class="rounded-xl {{ $proyekAktif > 0 ? 'bg-blue-100' : 'bg-emerald-100' }} p-3">
                <svg class="h-6 w-6 {{ $proyekAktif > 0 ? 'text-blue-600' : 'text-emerald-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <div>
                <p class="text-sm {{ $proyekAktif > 0 ? 'text-blue-600' : 'text-emerald-600' }}">Proyek Aktif</p>
                <p class="text-2xl font-bold {{ $proyekAktif > 0 ? 'text-blue-700' : 'text-emerald-700' }}">{{ $proyekAktif }} proyek</p>
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/owner/index.blade.php
git commit -m "feat: add summary cards to dashboard"
```

---

### Task 5: Create Dashboard View - Charts

**Files:**
- Modify: `resources/views/owner/index.blade.php`

- [ ] **Step 1: Add charts section after summary cards**

Add before `@endsection`:
```blade
{{-- Charts Row --}}
<div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
    {{-- Penjualan 7 Hari --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5">
        <div class="mb-4">
            <h3 class="font-semibold text-slate-800">Penjualan 7 Hari Terakhir</h3>
            <p class="text-sm text-slate-500">Total: Rp {{ number_format($penjualan7Hari->sum('total'), 0, ',', '.') }}</p>
        </div>
        <canvas id="penjualanChart" height="200"></canvas>
    </div>

    {{-- Laba Bulanan --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5">
        <div class="mb-4">
            <h3 class="font-semibold text-slate-800">Laba Bersih Bulanan</h3>
            <p class="text-sm text-slate-500">Total: Rp {{ number_format($labaBulanan->sum('total'), 0, ',', '.') }}</p>
        </div>
        <canvas id="labaChart" height="200"></canvas>
    </div>
</div>

{{-- Chart.js Scripts --}}
@push('scripts')
<script>
    // Penjualan 7 Hari Chart
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
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#10b981',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => 'Rp ' + ctx.parsed.y.toLocaleString('id-ID')
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => 'Rp ' + value.toLocaleString('id-ID')
                    }
                }
            }
        }
    });

    // Laba Bulanan Chart
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
                backgroundColor: '#10b981',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => 'Rp ' + ctx.parsed.y.toLocaleString('id-ID')
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => 'Rp ' + value.toLocaleString('id-ID')
                    }
                }
            }
        }
    });
</script>
@endpush
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/owner/index.blade.php
git commit -m "feat: add charts to dashboard"
```

---

### Task 6: Create Dashboard View - Bottom Row

**Files:**
- Modify: `resources/views/owner/index.blade.php`

- [ ] **Step 1: Add bottom row section after charts**

Add before `@endsection`:
```blade
{{-- Bottom Row --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    {{-- Proyek Terbaru --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">Proyek Terbaru</h3>
            <a href="{{ route('projects.index') }}" class="text-sm text-emerald-600 hover:text-emerald-700">Lihat Semua →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-slate-500">
                        <th class="pb-3 font-medium">Nama Proyek</th>
                        <th class="pb-3 font-medium">Status</th>
                        <th class="pb-3 font-medium">Cabang</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($proyekTerbaru as $proyek)
                        <tr class="border-b border-slate-100">
                            <td class="py-3">
                                <p class="font-medium text-slate-800">{{ $proyek->title }}</p>
                                <p class="text-xs text-slate-500">{{ $proyek->code }}</p>
                            </td>
                            <td class="py-3">
                                @if($proyek->status === 'berlangsung')
                                    <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">
                                        Berlangsung
                                    </span>
                                @elseif($proyek->status === 'selesai')
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                        Selesai
                                    </span>
                                @elseif($proyek->status === 'terlambat')
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                        Terlambat
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-800">
                                        {{ ucfirst($proyek->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 text-slate-600">{{ $proyek->branch->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-6 text-center text-slate-500">Belum ada proyek</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Aktivitas Terakhir --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5">
        <div class="mb-4">
            <h3 class="font-semibold text-slate-800">Aktivitas Terakhir</h3>
        </div>
        <div class="space-y-3">
            @forelse($aktivitas as $item)
                <div class="flex items-start gap-3">
                    @if($item->type === 'penjualan')
                        <div class="mt-1 h-2 w-2 rounded-full bg-emerald-500"></div>
                    @elseif($item->type === 'stok')
                        <div class="mt-1 h-2 w-2 rounded-full bg-blue-500"></div>
                    @elseif($item->type === 'project')
                        <div class="mt-1 h-2 w-2 rounded-full bg-yellow-500"></div>
                    @else
                        <div class="mt-1 h-2 w-2 rounded-full bg-slate-400"></div>
                    @endif
                    <div class="flex-1">
                        <p class="text-sm text-slate-800">{{ $item->detail }}</p>
                        <p class="text-xs text-slate-500">{{ $item->created_at->format('H:i') }}</p>
                    </div>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-slate-500">Belum ada aktivitas</p>
            @endforelse
        </div>
    </div>
</div>
```

- [ ] **Step 2: Add filter JavaScript at the end**

Add before `@endsection`:
```blade
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
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/owner/index.blade.php
git commit -m "feat: add bottom row and filter scripts to dashboard"
```

---

### Task 7: Reorganize Sidebar Menu

**Files:**
- Modify: `resources/views/layouts/dashboard.blade.php:50-186`

- [ ] **Step 1: Replace sidebar navigation section**

Replace the navigation section (lines 50-186) with:

```blade
<!-- ### NAVIGASI BARU DIMULAI DI SINI ### -->
<nav class="flex-1 overflow-y-auto p-4 space-y-2">
    <!-- Dashboard -->
    <a href="{{ route('owner.home') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-xl border border-transparent hover:border-slate-200 hover:bg-slate-100
              dark:hover:border-[rgba(148,163,184,.12)] dark:hover:bg-white/5">
        <svg width="20" height="20" class="text-slate-500 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        <span class="font-medium">Dashboard</span>
    </a>

    <!-- Laporan -->
    <div>
        <button type="button"
            class="w-full flex items-center justify-between px-3 py-2 rounded-xl hover:bg-slate-100
                   dark:hover:bg-white/5">
            <span class="flex items-center gap-3">
                <svg width="20" height="20" class="text-slate-500 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 00-4-4H3V9a4 4 0 014-4h2a4 4 0 014 4v2m-6 4h6m-6 2h6m0 0v2a4 4 0 004 4h3a4 4 0 004-4v-2m-4-4v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2"/>
                </svg>
                <span class="font-medium">Laporan</span>
            </span>
            <svg width="16" height="16" class="text-slate-400 transition-transform rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
        <div class="space-y-1 mt-2 ml-2 hidden">
            <a href="{{ route('reports.transactions.index') }}"
               class="block px-3 py-2 rounded-lg text-sm hover:bg-slate-100 border border-transparent hover:border-slate-200
                      dark:hover:bg-white/5 dark:hover:border-[rgba(148,163,184,.12)]">
                Riwayat Transaksi
            </a>
            <a href="{{ route('reports.stock.index') }}"
               class="block px-3 py-2 rounded-lg text-sm hover:bg-slate-100 border border-transparent hover:border-slate-200
                      dark:hover:bg-white/5 dark:hover:border-[rgba(148,163,184,.12)]">
                Laporan Stok
            </a>
            <a href="{{ route('reports.income_statement') }}"
               class="block px-3 py-2 rounded-lg text-sm hover:bg-slate-100 border border-transparent hover:border-slate-200
                      dark:hover:bg-white/5 dark:hover:border-[rgba(148,163,184,.12)]">
                Laba Rugi
            </a>
        </div>
    </div>

    <!-- Akuntansi -->
    <div>
        <button type="button"
            class="w-full flex items-center justify-between px-3 py-2 rounded-xl hover:bg-slate-100
                   dark:hover:bg-white/5">
            <span class="flex items-center gap-3">
                <svg width="20" height="20" class="text-slate-500 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                <span class="font-medium">Akuntansi</span>
            </span>
            <svg width="16" height="16" class="text-slate-400 transition-transform rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
        <div class="space-y-1 mt-2 ml-2 hidden">
            <a href="{{ route('accounting.chart') }}"
               class="block px-3 py-2 rounded-lg text-sm hover:bg-slate-100 border border-transparent hover:border-slate-200
                      dark:hover:bg-white/5 dark:hover:border-[rgba(148,163,184,.12)]">
                Chart of Accounts
            </a>
            <a href="{{ route('accounting.journal') }}"
               class="block px-3 py-2 rounded-lg text-sm hover:bg-slate-100 border border-transparent hover:border-slate-200
                      dark:hover:bg-white/5 dark:hover:border-[rgba(148,163,184,.12)]">
                Jurnal Harian
            </a>
        </div>
    </div>

    <!-- Manajemen Stok -->
    <div>
        <button type="button"
            class="w-full flex items-center justify-between px-3 py-2 rounded-xl hover:bg-slate-100
                   dark:hover:bg-white/5">
            <span class="flex items-center gap-3">
                <svg width="20" height="20" class="text-slate-500 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
                <span class="font-medium">Manajemen Stok</span>
            </span>
            <svg width="16" height="16" class="text-slate-400 transition-transform rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
        <div class="space-y-1 mt-2 ml-2 hidden">
            <a href="{{ route('stock.adjust.create') }}"
               class="block px-3 py-2 rounded-lg text-sm hover:bg-slate-100 border border-transparent hover:border-slate-200
                      dark:hover:bg-white/5 dark:hover:border-[rgba(148,163,184,.12)]">
                Penyesuaian Stok
            </a>
            <a href="{{ route('stock.transfer.create') }}"
               class="block px-3 py-2 rounded-lg text-sm hover:bg-slate-100 border border-transparent hover:border-slate-200
                      dark:hover:bg-white/5 dark:hover:border-[rgba(148,163,184,.12)]">
                Transfer Stok
            </a>
            <a href="{{ route('leftovers.index') }}"
               class="block px-3 py-2 rounded-lg text-sm hover:bg-slate-100 border border-transparent hover:border-slate-200
                      dark:hover:bg-white/5 dark:hover:border-[rgba(148,163,184,.12)]">
                Sisa Potongan
            </a>
        </div>
    </div>

    <!-- Pembelian -->
    <a href="{{ route('purchase.direct.create') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-xl border border-transparent hover:border-slate-200 hover:bg-slate-100
              dark:hover:border-[rgba(148,163,184,.12)] dark:hover:bg-white/5">
        <svg width="20" height="20" class="text-slate-500 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
        </svg>
        <span class="font-medium">Pembelian Langsung</span>
    </a>

    <!-- Master Data -->
    <div>
        <button type="button"
            class="w-full flex items-center justify-between px-3 py-2 rounded-xl hover:bg-slate-100
                   dark:hover:bg-white/5">
            <span class="flex items-center gap-3">
                <svg width="20" height="20" class="text-slate-500 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4"/>
                </svg>
                <span class="font-medium">Master Data</span>
            </span>
            <svg width="16" height="16" class="text-slate-400 transition-transform rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
        <div class="space-y-1 mt-2 ml-2 hidden">
            <a href="{{ route('products.create') }}"
               class="block px-3 py-2 rounded-lg text-sm hover:bg-slate-100 border border-transparent hover:border-slate-200
                      dark:hover:bg-white/5 dark:hover:border-[rgba(148,163,184,.12)]">
                Buat Produk
            </a>
            <a href="{{ route('suppliers.create') }}"
               class="block px-3 py-2 rounded-lg text-sm hover:bg-slate-100 border border-transparent hover:border-slate-200
                      dark:hover:bg-white/5 dark:hover:border-[rgba(148,163,184,.12)]">
                Buat Supplier
            </a>
            <a href="{{ route('admin.products.import.form') }}"
               class="block px-3 py-2 rounded-lg text-sm hover:bg-slate-100 border border-transparent hover:border-slate-200
                      dark:hover:bg-white/5 dark:hover:border-[rgba(148,163,184,.12)]">
                Import Data Produk
            </a>
            <a href="{{ route('admin.branches.create') }}"
               class="block px-3 py-2 rounded-lg text-sm hover:bg-slate-100 border border-transparent hover:border-slate-200
                      dark:hover:bg-white/5 dark:hover:border-[rgba(148,163,184,.12)]">
                Buat Cabang Baru
            </a>
        </div>
    </div>
</nav>
<!-- ### AKHIR DARI NAVIGASI BARU ### -->
```

- [ ] **Step 2: Add user info at bottom of sidebar**

Replace the logout section (lines 188-200) with:

```blade
<div class="p-4 border-t border-slate-200 dark:border-[rgba(148,163,184,.12)]">
    <div class="flex items-center gap-3 px-3 py-2 mb-2">
        <img class="h-9 w-9 rounded-xl border border-slate-200 dark:border-[rgba(148,163,184,.12)]"
             src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=dbeafe&color=1e3a8a" alt="User avatar">
        <div>
            <p class="text-sm font-medium">{{ Auth::user()->name }}</p>
            <p class="text-xs text-slate-500">Owner</p>
        </div>
    </div>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <a href="{{ route('logout') }}"
           onclick="event.preventDefault(); this.closest('form').submit();"
           class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-white/5">
            <svg width="20" height="20" class="text-slate-500 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            <span>Logout</span>
        </a>
    </form>
</div>
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/layouts/dashboard.blade.php
git commit -m "feat: reorganize sidebar menu by usage frequency"
```

---

### Task 8: Final Verification

- [ ] **Step 1: Test dashboard loads**

Run: `php artisan route:list --path=owner`
Expected: Route shows OwnerDashboardController@index

- [ ] **Step 2: Test view renders**

Run: `php artisan view:clear && php artisan view:cache`
Expected: No errors

- [ ] **Step 3: Run linting**

Run: `php -l app/Http/Controllers/OwnerDashboardController.php && php -l routes/web.php`
Expected: No syntax errors

- [ ] **Step 4: Final commit**

```bash
git add -A
git commit -m "feat: complete owner dashboard redesign"
```
