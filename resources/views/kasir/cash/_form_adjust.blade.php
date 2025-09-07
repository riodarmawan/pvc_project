<div class="bg-white rounded-xl shadow p-5 space-y-4">
  <div class="font-semibold">Penyesuaian Kas (Masuk/Keluar)</div>
  <form class="js-ajax space-y-3" method="post" action="{{ route('kasir.cash.adjust') }}">
    @csrf
    <input type="hidden" name="start_date" value="{{ $start }}">
    <input type="hidden" name="end_date"   value="{{ $end }}">

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
      <div>
        <label class="block text-xs text-gray-500 mb-1">Arah</label>
        <select name="direction" class="w-full rounded-lg border-gray-300" required>
          <option value="IN">Masuk (IN)</option>
          <option value="OUT">Keluar (OUT)</option>
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-500 mb-1">Kategori</label>
        <select name="category" class="w-full rounded-lg border-gray-300" required>
          <option value="OPENING">Saldo Awal</option>
          <option value="SETOR_BANK">Setor ke Bank</option>
          <option value="TARIK_BANK">Tarik dari Bank</option>
          <option value="LAINNYA">Lainnya</option>
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-500 mb-1">Nominal</label>
        <input type="number" name="amount" step="0.01" min="0.01" required class="w-full rounded-lg border-gray-300">
      </div>
      <div>
        <label class="block text-xs text-gray-500 mb-1">Catatan</label>
        <input type="text" name="memo" class="w-full rounded-lg border-gray-300" placeholder="opsional">
      </div>
    </div>

    <div class="pt-2">
      <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Simpan Penyesuaian</button>
    </div>
  </form>
</div>
