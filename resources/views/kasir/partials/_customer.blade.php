@php
  $customerId       = $customerId ?? null;
  $selectedCustomer = $selectedCustomer ?? null;
  $customerResults  = $customerResults ?? [];
@endphp

<div class="px-4 py-3 border-b border-slate-200 bg-slate-50">
  <div class="flex items-center justify-between">
    <div class="flex items-center gap-2">
      <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      <span class="text-sm font-semibold text-slate-900">Pelanggan</span>
    </div>
    @if($selectedCustomer)
      <button onclick="checkoutClearCustomer()" class="text-xs text-slate-500 hover:text-red-500 transition">Hapus pilihan</button>
    @endif
  </div>
</div>

<div class="p-4">
  @if ($selectedCustomer)
    {{-- Selected Customer --}}
    <div class="flex items-center gap-3 p-3 bg-emerald-50 rounded-lg border border-emerald-200">
      <div class="flex-shrink-0 w-9 h-9 bg-emerald-100 rounded-full flex items-center justify-center">
        <span class="text-sm font-bold text-emerald-700">{{ strtoupper(mb_substr($selectedCustomer->name, 0, 1)) }}</span>
      </div>
      <div class="flex-1 min-w-0">
        <div class="text-sm font-semibold text-slate-900 truncate">{{ $selectedCustomer->name }}</div>
        <div class="text-xs text-slate-500">{{ $selectedCustomer->phone ?: 'Tanpa telepon' }}</div>
      </div>
      <button onclick="checkoutClearCustomer()" class="h-7 w-7 rounded-md text-slate-400 hover:text-red-500 hover:bg-red-50 flex items-center justify-center transition flex-shrink-0">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
  @else
    {{-- Search + Quick Add --}}
    <div class="flex gap-2">
      <div class="relative flex-1 min-w-0">
        <div class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none">
          <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <input type="text" id="customer-search" placeholder="Cari nama/telepon..."
               class="w-full h-9 pl-8 pr-3 rounded-lg border border-slate-200 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" autocomplete="off">
        <div id="customer-results" class="absolute top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-lg z-20 hidden max-h-48 overflow-y-auto"></div>
      </div>
      <button onclick="openCustomerModal()" class="h-9 px-3 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700 transition flex items-center gap-1 flex-shrink-0 active:scale-95">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Baru
      </button>
    </div>
  @endif
</div>
