<div class="bg-white rounded-xl border border-slate-200 p-5">
  <div class="flex items-center gap-2 mb-4">
    <div class="h-8 w-8 rounded-lg bg-rose-50 flex items-center justify-center">
      <svg class="h-4 w-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
    </div>
    <div>
      <h3 class="text-sm font-semibold text-slate-900">Pengeluaran Kas Kecil</h3>
      <p class="text-[10px] text-slate-400">Beban operasional harian</p>
    </div>
  </div>

  <form class="js-ajax space-y-3" method="post" action="{{ route('kasir.cash.out') }}">
    @csrf
    <input type="hidden" name="start_date" value="{{ $start }}">
    <input type="hidden" name="end_date"   value="{{ $end }}">

    <div>
      <label class="block text-xs font-medium text-slate-500 mb-1">Kategori</label>
      <select name="category" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" required>
        <option value="BBM">BBM</option>
        <option value="PARKIR">Parkir</option>
        <option value="TOL">Tol</option>
        <option value="MAKAN">Makan</option>
        <option value="LAINNYA">Lainnya</option>
      </select>
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

    <button type="submit" class="w-full h-10 rounded-lg bg-rose-600 text-white text-sm font-semibold hover:bg-rose-700 active:bg-rose-800 transition flex items-center justify-center gap-2">
      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
      Simpan Pengeluaran
    </button>
  </form>
</div>
