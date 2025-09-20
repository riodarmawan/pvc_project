{{-- resources/views/kasir/checkout.blade.php --}}
@extends('layouts.app', ['title' => 'Checkout'])

@section('content')
<div class="space-y-6">
  <!-- Header -->
  <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-xl md:text-2xl font-semibold">Checkout</h1>
    <a href="{{ route('kasir.home') }}"
       class="inline-flex items-center h-10 px-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 shadow-sm transition">
      ← Kembali ke Katalog
    </a>
  </div>

  <!-- Main Grid -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    {{-- Kolom kiri --}}
    <div class="lg:col-span-8 space-y-6">
      <div id="cart-list" class="rounded-2xl border border-slate-200 bg-white shadow-card overflow-hidden">
        @include('kasir.partials._cart', ['cart' => array_values($cart ?? [])])
      </div>
      <div id="customer-panel" class="rounded-2xl border border-slate-200 bg-white shadow-card overflow-hidden">
        @include('kasir.partials._customer', ['customerId' => $customerId ?? 0, 'selectedCustomer' => $selectedCustomer ?? null, 'customerResults' => $customerResults ?? []])
      </div>
    </div>

    {{-- Kolom kanan --}}
    <div class="lg:col-span-4 space-y-6 lg:sticky lg:top-24 self-start">
      <div id="payments-panel" class="rounded-2xl border border-slate-200 bg-white shadow-card overflow-hidden">
        @include('kasir.partials._payments', ['payments' => $payments ?? []])
      </div>
      <div id="summary-panel" class="rounded-2xl border border-slate-200 bg-white shadow-card overflow-hidden">
        @include('kasir.partials._summary', ['cart' => array_values($cart ?? []), 'total' => $total ?? 0, 'paid' => $paid ?? 0, 'due' => $due ?? 0])
      </div>
    </div>
  </div>
</div>

{{-- ✅ INCLUDE MODAL CUSTOMER --}}
@include('kasir.modals._modal_customer')

{{-- Modal Verifikasi Finalize --}}
<div id="modal-verify" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
  <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all">
    <div class="p-6">
      <div class="flex items-center justify-center mb-6">
        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
          <svg class="w-8 h-8 text-red-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
          </svg>
        </div>
      </div>
      <div class="text-center mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">⚠️ Verifikasi Finalisasi Transaksi</h3>
        <p class="text-red-600 font-medium mb-4">Setelah transaksi diselesaikan, data tidak dapat diubah lagi!</p>
        <p class="text-sm text-gray-500 mb-4">Masukkan kode verifikasi di bawah ini:</p>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
          <div class="text-2xl font-bold text-red-800 tracking-widest" id="verification-code"></div>
        </div>
      </div>
      <div class="mb-6">
        <input type="text" id="verification-input" maxlength="5" class="w-full px-4 py-3 text-center text-xl font-bold border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all duration-200 tracking-widest" placeholder="00000">
        <p id="verification-error" class="text-red-500 text-sm mt-2 hidden">❌ Kode verifikasi tidak cocok!</p>
      </div>
      <div class="flex space-x-3">
        <button id="btn-cancel-verify" class="flex-1 px-4 py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200 font-medium">Batal</button>
        <button id="btn-confirm-verify" class="flex-1 px-4 py-3 bg-red-600 text-white rounded-xl hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all duration-200 font-medium">⚠️ Konfirmasi</button>
      </div>
    </div>
  </div>
</div>

{{-- Form Hidden untuk Finalize --}}
<form id="form-finalize-hidden" class="hidden" method="post" action="{{ route('kasir.finalize') }}">
  @csrf
</form>
@endsection

{{-- ✅ PERBAIKI: Gunakan @section dengan @endsection --}}
@section('scripts')
<script src="{{ asset('js/pos.js') }}"></script>

<script>
function initializeVerificationModal() {
    console.log('Attempting to initialize verification modal...');
    
    const btnVerifyFinalize = document.getElementById('btn-verify-finalize');
    const modal = document.getElementById('modal-verify');
    
    if (!btnVerifyFinalize || !modal) return;
    if (btnVerifyFinalize.hasAttribute('data-modal-initialized')) return;
    
    const codeDisplay = document.getElementById('verification-code');
    const codeInput = document.getElementById('verification-input');
    const errorMsg = document.getElementById('verification-error');
    const btnCancel = document.getElementById('btn-cancel-verify');
    const btnConfirm = document.getElementById('btn-confirm-verify');
    const hiddenForm = document.getElementById('form-finalize-hidden');

    function generateCode() { 
        return Math.floor(10000 + Math.random() * 90000).toString(); 
    }
    
    btnVerifyFinalize.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (this.disabled) {
            console.error('Button is DISABLED. Click ignored.');
            return;
        }
        codeDisplay.textContent = generateCode();
        codeInput.value = '';
        errorMsg.classList.add('hidden');
        modal.classList.remove('hidden');
        setTimeout(() => codeInput.focus(), 100);
        console.log('Modal shown. Code:', codeDisplay.textContent);
    });

    btnConfirm.addEventListener('click', function() {
        if (codeInput.value.trim() === codeDisplay.textContent) {
            modal.classList.add('hidden');
            hiddenForm.submit();
        } else {
            errorMsg.classList.remove('hidden');
            codeInput.focus();
            codeInput.select();
        }
    });

    btnCancel.addEventListener('click', () => modal.classList.add('hidden'));
    modal.addEventListener('click', (e) => { 
        if (e.target === modal) modal.classList.add('hidden'); 
    });
    codeInput.addEventListener('keypress', (e) => { 
        if (e.key === 'Enter') btnConfirm.click(); 
    });
    document.addEventListener('keydown', (e) => { 
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) btnCancel.click(); 
    });

    btnVerifyFinalize.setAttribute('data-modal-initialized', 'true');
    console.log('✅ Verification modal initialized successfully!');
}

// Initialize when DOM ready
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(initializeVerificationModal, 500);
});

// Re-initialize after AJAX updates
document.addEventListener('pos:refreshed', () => {
    console.log('Event pos:refreshed detected! Re-initializing modal...');
    setTimeout(initializeVerificationModal, 50);
});
</script>
@endsection
