# Owner Dashboard Redesign

## Overview

Redesign the owner dashboard from a simple "WELCOME OWNER" page to a professional, data-rich monitoring dashboard with real-time metrics and performance analysis. Uses green business theme, single-page layout with summary cards + charts + detail sections.

## Goals

1. **Monitoring real-time** — owner can see penjualan, stok, kas, proyek hari ini
2. **Performance analysis** — chart penjualan 7 hari, laba bulanan
3. **All metrics** — finance, stock, projects, recent activity in one view
4. **Default all branches** — with optional filter per cabang
5. **Professional UI** — green business theme, clean cards, responsive

## Design Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Layout approach | Single page dashboard | Owner needs quick glance at all data without navigation |
| Number display | Cards + charts | Simple cards for summary, large charts for detailed analysis |
| Color theme | Green business | Fresh, associated with profit/finance |
| Sidebar | Reorganized by frequency | Dashboard & Laporan on top, Akuntansi separated, Master Data at bottom |
| Additional features | None | Focus on data display, quick actions already in sidebar |

## Layout Structure

```
┌─────────────────────────────────────────────────────┐
│  HEADER: Judul "Dashboard" + Filter Bar             │
│  [Semua Cabang ▼] [Hari Ini ▼] [Refresh]           │
├─────────────────────────────────────────────────────┤
│  SUMMARY CARDS (4 cards, 1 baris)                  │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐      │
│  │Total   │ │Laba    │ │Stok    │ │Proyek  │      │
│  │Penjualan│ │Bersih │ │Menipis │ │Aktif   │      │
│  └────────┘ └────────┘ └────────┘ └────────┘      │
├─────────────────────────────────────────────────────┤
│  CHARTS ROW (2 chart side by side)                  │
│  ┌──────────────────────┐ ┌──────────────────────┐ │
│  │ Penjualan 7 Hari     │ │ Laba Bulanan         │ │
│  │ [line chart]         │ │ [bar chart]          │ │
│  └──────────────────────┘ └──────────────────────┘ │
├─────────────────────────────────────────────────────┤
│  BOTTOM ROW (2 section side by side)                │
│  ┌──────────────────────┐ ┌──────────────────────┐ │
│  │ Proyek Terbaru       │ │ Aktivitas Terakhir   │ │
│  │ [tabel]              │ │ [timeline/log]       │ │
│  └──────────────────────┘ └──────────────────────┘ │
└─────────────────────────────────────────────────────┘
```

## Section Details

### 1. Filter Bar

- **Position:** Top of content area, below header
- **Components:**
  - Dropdown "Semua Cabang" — filter by branch (default: all)
  - Dropdown "Hari Ini" — date range (Hari Ini, 7 Hari, 30 Hari, Bulan Ini)
  - Refresh button — reload data via AJAX
- **Behavior:** Filter changes trigger AJAX reload, no page refresh

### 2. Summary Cards

4 cards in a row (desktop), 2x2 grid (mobile):

| Card | Icon | Data | Sub-info |
|------|------|------|----------|
| Total Penjualan | 💰 (dollar) | Rp X.XXX.XXX | ↑/↓ % dari kemarin |
| Laba Bersih | 📊 (chart) | Rp X.XXX.XXX | ↑/↓ % dari kemarin |
| Stok Menipis | 📦 (box) | X item | Item names (limit 3) |
| Proyek Aktif | 🏗️ (building) | X proyek | Deadline warning |

**Styling:**
- Background: `bg-emerald-50`
- Border: `border-emerald-200`
- Icon: `text-emerald-600`
- Number: `text-emerald-700 text-2xl font-bold`
- Trend up: `text-emerald-600` with ↑
- Trend down: `text-red-500` with ↓

**Interactions:**
- Click card → scroll to related chart/detail section
- Stok Menipis card → yellow highlight if items low
- Proyek card → red highlight if deadline < 7 days

### 3. Charts Row

**Left: Penjualan 7 Hari (Line Chart)**
- Tipe: Line chart (Chart.js)
- Warna: `#10b981` (emerald-500) with gradient fill
- Data: 7 days, grouped by date
- Tooltip: show exact value on hover
- Title: "Penjualan 7 Hari Terakhir"
- Subtitle: Total: Rp XXX.XXX.XXX

**Right: Laba Bulanan (Bar Chart)**
- Tipe: Bar chart (Chart.js)
- Warna: `#10b981` (emerald-500) solid
- Data: 4-6 months, grouped by month
- Tooltip: show exact value on hover
- Title: "Laba Bersih Bulan Ini"
- Subtitle: Total: Rp XXX.XXX.XXX

**Library:** Chart.js via CDN (already in project)

### 4. Bottom Row

**Left: Proyek Terbaru**
- Table with 5 rows
- Columns: Nama Proyek, Status, Progress, Deadline
- Status badges:
  - 🟡 Berlangsung: `bg-yellow-100 text-yellow-800`
  - 🟢 Selesai: `bg-green-100 text-green-800`
  - 🔴 Terlambat: `bg-red-100 text-red-800`
- Progress: mini progress bar
- Link "Lihat Semua" → `/projects`

