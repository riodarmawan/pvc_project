@php
  $cart  = $cart  ?? [];
  $total = (float)($total ?? 0);
  $paid  = (float)($paid ?? 0);
  $due   = (float)($due ?? max(0, $total - $paid));
  $change = $paid > $total ? $paid - $total : 0;
@endphp

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
  {{-- Header --}}
  <div class="bg-gradient-to-r from-emerald-50 to-green-50 px-6 py-4 border-b border-gray-200">
    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
      <svg class="w-5 h-5 mr-2 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
      </svg>
      Ringkasan Transaksi
    </h3>
  </div>

  <div class="p-6 space-y-4">
    {{-- Transaction Details --}}
    <div class="space-y-3">
      <div class="flex justify-between items-center py-2 border-b border-gray-100">
        <span class="text-gray-600 flex items-center">
          <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
          </svg>
          Total Item
        </span>
        <span class="font-medium text-gray-900">{{ count($cart) }} item</span>
      </div>

      <div class="flex justify-between items-center py-2 border-b border-gray-100">
        <span class="text-gray-600 flex items-center">
          <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
          </svg>
          Subtotal
        </span>
        <span class="text-lg font-semibold text-gray-900">Rp {{ number_format($total, 0, ',', '.') }}</span>
      </div>

      <div class="flex justify-between items-center py-2 border-b border-gray-100">
        <span class="text-gray-600 flex items-center">
          <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
          </svg>
          Terbayar
        </span>
        <span class="font-semibold text-green-600">Rp {{ number_format($paid, 0, ',', '.') }}</span>
      </div>

      @if($change > 0)
        <div class="flex justify-between items-center py-2 border-b border-gray-100">
          <span class="text-gray-600 flex items-center">
            <svg class="w-4 h-4 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
            </svg>
            Kembalian
          </span>
          <span class="font-semibold text-blue-600">Rp {{ number_format($change, 0, ',', '.') }}</span>
        </div>
      @endif

      {{-- Outstanding Amount --}}
      <div class="flex justify-between items-center py-3 px-4 rounded-xl {{ $due > 0 ? 'bg-red-50 border border-red-200' : 'bg-green-50 border border-green-200' }}">
        <span class="font-medium {{ $due > 0 ? 'text-red-700' : 'text-green-700' }} flex items-center">
          @if($due > 0)
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            Kurang Bayar
          @else
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            Lunas
          @endif
        </span>
        <span class="text-xl font-bold {{ $due > 0 ? 'text-red-600' : 'text-green-600' }}">
          Rp {{ number_format($due, 0, ',', '.') }}
        </span>
      </div>
    </div>

    {{-- Finalize Button --}}
    <form id="form-finalize" class="js-ajax mt-6" method="post" action="{{ route('kasir.finalize') }}">
      @csrf
      @php
        $canFinalize = $due <= 0 && count($cart) > 0;
      @endphp
      <button {{ !$canFinalize ? 'disabled' : '' }}
              class="w-full px-6 py-4 rounded-xl font-semibold text-white transition-all duration-200 flex items-center justify-center space-x-3
                     {{ $canFinalize 
                        ? 'bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2' 
                        : 'bg-gray-300 cursor-not-allowed' }}">
        @if($canFinalize)
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
          </svg>
          <span>Finalisasi & Cetak Struk</span>
        @else
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5 9a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1zM5 13a1 1 0 011-1h4a1 1 0 110 2H6a1 1 0 01-1-1z" clip-rule="evenodd"></path>
            <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V5zm2 0v8h10V5H5z" clip-rule="evenodd"></path>
          </svg>
          <span>
            @if(count($cart) == 0)
              Keranjang Kosong
            @else
              Pembayaran Belum Lunas
            @endif
          </span>
        @endif
      </button>
    </form>
  </div>
</div>