<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>{{ isset($title) ? $title . ' - ' : '' }} {{ config('app.name', 'Laravel') }}</title>

  <!-- Inter -->
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

  <!-- Tailwind CDN + theme -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          fontFamily: { sans: ['Inter','ui-sans-serif','system-ui'] },
          colors: {
            brand: '#2563eb',
            brandDark: '#6366f1',
            panelDark: '#0f172a',
          },
          boxShadow: {
            card: '0 10px 20px rgba(2, 6, 23, 0.05)', // lembut utk light
          }
        }
      }
    }
  </script>
</head>

<body class="bg-slate-50 text-slate-800 antialiased font-sans dark:bg-[#0b1220] dark:text-slate-200">
  <div class="min-h-screen flex">

    <!-- Sidebar -->
    <aside id="sidebar"
      class="fixed z-40 inset-y-0 left-0 w-72 -translate-x-full md:translate-x-0 md:static md:flex-shrink-0
             bg-white/95 backdrop-blur border-r border-slate-200 shadow-card
             dark:bg-panelDark/90 dark:border-[rgba(148,163,184,.12)]
             transition-transform">
      
      <div class="h-16 px-6 flex items-center border-b border-slate-200 dark:border-[rgba(148,163,184,.12)]">
        <a href="/" class="text-lg font-semibold tracking-tight">Monitoring Dashboard</a>
      </div>

      <!-- ### NAVIGASI BARU DIMULAI DI SINI ### -->
      <nav class="flex-1 overflow-y-auto p-4 space-y-2">
        <!-- Dashboard -->
        <a href="/"
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
    <!-- Buat Cabang Baru - Diperbaiki -->
    <a href="{{ route('admin.branches.create') }}"
       class="block px-3 py-2 rounded-lg text-sm hover:bg-slate-100 border border-transparent hover:border-slate-200
              dark:hover:bg-white/5 dark:hover:border-[rgba(148,163,184,.12)]">
      <span class="flex items-center gap-2">
        <!-- SVG Icon untuk cabang/branch -->
        <svg width="16" height="16" class="text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
        </svg>
        <span>Buat Cabang Baru</span>
      </span>
    </a>
  </div>
</div>



      </nav>
      <!-- ### AKHIR DARI NAVIGASI BARU ### -->

      <div class="p-4 border-t border-slate-200 dark:border-[rgba(148,163,184,.12)]">
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
    </aside>

    <!-- Main content -->
    <div class="flex-1 min-w-0">
      <!-- Header -->
      <header class="sticky top-0 z-30 bg-white/80 backdrop-blur border-b border-slate-200
                     dark:bg-[#0b1220]/70 dark:border-[rgba(148,163,184,.12)]">
        <div class="h-16 px-4 md:px-6 flex items-center justify-between">
          <button id="menuBtn" type="button"
                  class="p-2 rounded-lg border border-slate-200 hover:bg-slate-100 md:hidden
                         dark:border-[rgba(148,163,184,.12)] dark:hover:bg-white/5">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>

          <div class="flex-1 flex items-center">
            <h1 class="text-lg font-semibold">{{ $title ?? 'Dashboard' }}</h1>
          </div>

          <div class="flex items-center gap-2">
            <!-- Theme toggle (tambahan kecil untuk light/dark) -->
            <button id="themeToggle" type="button"
              class="p-2 rounded-xl border border-slate-200 hover:bg-slate-100
                     dark:border-[rgba(148,163,184,.12)] dark:hover:bg-white/5"
              aria-label="Toggle theme">
              <svg id="iconSun" class="h-5 w-5 hidden dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.364 6.364l-1.414-1.414M7.05 7.05L5.636 5.636m12.728 0l-1.414 1.414M7.05 16.95l-1.414 1.414M12 8a4 4 0 100 8 4 4 0 000-8z"/>
              </svg>
              <svg id="iconMoon" class="h-5 w-5 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
              </svg>
            </button>

            <div class="relative">
              <button id="profileBtn" type="button"
                class="flex items-center gap-2 px-2 py-1.5 rounded-xl hover:bg-slate-100
                       dark:hover:bg-white/5">
                <span class="text-sm">{{ Auth::user()->name }}</span>
                <img class="h-9 w-9 rounded-xl border border-slate-200 dark:border-[rgba(148,163,184,.12)]"
                     src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=dbeafe&color=1e3a8a" alt="User avatar">
              </button>
              <div id="profileMenu" class="absolute right-0 mt-2 w-44 bg-white border border-slate-200 rounded-xl shadow-card p-2 hidden
                                           dark:bg-panelDark dark:border-[rgba(148,163,184,.12)]">
                <a href="#" class="block px-3 py-2 rounded-lg text-sm hover:bg-slate-100 dark:hover:bg-white/5">Profil Anda</a>
                <a href="#" class="block px-3 py-2 rounded-lg text-sm hover:bg-slate-100 dark:hover:bg-white/5">Pengaturan</a>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Content Area -->
      <main class="px-4 md:px-6 py-6">
        @yield('content')
      </main>
    </div>
  </div>

  <!-- ===== JS Fungsionalitas Layout ===== -->
  <script>
    // 0) Apply saved theme on load
    (function() {
      const saved = localStorage.getItem('theme'); // 'light' | 'dark' | null
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      const isDark = saved ? saved === 'dark' : prefersDark;
      document.documentElement.classList.toggle('dark', isDark);
    })();

    // 1) Toggle sidebar (mobile)
    const sidebar = document.getElementById('sidebar');
    document.getElementById('menuBtn')?.addEventListener('click', () => {
      sidebar.classList.toggle('-translate-x-full');
    });

    // 2) Toggle submenu nav (div > button + div)
    document.querySelectorAll('aside nav > div').forEach(section => {
      const btn = section.querySelector('button');
      const menu = btn?.nextElementSibling;
      const arrow = btn?.querySelector('svg[viewBox="0 0 24 24"]');
      if (btn && menu) {
        menu.classList.add('hidden');
        btn.addEventListener('click', () => {
          menu.classList.toggle('hidden');
          arrow?.classList.toggle('rotate-180');
        });
      }
    });

    // 3) Toggle profile dropdown
    const profileBtn = document.getElementById('profileBtn');
    const profileMenu = document.getElementById('profileMenu');
    profileBtn?.addEventListener('click', () => profileMenu.classList.toggle('hidden'));
    document.addEventListener('click', (e) => {
      if (!profileBtn?.contains(e.target) && !profileMenu?.contains(e.target)) {
        profileMenu?.classList.add('hidden');
      }
    });

    // 4) Theme toggle + persist
    const themeBtn = document.getElementById('themeToggle');
    themeBtn?.addEventListener('click', () => {
      const html = document.documentElement;
      const toDark = !html.classList.contains('dark');
      html.classList.toggle('dark', toDark);
      localStorage.setItem('theme', toDark ? 'dark' : 'light');
    });
  </script>
</body>
</html>
