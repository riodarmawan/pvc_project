{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $title ?? 'App' }} — POS System</title>
  <script src="https://cdn.tailwindcss.com"></script>
  @stack('head')
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
  <div class="min-h-screen flex flex-col">
    <!-- Top Bar -->
    <header class="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-slate-200">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="h-16 flex items-center justify-between">

          <!-- Left: Brand + Title -->
          <div class="flex items-center gap-3 min-w-0">
            <a href="{{ url('/') }}" class="flex items-center gap-2 flex-shrink-0">
              <div class="h-9 w-9 rounded-xl bg-gradient-to-br from-emerald-400/30 to-blue-400/30 grid place-items-center ring-1 ring-slate-200">
                <svg viewBox="0 0 20 20" class="h-4.5 w-4.5 text-emerald-600">
                  <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                </svg>
              </div>
            </a>
            <div class="hidden sm:block h-5 w-px bg-slate-200"></div>
            <h1 class="hidden sm:block truncate text-base font-semibold text-slate-900">{{ $title ?? 'Dashboard' }}</h1>
          </div>

          <!-- Right: Nav + User -->
          <nav class="flex items-center gap-2">
            {{-- Desktop nav --}}
            <div class="hidden md:flex items-center gap-1.5">
              @auth
                {{-- Kasir (role 3) --}}
                @if ((int)auth()->user()->role_id === 3)
                  <a href="{{ route('kasir.pos') }}"
                     class="inline-flex items-center gap-2 h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition">
                    <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    POS
                  </a>

                  <a href="{{ route('kasir.history') }}"
                     class="inline-flex items-center gap-2 h-9 px-3 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition">
                    <svg viewBox="0 0 20 20" class="h-4 w-4 text-slate-400" fill="currentColor"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V8zm0 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2z" clip-rule="evenodd"/></svg>
                    Riwayat
                  </a>

                  <a href="{{ route('kasir.cash') }}"
                     class="inline-flex items-center gap-2 h-9 px-3 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition">
                    <svg viewBox="0 0 20 20" class="h-4 w-4 text-slate-400" fill="currentColor"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/></svg>
                    Kas
                  </a>

                  <a href="{{ route('kasir.products.new') }}"
                     class="inline-flex items-center gap-2 h-9 px-3 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition">
                    <svg viewBox="0 0 20 20" class="h-4 w-4 text-slate-400" fill="currentColor"><path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/></svg>
                    Produk
                  </a>
                @endif

                {{-- Owner (role 1) --}}
                @if ((int)auth()->user()->role_id === 1)
                  <a href="{{ route('accounting.chart') }}"
                     class="inline-flex items-center gap-2 h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition">
                    <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                    Akuntansi
                  </a>

                  <a href="{{ route('reports.income_statement') }}"
                     class="inline-flex items-center gap-2 h-9 px-3 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition">
                    <svg viewBox="0 0 20 20" class="h-4 w-4 text-slate-400" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    Laba Rugi
                  </a>

                  <a href="{{ route('reports.audit-log') }}"
                     class="inline-flex items-center gap-2 h-9 px-3 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Log Audit
                  </a>
                @endif
              @endauth
            </div>

            <div class="hidden md:block h-5 w-px bg-slate-200 mx-1"></div>

            {{-- User dropdown --}}
            @auth
              <div class="hidden md:block relative" id="user-dropdown-wrap">
                <button onclick="toggleUserDropdown()" id="user-dropdown-btn"
                        class="inline-flex items-center gap-2 h-9 px-2.5 rounded-lg hover:bg-slate-100 transition">
                  <div class="h-7 w-7 rounded-lg bg-emerald-100 flex items-center justify-center">
                    <span class="text-xs font-bold text-emerald-700">{{ strtoupper(substr(auth()->user()->full_name ?? 'U', 0, 1)) }}</span>
                  </div>
                  <span class="text-sm font-medium text-slate-700 max-w-[100px] truncate">{{ auth()->user()->full_name ?? 'User' }}</span>
                  <svg id="user-dropdown-chevron" class="h-4 w-4 text-slate-400 transition-transform" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>

                {{-- Dropdown menu --}}
                <div id="user-dropdown-menu" class="hidden absolute right-0 top-full mt-1 w-56 bg-white rounded-xl border border-slate-200 shadow-lg py-1 z-50">
                  {{-- User info --}}
                  <div class="px-4 py-3 border-b border-slate-100">
                    <p class="text-sm font-semibold text-slate-900">{{ auth()->user()->full_name }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">Branch {{ auth()->user()->default_branch_id ?? '—' }}</p>
                    <div class="inline-flex items-center gap-1.5 mt-1.5">
                      <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                      <span class="text-[11px] text-emerald-600 font-medium">Online</span>
                    </div>
                  </div>

                  {{-- Secondary links --}}
                  <div class="py-1">
                    @if ((int)auth()->user()->role_id === 3)
                      <a href="{{ route('kasir.home') }}" class="flex items-center gap-3 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        Katalog
                      </a>
                      <a href="{{ route('projects.index') }}" class="flex items-center gap-3 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        Projek
                      </a>
                      <a href="{{ route('kasir.products.new') }}" class="flex items-center gap-3 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Buat Produk
                      </a>
                    @endif
                    @if ((int)auth()->user()->role_id === 1)
                      <a href="{{ route('accounting.journal') }}" class="flex items-center gap-3 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Jurnal
                      </a>
                    @endif
                  </div>

                  {{-- Logout --}}
                  <div class="border-t border-slate-100 py-1">
                    <form method="POST" action="{{ route('logout') }}">
                      @csrf
                      <button type="submit" class="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Logout
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            @endauth

            {{-- Mobile menu button --}}
            <div class="md:hidden">
              <button id="mobile-menu-button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg hover:bg-slate-100">
                <svg viewBox="0 0 20 20" class="h-5 w-5 text-slate-600" fill="currentColor"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
              </button>
            </div>
          </nav>
        </div>

        {{-- Mobile Menu --}}
        <div id="mobile-menu" class="md:hidden hidden pb-4">
          <div class="mt-2 rounded-xl border border-slate-200 bg-white shadow-lg overflow-hidden">
            @auth
              <div class="px-4 py-3 border-b border-slate-100 flex items-center gap-3">
                <div class="h-8 w-8 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                  <span class="text-xs font-bold text-emerald-700">{{ strtoupper(substr(auth()->user()->full_name ?? 'U', 0, 1)) }}</span>
                </div>
                <div class="min-w-0">
                  <p class="text-sm font-semibold text-slate-900 truncate">{{ auth()->user()->full_name }}</p>
                  <p class="text-xs text-slate-500">Branch {{ auth()->user()->default_branch_id ?? '—' }}</p>
                </div>
              </div>

              <div class="p-2 space-y-1">
                @if ((int)auth()->user()->role_id === 3)
                  <a href="{{ route('kasir.pos') }}" class="flex items-center gap-3 h-10 px-3 rounded-lg bg-emerald-600 text-white font-semibold">
                    <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    POS
                  </a>
                  <a href="{{ route('kasir.home') }}" class="flex items-center gap-3 h-10 px-3 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    Katalog
                  </a>
                  <a href="{{ route('projects.index') }}" class="flex items-center gap-3 h-10 px-3 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    Projek
                  </a>
                  <a href="{{ route('kasir.products.new') }}" class="flex items-center gap-3 h-10 px-3 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Buat Produk
                  </a>
                  <a href="{{ route('kasir.history') }}" class="flex items-center gap-3 h-10 px-3 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Riwayat
                  </a>
                  <a href="{{ route('kasir.cash') }}" class="flex items-center gap-3 h-10 px-3 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Kas
                  </a>
                @endif

                @if ((int)auth()->user()->role_id === 1)
                  <a href="{{ route('accounting.chart') }}" class="flex items-center gap-3 h-10 px-3 rounded-lg bg-emerald-600 text-white font-semibold">
                    <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                    Akuntansi
                  </a>
                  <a href="{{ route('accounting.journal') }}" class="flex items-center gap-3 h-10 px-3 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Jurnal
                  </a>
                  <a href="{{ route('reports.income_statement') }}" class="flex items-center gap-3 h-10 px-3 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Laba Rugi
                  </a>
                  <a href="{{ route('reports.audit-log') }}" class="flex items-center gap-3 h-10 px-3 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Log Audit
                  </a>
                @endif
              </div>

              <div class="p-2 border-t border-slate-100">
                <form method="POST" action="{{ route('logout') }}">
                  @csrf
                  <button type="submit" class="w-full flex items-center justify-center gap-2 h-10 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Logout
                  </button>
                </form>
              </div>
            @endauth
          </div>
        </div>
      </div>
    </header>

    <!-- Content -->
    <main class="flex-1">
      <!-- Toasts -->
      <div id="toast-container" class="fixed right-4 top-20 z-[60] space-y-2">
        @if (session('ok'))
          <div class="rounded-xl border border-emerald-200 bg-emerald-50 shadow-card px-4 py-3">
            <div class="flex items-start gap-3">
              <svg viewBox="0 0 20 20" class="h-5 w-5 text-emerald-600">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 10-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
              </svg>
              <div>
                <p class="font-semibold text-emerald-800">Berhasil!</p>
                <p class="text-sm text-emerald-700">{{ session('ok') }}</p>
              </div>
            </div>
          </div>
        @endif

        @if ($errors->any())
          <div class="rounded-xl border border-rose-200 bg-rose-50 shadow-card px-4 py-3">
            <div class="flex items-start gap-3">
              <svg viewBox="0 0 20 20" class="h-5 w-5 text-rose-600">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
              </svg>
              <div>
                <p class="font-semibold text-rose-800">Error!</p>
                <p class="text-sm text-rose-700">{{ $errors->first() }}</p>
              </div>
            </div>
          </div>
        @endif
      </div>

      <!-- Main slot -->
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
        <div class="rounded-2xl">
          @yield('content')
        </div>
      </div>
    </main>

  </div>

  @stack('modals')

  @push('scripts')
  <script>
    // Toggle mobile menu
    (function(){
      const btn = document.getElementById('mobile-menu-button');
      const menu = document.getElementById('mobile-menu');
      if(!btn || !menu) return;
      btn.addEventListener('click', () => menu.classList.toggle('hidden'));
      document.addEventListener('click', (e) => {
        if (!menu.classList.contains('hidden')) {
          const within = menu.contains(e.target) || btn.contains(e.target);
          if (!within) menu.classList.add('hidden');
        }
      });
    })();

    // User dropdown
    function toggleUserDropdown() {
      const menu = document.getElementById('user-dropdown-menu');
      const chevron = document.getElementById('user-dropdown-chevron');
      if (!menu) return;
      menu.classList.toggle('hidden');
      if (chevron) chevron.style.transform = menu.classList.contains('hidden') ? '' : 'rotate(180deg)';
    }

    // Close user dropdown on outside click
    document.addEventListener('click', (e) => {
      const wrap = document.getElementById('user-dropdown-wrap');
      const menu = document.getElementById('user-dropdown-menu');
      const chevron = document.getElementById('user-dropdown-chevron');
      if (wrap && menu && !wrap.contains(e.target)) {
        menu.classList.add('hidden');
        if (chevron) chevron.style.transform = '';
      }
    });

    // Auto hide toasts
    (function(){
      const toastWrap = document.getElementById('toast-container');
      if (!toastWrap) return;
      setTimeout(() => toastWrap.querySelectorAll('div').forEach(t => {
        t.classList.add('transition', 'duration-300', 'ease-out', 'opacity-0', 'translate-y-1');
        setTimeout(() => t.remove(), 350);
      }), 3500);
    })();
  </script>
  @endpush

  @stack('scripts')
</body>
</html>
