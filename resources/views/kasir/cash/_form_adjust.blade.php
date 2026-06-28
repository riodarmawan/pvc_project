<div class="bg-white rounded-xl border border-slate-200 p-5">
  <div class="flex items-center gap-2 mb-4">
    <div class="h-8 w-8 rounded-lg bg-indigo-50 flex items-center justify-center">
      <svg class="h-4 w-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
    </div>
    <div>
      <h3 class="text-sm font-semibold text-slate-900">Penyesuaian Kas</h3>
      <p class="text-[10px] text-slate-400">Setor / tarik bank, saldo awal</p>
    </div>
  </div>

  <form class="js-ajax space-y-3" method="post" action="{{ route('kasir.cash.adjust') }}">
    @csrf
    <input type="hidden" name="start_date" value="{{ $start }}">
    <input type="hidden" name="end_date"   value="{{ $end }}">

    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Arah</label>
        <select name="direction" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" required>
          <option value="IN">Masuk (IN)</option>
          <option value="OUT">Keluar (OUT)</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Kategori</label>
        <select name="category" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" required>
          <option value="OPENING">Saldo Awal</option>
          <option value="SETOR_BANK">Setor ke Bank</option>
          <option value="TARIK_BANK">Tarik dari Bank</option>
          <option value="LAINNYA">Lainnya</option>
        </select>
      </div>
    </div>

    <div>
      <label class="block text-xs font-medium text-slate-500 mb-1">Nominal</label>
      <div class="relative">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-400">Rp</span>
        <input type="number" name="amount" step="0.01" min="0.01" required
               class="w-full h-10 pl-9 pr-3 rounded-lg border border-slate-200 text-sm text-right tabular-nums focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
               placeholder="0">
      </div>
    </div>

    <div>
      <label class="block text-xs font-medium text-slate-500 mb-1">Catatan</label>
      <input type="text" name="memo"
             class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
             placeholder="Opsional">
    </div>

    <button type="submit" class="w-full h-10 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 active:bg-indigo-800 transition flex items-center justify-center gap-2">
      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
      Simpan Penyesuaian
    </button>
  </form>
</div>
