@php $payments = $payments ?? []; @endphp

<div class="px-4 py-3 border-b border-slate-200 bg-slate-50">
  <div class="flex items-center justify-between">
    <div class="flex items-center gap-2">
      <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
      <span class="text-sm font-semibold text-slate-900">Pembayaran</span>
    </div>
    @if(count($payments))
      <button onclick="checkoutClearPayments()" class="text-xs text-slate-500 hover:text-red-500 transition">Reset</button>
    @endif
  </div>
</div>

<div class="p-4 space-y-3">
  {{-- Payment Method Buttons --}}
  <div class="grid grid-cols-4 gap-1.5 sm:gap-2">
    <button onclick="selectPayMethod('CASH')" id="paymethod-CASH"
            class="paymethod-btn flex flex-col items-center gap-1 py-2.5 px-1 rounded-lg border-2 border-emerald-500 bg-emerald-50 transition active:scale-95">
      <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
      <span class="text-[10px] font-semibold text-emerald-700">Tunai</span>
    </button>
    <button onclick="selectPayMethod('CARD')" id="paymethod-CARD"
            class="paymethod-btn flex flex-col items-center gap-1 py-2.5 px-1 rounded-lg border-2 border-slate-200 hover:border-slate-300 transition active:scale-95">
      <svg class="h-5 w-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
      <span class="text-[10px] font-medium text-slate-500">Kartu</span>
    </button>
    <button onclick="selectPayMethod('QR')" id="paymethod-QR"
            class="paymethod-btn flex flex-col items-center gap-1 py-2.5 px-1 rounded-lg border-2 border-slate-200 hover:border-slate-300 transition active:scale-95">
      <svg class="h-5 w-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
      <span class="text-[10px] font-medium text-slate-500">QRIS</span>
    </button>
    <button onclick="selectPayMethod('TRANSFER')" id="paymethod-TRANSFER"
            class="paymethod-btn flex flex-col items-center gap-1 py-2.5 px-1 rounded-lg border-2 border-slate-200 hover:border-slate-300 transition active:scale-95">
      <svg class="h-5 w-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
      <span class="text-[10px] font-medium text-slate-500">Transfer</span>
    </button>
  </div>

  {{-- Amount Input --}}
  <div>
    <label class="block text-xs font-medium text-slate-500 mb-1">Jumlah Bayar</label>
    <div class="relative">
      <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 text-sm font-medium">Rp</span>
      <input type="number" id="pay-amount" step="1" min="1" required
             class="w-full h-11 pl-9 pr-3 rounded-lg border border-slate-200 text-lg font-bold tabular-nums focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
             placeholder="0">
    </div>
  </div>

  {{-- Quick Amount Buttons --}}
  <div id="quick-amounts" class="grid grid-cols-3 gap-1.5 sm:gap-2">
    <button onclick="setQuickAmount('exact')" class="h-9 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50 active:bg-slate-100 transition">Uang Pas</button>
    <button onclick="setQuickAmount('50k')" class="h-9 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50 active:bg-slate-100 transition">+50.000</button>
    <button onclick="setQuickAmount('100k')" class="h-9 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50 active:bg-slate-100 transition">+100.000</button>
  </div>

  {{-- Add Payment Button --}}
  <button onclick="checkoutAddPayment()" id="btn-add-pay"
          class="w-full h-11 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 active:bg-emerald-800 transition flex items-center justify-center gap-1.5">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Tambah Pembayaran
  </button>

  {{-- Payment List --}}
  @if (count($payments))
    <div class="space-y-1.5" id="payment-list">
      @foreach ($payments as $i => $p)
        <div class="flex items-center justify-between py-2 px-3 bg-slate-50 rounded-lg">
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center h-5 min-w-[20px] px-1.5 rounded bg-slate-200 text-slate-600 text-[10px] font-bold">{{ $i + 1 }}</span>
            <span class="text-xs font-medium text-slate-700">
              @switch($p['method'])
                @case('CASH') Tunai @break
                @case('CARD') Kartu @break
                @case('QR') QRIS @break
                @case('TRANSFER') Transfer @break
                @default {{ $p['method'] }}
              @endswitch
            </span>
          </div>
          <span class="text-sm font-semibold text-slate-900 tabular-nums">Rp {{ number_format($p['amount'] ?? 0, 0, ',', '.') }}</span>
        </div>
      @endforeach
    </div>
  @else
    <div id="payment-empty" class="text-center py-4">
      <svg class="h-8 w-8 mx-auto text-slate-200 mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
      <p class="text-xs text-slate-400">Belum ada pembayaran</p>
    </div>
  @endif
</div>
