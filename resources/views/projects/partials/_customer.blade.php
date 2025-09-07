{{-- Pilih / buat cepat customer --}}
<div class="bg-white rounded-xl shadow-soft-lg p-4">
  <div class="flex items-center justify-between mb-3">
    <h3 class="text-sm font-semibold text-gray-700">Customer</h3>
    <a href="#modal-customer"
       class="text-xs px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100">
      Pilih Dari Daftar
    </a>
  </div>

  @php $c = session('project.customer'); @endphp

  @if ($c)
    <div class="p-3 rounded-lg border border-emerald-200 bg-emerald-50 text-sm text-emerald-800 mb-4">
      <div class="font-medium">Terpilih:</div>
      <div>{{ $c['name'] }}</div>
      <div class="text-xs text-emerald-700">Telp: {{ $c['phone'] ?? '—' }} · {{ $c['address'] ?? '—' }}</div>
    </div>
  @else
    <div class="p-3 rounded-lg border border-amber-200 bg-amber-50 text-sm text-amber-800 mb-4">
      Belum ada customer terpilih.
    </div>
  @endif

  <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
    {{-- Quick Create --}}
    <form action="{{ route('projects.customer.quick') }}" method="POST" class="md:col-span-7 bg-gray-50 p-3 rounded-lg border">
      @csrf
      <div class="text-xs font-semibold text-gray-600 mb-2">Buat Cepat Customer</div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="block text-xs text-gray-600 mb-1">Nama</label>
          <input name="name" required class="w-full px-3 py-2 border rounded-lg" placeholder="Nama customer">
        </div>
        <div>
          <label class="block text-xs text-gray-600 mb-1">Telepon</label>
          <input name="phone" class="w-full px-3 py-2 border rounded-lg" placeholder="08xxxxxxxxxx">
        </div>
        <div class="md:col-span-2">
          <label class="block text-xs text-gray-600 mb-1">Alamat</label>
          <textarea name="address" rows="2" class="w-full px-3 py-2 border rounded-lg" placeholder="Alamat..."></textarea>
        </div>
      </div>
      <div class="mt-3">
        <button class="px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700">
          Simpan & Pilih
        </button>
      </div>
    </form>

    {{-- Select dari dropdown cepat (opsi selain modal) --}}
    <form action="{{ route('projects.customer.select') }}" method="POST" class="md:col-span-5 bg-gray-50 p-3 rounded-lg border">
      @csrf
      <div class="text-xs font-semibold text-gray-600 mb-2">Pilih Cepat</div>
      <select name="customer_id" class="w-full px-3 py-2 border rounded-lg mb-3">
        @foreach ($customers as $row)
          <option value="{{ $row->id }}">{{ $row->name }} @if($row->phone) — {{ $row->phone }} @endif</option>
        @endforeach
      </select>
      <button class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">
        Pilih
      </button>
    </form>
  </div>
</div>
