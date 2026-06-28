<div id="modal-customer" class="hidden fixed inset-0 z-50 bg-black/40 backdrop-blur-sm">
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-xl bg-white shadow-2xl">
      {{-- Header --}}
      <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
        <h3 class="text-base font-semibold text-slate-900">Tambah Pelanggan Baru</h3>
        <button onclick="closeCustomerModal()" class="h-8 w-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      {{-- Body --}}
      <form id="quick-customer-form" class="p-5 space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Nama <span class="text-red-500">*</span></label>
          <input type="text" name="name" required
                 class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                 placeholder="Nama pelanggan">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Telepon</label>
          <input type="text" name="phone"
                 class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                 placeholder="08xxxxxxxxxx">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Alamat</label>
          <textarea name="address" rows="2"
                    class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 resize-none"
                    placeholder="Alamat (opsional)"></textarea>
        </div>
        <div class="flex gap-3 pt-2">
          <button type="button" onclick="closeCustomerModal()"
                  class="flex-1 h-10 rounded-lg border border-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50 transition">Batal</button>
          <button type="submit"
                  class="flex-1 h-10 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
