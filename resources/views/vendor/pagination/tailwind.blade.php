@if ($paginator->hasPages())
  <nav class="flex items-center gap-1">
    {{-- Previous --}}
    @if ($paginator->onFirstPage())
      <span class="h-9 w-9 rounded-lg border border-slate-200 bg-white text-slate-300 flex items-center justify-center cursor-not-allowed">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      </span>
    @else
      <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
         class="h-9 w-9 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 flex items-center justify-center transition">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      </a>
    @endif

    {{-- Pages --}}
    @foreach ($elements as $element)
      @if (is_string($element))
        <span class="h-9 px-3 rounded-lg border border-slate-200 bg-white text-slate-400 text-xs font-medium flex items-center justify-center cursor-default">
          {{ $element }}
        </span>
      @endif

      @if (is_array($element))
        @foreach ($element as $page => $url)
          @if ($page == $paginator->currentPage())
            <span class="h-9 w-9 rounded-lg bg-emerald-600 text-white text-xs font-bold flex items-center justify-center shadow-sm">
              {{ $page }}
            </span>
          @else
            <a href="{{ $url }}"
               class="h-9 w-9 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 text-xs font-medium flex items-center justify-center transition">
              {{ $page }}
            </a>
          @endif
        @endforeach
      @endif
    @endforeach

    {{-- Next --}}
    @if ($paginator->hasMorePages())
      <a href="{{ $paginator->nextPageUrl() }}" rel="next"
         class="h-9 w-9 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 flex items-center justify-center transition">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      </a>
    @else
      <span class="h-9 w-9 rounded-lg border border-slate-200 bg-white text-slate-300 flex items-center justify-center cursor-not-allowed">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      </span>
    @endif
  </nav>
@endif
