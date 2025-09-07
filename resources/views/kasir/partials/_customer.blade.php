@php
  $customerId       = $customerId ?? null;
  $selectedCustomer = $selectedCustomer ?? null;
  $customerResults  = $customerResults ?? [];
@endphp

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
  {{-- Header --}}
  <div class="bg-gradient-to-r from-green-50 to-teal-50 px-6 py-4 border-b border-gray-200">
    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
      <svg class="w-5 h-5 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
      </svg>
      Pelanggan
    </h3>
  </div>

  <div class="p-6 space-y-4">
    {{-- Search Form --}}
    <form method="get" action="{{ route('kasir.checkout') }}" class="flex gap-3">
      <div class="relative flex-1">
        <input type="text" name="cq" value="{{ request('cq') }}"
               placeholder="Cari berdasarkan nama atau telepon..."
               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
        <div class="absolute inset-y-0 left-0 flex items-center pl-3">
          <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
          </svg>
        </div>
      </div>
      <button class="px-6 py-3 bg-green-600 text-white font-medium rounded-xl hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200">
        <svg class="w-4 h-4 mr-2 inline" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
        </svg>
        Cari
      </button>
    </form>

    {{-- Search Results --}}
    @if (count($customerResults))
      <div class="border border-gray-200 rounded-xl overflow-hidden">
        <div class="bg-gray-50 px-4 py-2">
          <h4 class="text-sm font-medium text-gray-700">Hasil Pencarian</h4>
        </div>
        <div class="max-h-64 overflow-y-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 sticky top-0">
              <tr>
                <th class="text-left py-3 px-4 font-medium text-gray-600">Nama</th>
                <th class="text-left py-3 px-4 font-medium text-gray-600">Telepon</th>
                <th class="py-3 px-4 w-24"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              @foreach ($customerResults as $c)
                <tr class="hover:bg-gray-50 transition-colors duration-200">
                  <td class="py-3 px-4 font-medium">{{ $c->name }}</td>
                  <td class="py-3 px-4 text-gray-600">{{ $c->phone }}</td>
                  <td class="py-3 px-4">
                    <form class="js-ajax inline" method="post" action="{{ route('kasir.customer.select') }}">
                      @csrf
                      <input type="hidden" name="customer_id" value="{{ $c->id }}">
                      <button class="px-3 py-1.5 text-xs font-medium text-green-700 bg-green-100 border border-green-200 rounded-lg hover:bg-green-200 focus:ring-2 focus:ring-green-500 focus:ring-offset-1 transition-all duration-200">
                        Pilih
                      </button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @endif

    {{-- Selected Customer --}}
    @if ($selectedCustomer)
      <div class="bg-gradient-to-r from-green-50 to-teal-50 rounded-xl border border-green-200 p-4">
        <div class="flex items-start gap-4">
          <div class="flex-shrink-0 w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
            <span class="text-lg font-bold text-green-600">
              {{ strtoupper(mb_substr($selectedCustomer->name,0,1)) }}
            </span>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
              <h4 class="font-semibold text-gray-900 truncate">{{ $selectedCustomer->name }}</h4>
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                Terpilih
              </span>
            </div>
            <div class="text-sm text-gray-600">{{ $selectedCustomer->phone ?: '—' }}</div>
            @if(!empty($selectedCustomer->address))
              <div class="text-xs text-gray-500 mt-1 truncate">{{ $selectedCustomer->address }}</div>
            @endif
          </div>
          <div class="flex flex-col gap-2">
            <button type="button"
                    data-modal-target="#modal-customer"
                    class="px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-100 border border-blue-200 rounded-lg hover:bg-blue-200 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 transition-all duration-200">
              Ganti
            </button>
            <form class="js-ajax" method="post" action="{{ route('kasir.customer.select') }}">
              @csrf
              <input type="hidden" name="clear" value="1">
              <button class="px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 focus:ring-2 focus:ring-red-500 focus:ring-offset-1 transition-all duration-200">
                Hapus
              </button>
            </form>
          </div>
        </div>
      </div>
    @else
      <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <svg class="w-5 h-5 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            <div>
              <h4 class="font-medium text-yellow-800">Pelanggan Belum Dipilih</h4>
              <p class="text-sm text-yellow-600">Pilih pelanggan atau tambah pelanggan baru</p>
            </div>
          </div>
          <button type="button"
                  data-modal-target="#modal-customer"
                  class="px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 flex items-center">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path>
            </svg>
            Tambah Pelanggan
          </button>
        </div>
      </div>
    @endif
  </div>
</div>