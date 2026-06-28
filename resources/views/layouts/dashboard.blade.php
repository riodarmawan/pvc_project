<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ isset($title) ? $title . ' — ' : '' }}PVC Dashboard</title>

  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Inter','ui-sans-serif','system-ui'] },
          colors: {
            brand:  '#059669',
            accent: '#10b981',
            surface:'#f8fafc',
          },
          boxShadow: {
            card: '0 1px 3px 0 rgb(0 0 0 / 0.04), 0 1px 2px -1px rgb(0 0 0 / 0.04)',
          }
        }
      }
    }
  </script>
  @stack('head')
</head>
<body class="bg-slate-50 text-slate-700 antialiased font-sans">

<div class="min-h-screen flex">

  {{-- ===== SIDEBAR ===== --}}
  <aside id="sidebar"
    class="fixed z-40 inset-y-0 left-0 w-64 -translate-x-full md:translate-x-0 md:static md:flex-shrink-0
           bg-white border-r border-slate-200 flex flex-col transition-transform">

    {{-- Logo --}}
    <div class="h-16 flex items-center gap-3 px-5 border-b border-slate-100 flex-shrink-0">
      <div class="h-9 w-9 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 grid place-items-center shadow-sm shadow-emerald-200">
        <svg viewBox="0 0 20 20" class="h-5 w-5 text-white" fill="currentColor"><path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/></svg>
      </div>
      <span class="font-bold text-slate-900 tracking-tight">PVC Panel</span>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto p-3 space-y-1" id="sidebar-nav">
      @php
        $isActive = fn($route) => request()->routeIs($route) ? 'bg-emerald-50 text-emerald-700 border-emerald-200 font-semibold' : 'text-slate-600 hover:bg-slate-50 border-transparent';
        $isParent = fn(...$routes) => collect($routes)->contains(fn($r) => request()->routeIs($r));
      @endphp

      {{-- Dashboard --}}
      <a href="{{ route('owner.home') }}"
         class="flex items-center gap-3 px-3 py-2.5 rounded-xl border text-sm transition {{ $isActive('owner.home') }}">
        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        Dashboard
      </a>

      {{-- Laporan --}}
      <div>
        <button type="button" data-submenu="laporan"
          class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-sm transition
                 {{ $isParent('reports.*') ? 'text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
          <span class="flex items-center gap-3">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            Laporan
          </span>
          <svg class="h-4 w-4 text-slate-400 transition-transform duration-200 {{ $isParent('reports.*') ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div class="ml-3 mt-1 space-y-1 {{ $isParent('reports.*') ? '' : 'hidden' }}" data-submenu-target="laporan">
          <a href="{{ route('reports.transactions.index') }}" class="block px-3 py-2 rounded-lg text-sm {{ $isActive('reports.transactions.index') }}">Riwayat Transaksi</a>
          <a href="{{ route('reports.stock.index') }}" class="block px-3 py-2 rounded-lg text-sm {{ $isActive('reports.stock.index') }}">Laporan Stok</a>
          <a href="{{ route('reports.income_statement') }}" class="block px-3 py-2 rounded-lg text-sm {{ $isActive('reports.income_statement') }}">Laba Rugi</a>
          <a href="{{ route('reports.cash_reconciliation') }}" class="block px-3 py-2 rounded-lg text-sm {{ $isActive('reports.cash_reconciliation') }}">Rekonsiliasi Kas</a>
          <a href="{{ route('reports.audit-log') }}" class="block px-3 py-2 rounded-lg text-sm {{ $isActive('reports.audit-log') }}">Log Audit</a>
        </div>
      </div>

      {{-- Akuntansi --}}
      <div>
        <button type="button" data-submenu="akuntansi"
          class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-sm transition
                 {{ $isParent('accounting.*') ? 'text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
          <span class="flex items-center gap-3">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            Akuntansi
          </span>
          <svg class="h-4 w-4 text-slate-400 transition-transform duration-200 {{ $isParent('accounting.*') ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div class="ml-3 mt-1 space-y-1 {{ $isParent('accounting.*') ? '' : 'hidden' }}" data-submenu-target="akuntansi">
          <a href="{{ route('accounting.chart') }}" class="block px-3 py-2 rounded-lg text-sm {{ $isActive('accounting.chart') }}">Chart of Accounts</a>
          <a href="{{ route('accounting.journal') }}" class="block px-3 py-2 rounded-lg text-sm {{ $isActive('accounting.journal') }}">Jurnal Harian</a>
        </div>
      </div>

      {{-- Manajemen Stok --}}
      <div>
        <button type="button" data-submenu="stok"
          class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-sm transition
                 {{ $isParent('stock.*', 'leftovers.*') ? 'text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
          <span class="flex items-center gap-3">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            Manajemen Stok
          </span>
          <svg class="h-4 w-4 text-slate-400 transition-transform duration-200 {{ $isParent('stock.*', 'leftovers.*') ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div class="ml-3 mt-1 space-y-1 {{ $isParent('stock.*', 'leftovers.*') ? '' : 'hidden' }}" data-submenu-target="stok">
          <a href="{{ route('stock.adjust.create') }}" class="block px-3 py-2 rounded-lg text-sm {{ $isActive('stock.adjust.create') }}">Penyesuaian Stok</a>
          <a href="{{ route('stock.transfer.create') }}" class="block px-3 py-2 rounded-lg text-sm {{ $isActive('stock.transfer.create') }}">Transfer Stok</a>
          <a href="{{ route('leftovers.index') }}" class="block px-3 py-2 rounded-lg text-sm {{ $isActive('leftovers.index') }}">Sisa Potongan</a>
        </div>
      </div>

      {{-- Pembelian Langsung --}}
      <a href="{{ route('purchase.direct.create') }}"
         class="flex items-center gap-3 px-3 py-2.5 rounded-xl border text-sm transition {{ $isActive('purchase.direct.create') }}">
        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
        </svg>
        Pembelian Langsung
      </a>

      {{-- Master Data --}}
      <div>
        <button type="button" data-submenu="master"
          class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-sm transition
                 {{ $isParent('admin.*', 'suppliers.*') ? 'text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
          <span class="flex items-center gap-3">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4"/>
            </svg>
            Master Data
          </span>
          <svg class="h-4 w-4 text-slate-400 transition-transform duration-200 {{ $isParent('admin.*', 'suppliers.*') ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div class="ml-3 mt-1 space-y-1 {{ $isParent('admin.*', 'suppliers.*') ? '' : 'hidden' }}" data-submenu-target="master">
          <a href="{{ route('suppliers.create') }}" class="block px-3 py-2 rounded-lg text-sm {{ $isActive('suppliers.create') }}">Buat Supplier</a>
          <a href="{{ route('admin.products.import.form') }}" class="block px-3 py-2 rounded-lg text-sm {{ $isActive('admin.products.import.form') }}">Import Data Produk</a>
          <a href="{{ route('admin.branches.create') }}" class="block px-3 py-2 rounded-lg text-sm {{ $isActive('admin.branches.create') }}">Buat Cabang Baru</a>
        </div>
      </div>
    </nav>

    {{-- User Footer --}}
    <div class="border-t border-slate-100 p-3 flex-shrink-0">
      <div class="relative" id="sidebar-user-dropdown">
        <button onclick="toggleSidebarUserDropdown()"
          class="w-full flex items-center gap-3 px-2 py-2 rounded-xl hover:bg-slate-50 transition text-left">
          <div class="h-8 w-8 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
            <span class="text-xs font-bold text-emerald-700">{{ strtoupper(substr(Auth::user()->full_name ?? 'U', 0, 1)) }}</span>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-slate-900 truncate">{{ Auth::user()->full_name }}</p>
            <p class="text-xs text-slate-500">Owner</p>
          </div>
          <svg class="h-4 w-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>

        <div id="sidebar-user-menu" class="hidden absolute bottom-full left-0 right-0 mb-1 bg-white border border-slate-200 rounded-xl shadow-lg p-1 z-50">
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
              class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-rose-600 hover:bg-rose-50 transition">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
              </svg>
              Logout
            </button>
          </form>
        </div>
      </div>
    </div>
  </aside>

  {{-- ===== MAIN ===== --}}
  <div class="flex-1 min-w-0 flex flex-col">

    {{-- Header --}}
    <header class="sticky top-0 z-30 bg-white/80 backdrop-blur border-b border-slate-200">
      <div class="h-16 px-4 md:px-6 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
          <button id="menuBtn" type="button"
            class="p-2 rounded-lg border border-slate-200 hover:bg-slate-50 md:hidden shrink-0">
            <svg class="h-5 w-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>
          <div class="hidden sm:block h-5 w-px bg-slate-200 shrink-0"></div>
          <h1 class="text-lg font-semibold text-slate-900 truncate">{{ $title ?? 'Dashboard' }}</h1>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          {{-- Quick link ke POS --}}
          <a href="{{ url('/kasir/pos') }}"
             class="hidden sm:inline-flex items-center gap-2 h-9 px-3 rounded-lg text-sm font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3z"/>
            </svg>
            POS
          </a>

          {{-- User Dropdown (top right) --}}
          <div class="relative" id="top-user-dropdown">
            <button onclick="toggleTopUserDropdown()"
              class="flex items-center gap-2 px-2 py-1.5 rounded-xl hover:bg-slate-100 transition">
              <div class="h-8 w-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                <span class="text-xs font-bold text-emerald-700">{{ strtoupper(substr(Auth::user()->full_name ?? 'U', 0, 1)) }}</span>
              </div>
              <span class="hidden sm:block text-sm font-medium text-slate-700">{{ Auth::user()->full_name }}</span>
              <svg class="h-4 w-4 text-slate-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>

            <div id="top-user-menu" class="hidden absolute right-0 top-full mt-1 w-56 bg-white border border-slate-200 rounded-xl shadow-lg py-1 z-50">
              <div class="px-4 py-3 border-b border-slate-100">
                <p class="text-sm font-semibold text-slate-900">{{ Auth::user()->full_name }}</p>
                <p class="text-xs text-slate-500">Owner — Branch {{ Auth::user()->default_branch_id ?? '—' }}</p>
              </div>
              <div class="py-1">
                <a href="{{ route('owner.home') }}" class="flex items-center gap-3 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Dashboard</a>
                <a href="{{ route('projects.index') }}" class="flex items-center gap-3 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Proyek</a>
              </div>
              <div class="border-t border-slate-100 py-1">
                <form method="POST" action="{{ route('logout') }}">
                  @csrf
                  <button type="submit" class="w-full flex items-center gap-3 px-4 py-2 text-sm text-rose-600 hover:bg-rose-50">Logout</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>

    {{-- Content --}}
    <main class="flex-1 px-4 md:px-8 py-6">
      @yield('content')
    </main>

  </div>
</div>

{{-- Backdrop for mobile sidebar --}}
<div id="sidebar-backdrop" class="fixed inset-0 z-30 bg-slate-900/20 backdrop-blur-sm hidden md:hidden" onclick="closeSidebar()"></div>

{{-- ===== SCRIPTS ===== --}}
<script>
  // Mobile sidebar
  const sidebar = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebar-backdrop');
  document.getElementById('menuBtn')?.addEventListener('click', () => {
    sidebar.classList.toggle('-translate-x-full');
    backdrop.classList.toggle('hidden');
  });
  function closeSidebar() {
    sidebar.classList.add('-translate-x-full');
    backdrop.classList.add('hidden');
  }

  // Submenu accordion
  document.querySelectorAll('#sidebar-nav [data-submenu]').forEach(btn => {
    const targetId = btn.dataset.submenu;
    const target = document.querySelector(`[data-submenu-target="${targetId}"]`);
    const chevron = btn.querySelector('svg:last-child');
    if (!target) return;

    btn.addEventListener('click', () => {
      target.classList.toggle('hidden');
      chevron?.classList.toggle('rotate-180');
    });
  });

  // Top user dropdown
  function toggleTopUserDropdown() {
    document.getElementById('top-user-menu').classList.toggle('hidden');
  }
  document.addEventListener('click', (e) => {
    const dd = document.getElementById('top-user-dropdown');
    const menu = document.getElementById('top-user-menu');
    if (dd && !dd.contains(e.target)) menu?.classList.add('hidden');
  });

  // Sidebar user dropdown
  function toggleSidebarUserDropdown() {
    document.getElementById('sidebar-user-menu').classList.toggle('hidden');
  }
  document.addEventListener('click', (e) => {
    const dd = document.getElementById('sidebar-user-dropdown');
    const menu = document.getElementById('sidebar-user-menu');
    if (dd && !dd.contains(e.target)) menu?.classList.add('hidden');
  });
</script>

@stack('scripts')

</body>
</html>
