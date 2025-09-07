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
        <div class="h-16 flex items-center justify-between gap-4">

          <!-- Left: brand + title/breadcrumb + user quick info -->
          <div class="flex items-center gap-4 min-w-0">
            <!-- Brand -->
            <div class="flex items-center gap-2">
              <div class="h-10 w-10 rounded-2xl bg-gradient-to-br from-violet-400/30 to-blue-400/30 grid place-items-center ring-1 ring-slate-200">
                <svg viewBox="0 0 20 20" class="h-5 w-5 text-blue-600">
                  <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                </svg>
              </div>
              <div class="hidden sm:block font-semibold text-slate-900">POS</div>
            </div>

            <!-- Title & breadcrumb + quick user info (collapse nicely) -->
            <div class="flex items-center gap-3 min-w-0">
              <div class="flex items-center gap-2 truncate">
                <h1 class="truncate text-base sm:text-lg font-semibold text-slate-900">{{ $title ?? 'Dashboard' }}</h1>
                <svg viewBox="0 0 20 20" class="h-4 w-4 text-slate-400">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 111.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-slate-500 shrink-0">POS System</span>
              </div>

              @auth
                <div class="hidden md:flex items-center gap-4 pl-4 ml-4 border-l border-slate-200">
                  <div class="inline-flex items-center gap-2 text-sm text-slate-600">
                    <svg viewBox="0 0 20 20" class="h-4 w-4 text-slate-400">
                      <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="font-medium text-slate-800">{{ auth()->user()->full_name }}</span>
                  </div>
                  <div class="inline-flex items-center gap-2 text-sm text-slate-600">
                    <svg viewBox="0 0 20 20" class="h-4 w-4 text-slate-400">
                      <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4z" clip-rule="evenodd"></path>
                    </svg>
                    Branch: {{ auth()->user()->default_branch_id ?? '—' }}
                  </div>
                  <div class="inline-flex items-center gap-2 text-xs">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-emerald-500/20"></span>
                    <span class="text-emerald-600">Online</span>
                  </div>
                </div>
              @endauth
            </div>
          </div>

          <!-- Right: nav links + mobile button + user menu -->
          <nav class="flex items-center gap-3">
            <!-- Desktop links -->
            <div class="hidden md:flex items-center gap-2">
              <a href="{{ url('/') }}" class="inline-flex items-center gap-2 h-10 px-3 rounded-xl border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 transition">
                <svg viewBox="0 0 20 20" class="h-4 w-4 text-blue-600">
                  <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                </svg>
                <span class="font-medium">Home</span>
              </a>

              @auth
                @if ((int)auth()->user()->role_id === 3)
                  <a href="{{ route('kasir.home') }}" class="inline-flex items-center gap-2 h-10 px-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 transition">
                    <svg viewBox="0 0 20 20" class="h-4 w-4 text-indigo-600">
                      <path fill-rule="evenodd" d="M10 2L3 7v11a2 2 0 002 2h10a2 2 0 002-2V7l-7-5zM8 15V9h4v6H8z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="font-medium">Katalog</span>
                  </a>

                  <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-2 h-10 px-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 transition">
                    <svg viewBox="0 0 20 20" class="h-4 w-4 text-emerald-600" aria-hidden="true">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.707a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 10-1.414 1.414l2 2a1 1 0 001.414 0l3-3z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="font-medium">Projek</span>
                  </a>

                  <a href="{{ route('kasir.history') }}" class="inline-flex items-center gap-2 h-10 px-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 transition">
                    <svg viewBox="0 0 20 20" class="h-4 w-4 text-amber-600">
                      <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V8zm0 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="font-medium">Riwayat</span>
                  </a>
                  
                  <a href="{{ route('kasir.cash') }}" class="inline-flex items-center gap-2 h-10 px-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 transition">
                    <svg viewBox="0 0 20 20" class="h-4 w-4 text-rose-600">
                      <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="font-medium">Kas</span>
                  </a>
                @endif
              @endauth
            </div>

            <!-- Mobile menu button -->
            <div class="md:hidden">
              <button id="mobile-menu-button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 hover:bg-slate-50">
                <svg viewBox="0 0 20 20" class="h-5 w-5 text-slate-700">
                  <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                </svg>
              </button>
            </div>

            @auth
              <!-- User Menu (logout only, simple) -->
              <div class="hidden md:block">
                <form method="POST" action="{{ route('logout') }}">
                  @csrf
                  <button type="submit" class="inline-flex items-center gap-2 h-10 px-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700">
                    <svg viewBox="0 0 20 20" class="h-4 w-4 text-slate-500">
                      <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="font-medium">Logout</span>
                  </button>
                </form>
              </div>
            @endauth
          </nav>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="md:hidden hidden pb-4">
          <div class="mt-2 rounded-2xl border border-slate-200 bg-white shadow-card overflow-hidden">
            @auth
              <div class="px-4 py-3 border-b border-slate-200">
                <div class="font-medium">{{ auth()->user()->full_name }}</div>
                <div class="text-sm text-slate-600">Branch: {{ auth()->user()->default_branch_id ?? '—' }}</div>
                <div class="mt-2 inline-flex items-center gap-2 text-xs">
                  <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-emerald-500/20"></span>
                  <span class="text-emerald-600">Online</span>
                </div>
              </div>
            @endauth

            <div class="p-2 grid gap-2">
              <a href="{{ url('/') }}" class="inline-flex items-center gap-2 h-11 px-3 rounded-xl border border-slate-200 hover:bg-slate-50">
                <svg viewBox="0 0 20 20" class="h-4 w-4 text-blue-600">
                  <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                </svg>
                Home
              </a>

              @auth
                @if ((int)auth()->user()->role_id === 3)
                  <a href="{{ route('kasir.home') }}" class="inline-flex items-center gap-2 h-11 px-3 rounded-xl border border-slate-200 hover:bg-slate-50">
                    <svg viewBox="0 0 20 20" class="h-4 w-4 text-indigo-600">
                      <path fill-rule="evenodd" d="M10 2L3 7v11a2 2 0 002 2h10a2 2 0 002-2V7l-7-5zM8 15V9h4v6H8z" clip-rule="evenodd"></path>
                    </svg>
                    Katalog
                  </a>

                  <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-2 h-11 px-3 rounded-xl border border-slate-200 hover:bg-slate-50">
                    <svg viewBox="0 0 20 20" class="h-4 w-4 text-emerald-600" aria-hidden="true">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.707a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 10-1.414 1.414l2 2a1 1 0 001.414 0l3-3z" clip-rule="evenodd"></path>
                    </svg>
                    Projek Terpasang
                  </a>

                  <a href="{{ route('kasir.history') }}" class="inline-flex items-center gap-2 h-11 px-3 rounded-xl border border-slate-200 hover:bg-slate-50">
                    <svg viewBox="0 0 20 20" class="h-4 w-4 text-amber-600">
                      <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V8zm0 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2z" clip-rule="evenodd"></path>
                    </svg>
                    Riwayat
                  </a>

                  <a href="{{ route('kasir.cash') }}" class="inline-flex items-center gap-2 h-11 px-3 rounded-xl border border-slate-200 hover:bg-slate-50">
                    <svg viewBox="0 0 20 20" class="h-4 w-4 text-rose-600">
                      <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                    </svg>
                    Kas
                  </a>
                @endif
              @endauth
            </div>

            @auth
              <div class="p-3 border-t border-slate-200">
                <form method="POST" action="{{ route('logout') }}">
                  @csrf
                  <button type="submit" class="w-full inline-flex items-center justify-center gap-2 h-11 px-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-50">
                    <svg viewBox="0 0 20 20" class="h-4 w-4 text-slate-500">
                      <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"></path>
                    </svg>
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

    <!-- Footer -->
    <footer class="border-t border-slate-200 bg-white">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="py-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="h-9 w-9 rounded-xl bg-gradient-to-br from-violet-400/30 to-blue-400/30 grid place-items-center ring-1 ring-slate-200">
              <svg viewBox="0 0 20 20" class="h-4 w-4 text-blue-600">
                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
              </svg>
            </div>
            <div>
              <p class="font-semibold text-slate-900">POS System</p>
              <p class="text-sm text-slate-500">Modern Point of Sales</p>
            </div>
          </div>

          <div class="text-sm text-slate-500">
            <p>© {{ date('Y') }} POS System. All rights reserved.</p>
            <p>Powered by Modern Technology</p>
          </div>
        </div>
      </div>
    </footer>
  </div>

  @stack('modals')
  @stack('scripts')

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
  <script src="{{ asset('js/project.js') }}" defer></script>
  @endpush
</body>
</html>
