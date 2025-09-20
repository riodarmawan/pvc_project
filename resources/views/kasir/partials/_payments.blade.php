@php $payments = $payments ?? []; @endphp

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
  {{-- Header --}}
  <div class="bg-gradient-to-r from-indigo-50 to-purple-50 px-6 py-4 border-b border-gray-200">
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-semibold text-gray-800 flex items-center">
        <svg class="w-5 h-5 mr-2 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
          <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"></path>
          <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"></path>
        </svg>
        Pembayaran
      </h3>
      @if (count($payments))
        <form method="post" action="{{ route('kasir.pay.clear') }}" class="js-ajax inline">
          @csrf
          <button class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all duration-200">
            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
            </svg>
            Reset
          </button>
        </form>
      @endif
    </div>
  </div>

  <div class="p-6 space-y-4">
    {{-- Payment Form --}}
{{-- Payment Form --}}
<form method="post" action="{{ route('kasir.pay.add') }}" class="js-ajax space-y-4">
  @csrf
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Metode Pembayaran</label>
      <select name="method" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
        <option value="CASH">💵 Tunai</option>
        <option value="CARD">💳 Kartu Debit/Kredit</option>
        <option value="QR">📱 QR Code</option>
        <option value="TRANSFER">🏦 Transfer Bank</option>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Nominal Pembayaran</label>
      <div class="relative">
        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">Rp</span>
        <input type="number" step="0.01" min="0.01" required name="amount"
               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
               placeholder="0">
      </div>
    </div>
  </div>
  <div>
    <label class="block text-sm font-medium text-gray-700 mb-2">Referensi / No. Approval (Opsional)</label>
    <input type="text" name="ref_no" 
           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
           placeholder="Masukkan nomor referensi...">
  </div>
  
  {{-- TAMBAH INI: Input Catatan Transaksi --}}
  <div>
    <label class="block text-sm font-medium text-gray-700 mb-2">Catatan Transaksi (Opsional)</label>
    <textarea name="notes" rows="2"
              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors resize-none"
              placeholder="Catatan untuk transaksi ini...">{{ session('pos.notes', '') }}</textarea>
    <p class="text-xs text-gray-500 mt-1">Catatan akan tersimpan otomatis saat menambah pembayaran</p>
  </div>

  <button class="w-full px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-medium rounded-xl hover:from-indigo-700 hover:to-purple-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200 flex items-center justify-center">
    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
      <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path>
    </svg>
    Tambah Pembayaran
  </button>
</form>


    {{-- Payment List --}}
    @if (count($payments))
      <div class="border border-gray-200 rounded-xl overflow-hidden">
        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
          <h4 class="text-sm font-medium text-gray-700">Daftar Pembayaran</h4>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left py-3 px-4 font-medium text-gray-600">#</th>
                <th class="text-left py-3 px-4 font-medium text-gray-600">Metode</th>
                <th class="text-right py-3 px-4 font-medium text-gray-600">Nominal</th>
                <th class="text-left py-3 px-4 font-medium text-gray-600">Referensi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              @foreach ($payments as $i => $p)
                <tr class="hover:bg-gray-50 transition-colors duration-200">
                  <td class="py-3 px-4">
                   <span class="inline-flex items-center justify-center w-6 h-6 bg-indigo-100 text-indigo-800 text-xs font-bold rounded-full">
                     {{ $i + 1 }}
                   </span>
                 </td>
                 <td class="py-3 px-4">
                   <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                     {{ $p['method'] === 'CASH' ? 'bg-green-100 text-green-800' : '' }}
                     {{ $p['method'] === 'CARD' ? 'bg-blue-100 text-blue-800' : '' }}
                     {{ $p['method'] === 'QR' ? 'bg-purple-100 text-purple-800' : '' }}
                     {{ $p['method'] === 'TRANSFER' ? 'bg-orange-100 text-orange-800' : '' }}">
                     @switch($p['method'])
                       @case('CASH')
                         💵 Tunai
                         @break
                       @case('CARD')
                         💳 Kartu
                         @break
                       @case('QR')
                         📱 QR Code
                         @break
                       @case('TRANSFER')
                         🏦 Transfer
                         @break
                       @default
                         {{ $p['method'] ?? '-' }}
                     @endswitch
                   </span>
                 </td>
                 <td class="py-3 px-4 text-right font-semibold text-green-600">
                   Rp {{ number_format((float)($p['amount'] ?? 0), 0, ',', '.') }}
                 </td>
                 <td class="py-3 px-4 text-gray-600">{{ $p['ref_no'] ?? '—' }}</td>
               </tr>
             @endforeach
           </tbody>
         </table>
       </div>
     </div>
   @else
     <div class="text-center py-8">
       <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
       </svg>
       <h4 class="text-lg font-medium text-gray-900 mb-1">Belum Ada Pembayaran</h4>
       <p class="text-gray-500">Tambahkan metode pembayaran untuk melanjutkan transaksi</p>
     </div>
   @endif
 </div>
</div>