{{-- Input Jasa/Service (nama + harga) --}}
<div class="bg-white rounded-xl shadow-soft-lg p-4">
  <h3 class="text-sm font-semibold text-gray-700 mb-3">Jasa / Service</h3>

  {{-- Form tambah service --}}
  <form action="{{ route('projects.cart.add') }}" method="POST" class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-4">
    @csrf
    <input type="hidden" name="type" value="service">
    <div class="md:col-span-7">
      <label class="block text-xs text-gray-600 mb-1">Nama Service</label>
      <input name="name" type="text" required
             class="w-full px-3 py-2 border rounded-lg"
             placeholder="Contoh: Antar barang / Tukang">
    </div>
    <div class="md:col-span-3">
      <label class="block text-xs text-gray-600 mb-1">Harga</label>
      <input name="price" type="number" min="0" step="0.01" required
             class="w-full px-3 py-2 border rounded-lg" placeholder="0">
    </div>
    <div class="md:col-span-2 flex items-end">
      <button class="w-full px-3 py-2 rounded-lg bg-purple-600 text-white text-sm hover:bg-purple-700">
        Tambah Service
      </button>
    </div>
  </form>

  {{-- Daftar service di cart --}}
  <div class="overflow-hidden rounded-lg border border-gray-200">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-3 py-2 text-left text-gray-600">Nama</th>
          <th class="px-3 py-2 text-right text-gray-600">Harga</th>
          <th class="px-3 py-2 w-48"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 bg-white">
        @php $services = session('project.services', []); @endphp
        @forelse ($services as $s)
          <tr>
            <td class="px-3 py-2">
              <form action="{{ route('projects.cart.update') }}" method="POST" class="flex items-center gap-2">
                @csrf
                <input type="hidden" name="kind" value="service">
                <input type="hidden" name="row_id" value="{{ $s['row_id'] }}">
                <input name="name" type="text" required value="{{ $s['name'] }}"
                       class="flex-1 px-2 py-1 border rounded-lg">
            </td>
            <td class="px-3 py-2">
                <input name="price" type="number" min="0" step="0.01" required value="{{ $s['price'] }}"
                       class="w-36 text-right px-2 py-1 border rounded-lg">
            </td>
            <td class="px-3 py-2 text-right">
                <button class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs hover:bg-blue-700">Update</button>
              </form>
              <form action="{{ route('projects.cart.remove') }}" method="POST" class="inline-block ml-2">
                @csrf
                <input type="hidden" name="kind" value="service">
                <input type="hidden" name="row_id" value="{{ $s['row_id'] }}">
                <button class="px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 text-xs hover:bg-rose-100">
                  Hapus
                </button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="3" class="px-3 py-3 text-center text-gray-500">Belum ada service.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