**Right: Aktivitas Terakhir**
- Timeline/log format
- 10 most recent activities from multiple tables
- Color codes:
  - 🟢 Transaksi (penjualan, pembayaran)
  - 🔵 Stok (penambahan, pengurangan)
  - 🟡 Proyek (update status)
  - 🔴 Alert (stok menipis, deadline)
  - ⚪ Sistem (login, logout)
- Time format: HH:MM
- Auto-scroll if new activity (optional)

### 5. Sidebar Reorganization

**New structure (ordered by usage frequency):**

```
Dashboard                    ← Top (always visible)
Laporan
  ├── Riwayat Transaksi
  ├── Laporan Stok
  └── Laba Rugi              ← Merged from Akuntansi
Akuntansi
  ├── Chart of Accounts
  └── Jurnal Harian
Manajemen Stok
  ├── Penyesuaian Stok
  ├── Transfer Stok
  └── Sisa Potongan
Pembelian Langsung
Master Data
  ├── Buat Produk
  ├── Buat Supplier
  ├── Import Data Produk
  └── Buat Cabang Baru
─────────────────────────
👤 Owner Name
[Logout]
```

**Changes from current:**
1. Laba Rugi moved to Laporan section
2. User info added at bottom
3. Order optimized by frequency

## Backend Changes

### Branch Filter Logic

All queries in OwnerDashboardController support optional branch filtering:

```php
public function index(Request $request)
{
    $branchId = $request->input('branch_id'); // null = all branches
    
    // Apply branch filter to all queries
    $query = Penjualan::query();
    if ($branchId) {
        $query->where('branch_id', $branchId);
    }
    
    $totalPenjualan = $query->whereDate('created_at', today())->sum('total');
    // ... same pattern for all other queries
}
```

### New Controller: OwnerDashboardController

```php
<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use App\Models\Product;
use App\Models\Project;
use Illuminate\Http\Request;

class OwnerDashboardController extends Controller
{
    public function index()
    {
        // 1. Summary cards
        $totalPenjualan = Penjualan::whereDate('created_at', today())->sum('total');
        $labaBersih = Penjualan::whereDate('created_at', today())->sum('laba');
        $stokMenipis = Product::where('stock', '<', 10)->count();
        $proyekAktif = Project::whereIn('status', ['berlangsung', 'terlambat'])->count();

        // 2. Chart penjualan 7 hari
        $penjualan7Hari = Penjualan::where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as tanggal, SUM(total) as total')
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        // 3. Chart laba bulanan
        $labaBulanan = Penjualan::where('created_at', '>=', now()->subMonths(6))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as bulan, SUM(laba) as total")
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get();

        // 4. Proyek terbaru (limit 5)
        $proyekTerbaru = Project::latest()->limit(5)->get();

        // 5. Aktivitas terakhir
        $aktivitas = $this->getRecentActivity();

        return view('owner.index', compact(
            'totalPenjualan', 'labaBersih', 'stokMenipis', 'proyekAktif',
            'penjualan7Hari', 'labaBulanan', 'proyekTerbaru', 'aktivitas'
        ));
    }

    private function getRecentActivity()
    {
        $penjualan = Penjualan::select('id', 'created_at', 'total as detail', 'penjualan as type')
            ->latest()->limit(5)->get();
        
        $stokAdjust = StockAdjustment::select('id', 'created_at', 'reason as detail', 'stok_adjust as type')
            ->latest()->limit(5)->get();
        
        $projects = Project::select('id', 'updated_at as created_at', 'name as detail', 'project as type')
            ->latest()->limit(5)->get();
        
        return $penjualan->merge($stokAdjust, $projects)
            ->sortByDesc('created_at')
            ->take(10)
            ->values();
    }
}
```

### Route Update

```php
// routes/web.php
Route::middleware(['auth','role:1'])->get('/owner', [OwnerDashboardController::class, 'index'])->name('owner.home');
```

## Files to Create/Modify

| File | Action | Description |
|------|--------|-------------|
| `app/Http/Controllers/OwnerDashboardController.php` | CREATE | New controller with dashboard logic |
| `routes/web.php` | MODIFY | Update owner route to use new controller |
| `resources/views/owner/index.blade.php` | REWRITE | Complete dashboard view |
| `resources/views/layouts/dashboard.blade.php` | MODIFY | Reorganize sidebar menu |

## Responsive Design

| Breakpoint | Layout |
|------------|--------|
| Desktop (≥1024px) | Full layout as designed |
| Tablet (768-1023px) | Cards 2x2, charts stack, bottom row stack |
| Mobile (<768px) | Everything stack, cards 1 column, charts full width |

## Testing

1. **Visual test:** Dashboard loads correctly with all sections
2. **Data test:** Summary cards show correct values
3. **Chart test:** Charts render with real data
4. **Filter test:** Branch filter works correctly
5. **Responsive test:** Layout works on mobile/tablet/desktop
6. **Sidebar test:** Menu items work correctly after reorganization

## Dependencies

- Chart.js (CDN) — already available
- Tailwind CSS (CDN) — already available
- No new npm packages required
