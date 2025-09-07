{{-- Modal Pilih Customer (tanpa JS khusus; tampilkan via anchor #modal-customer atau include langsung) --}}
<div id="modal-customer" class="fixed inset-0 z-50 hidden items-center justify-center">
  {{-- backdrop (biarkan hidden by default; jika kamu pakai JS sederhana bisa toggle .hidden) --}}
  <div class="absolute inset-0 bg-black/40"></div>

  <div class="relative bg-white rounded-2xl shadow-soft-xl w-full max-w-2xl p-5">
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-sm font-semibold text-gray-800">Pilih Customer</h3>
      <a href="#" class="text-gray-500 hover:text-gray-700 text-sm">Tutup</a>
    </div>

    <form action="{{ route('projects.customer.select') }}" method="POST" class="space-y-3">
      @csrf
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-72 overflow-auto border rounded-lg p-3">
        @foreach ($customers as $row)
          <label class="flex items-center gap-3 text-sm p-2 rounded-lg hover:bg-gray-50 border">
            <input type="radio" name="customer_id" value="{{ $row->id }}" class="text-blue-600">
            <div>
              <div class="font-medium text-gray-800">{{ $row->name }}</div>
              <div class="text-xs text-gray-500">{{ $row->phone ?? '—' }}</div>
            </div>
          </label>
        @endforeach
      </div>

      <div class="text-right">
        <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">
          Pilih Customer
        </button>
      </div>
    </form>
  </div>
</div>
