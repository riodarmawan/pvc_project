<div id="modal-invoice" class="hidden fixed inset-0 z-50 bg-black/40 backdrop-blur-sm">
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-xl bg-white shadow-2xl max-h-[90vh] overflow-y-auto">
      {{-- Header --}}
      <div class="sticky top-0 bg-white px-5 py-4 border-b border-slate-200 flex items-center justify-between rounded-t-xl">
        <h3 class="text-base font-semibold text-slate-900">Struk Pembayaran</h3>
        <div class="flex items-center gap-2">
          <button onclick="window.print()" class="h-8 px-3 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700 transition flex items-center gap-1">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Cetak
          </button>
          <button onclick="document.getElementById('modal-invoice').classList.add('hidden')" class="h-8 w-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
      </div>
      <div id="invoice-content" class="p-5"></div>
    </div>
  </div>
</div>
