@extends('layouts.app', ['title' => 'Kas Kasir'])

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

  {{-- Filter Periode --}}
  <form method="get" class="bg-white rounded-xl shadow p-4 grid grid-cols-1 md:grid-cols-5 gap-3">
    <div class="md:col-span-2">
      <label class="block text-xs text-gray-500 mb-1">Cabang</label>
      <input type="text" disabled
             value="ID Cabang: {{ $branchId }}"
             class="w-full rounded-lg border-gray-300 bg-gray-50">
    </div>
    <div>
      <label class="block text-xs text-gray-500 mb-1">Dari</label>
      <input type="date" name="start_date" value="{{ $start }}" class="w-full rounded-lg border-gray-300">
    </div>
    <div>
      <label class="block text-xs text-gray-500 mb-1">Sampai</label>
      <input type="date" name="end_date" value="{{ $end }}" class="w-full rounded-lg border-gray-300">
    </div>
    <div class="flex items-end">
      <button class="px-4 py-2 rounded-lg bg-gray-900 text-white w-full">Terapkan</button>
    </div>
  </form>

  {{-- Summary --}}
  <div id="summary-panel">
    @include('kasir.cash._summary', ['summary' => $summary, 'branchId' => $branchId, 'start'=>$start, 'end'=>$end])
  </div>

  {{-- Form Input --}}
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    @include('kasir.cash._form_out', ['branchId' => $branchId, 'start'=>$start, 'end'=>$end])
    @include('kasir.cash._form_adjust', ['branchId' => $branchId, 'start'=>$start, 'end'=>$end])
  </div>

  {{-- Tabel Mutasi --}}
  <div id="table-panel">
    @include('kasir.cash._table', ['moves' => $moves])
  </div>
</div>

{{-- Token Verification Modal --}}
<div id="token-modal" class="token-modal">
  <div class="token-modal-backdrop"></div>
  <div class="token-modal-content">
    <div class="token-modal-header">
      <h3 class="token-modal-title">Verifikasi Keamanan</h3>
      <button type="button" class="token-modal-close" onclick="closeTokenModal()">&times;</button>
    </div>
    
    <div class="token-modal-body">
      <p class="token-instruction">Masukkan kode verifikasi 5 digit berikut:</p>
      
      <div class="token-display">
        <span id="verification-token">00000</span>
      </div>
      
      <div class="token-input-group">
        <label class="token-label">Kode Verifikasi:</label>
        <input type="text" 
               id="token-input" 
               class="token-input" 
               placeholder="00000"
               maxlength="5"
               pattern="[0-9]{5}"
               autocomplete="off">
      </div>
      
      <div class="token-error" id="token-error" style="display: none;">
        Kode verifikasi tidak sesuai!
      </div>
    </div>
    
    <div class="token-modal-footer">
      <button type="button" class="token-btn-cancel" onclick="closeTokenModal()">Batal</button>
      <button type="button" class="token-btn-verify" onclick="verifyToken()">Verifikasi</button>
      <button type="button" class="token-btn-regenerate" onclick="regenerateToken()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
          <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
        </svg>
        Generate Ulang
      </button>
    </div>
  </div>
</div>

<style>
/* Token Modal Styles */
.token-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.7);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  backdrop-filter: blur(4px);
  animation: fadeIn 0.3s ease-in-out;
}

.token-modal.active {
  display: flex;
}

.token-modal-backdrop {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  cursor: pointer;
}

.token-modal-content {
  background: white;
  border-radius: 16px;
  width: 90%;
  max-width: 450px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  transform: scale(0.95);
  animation: modalAppear 0.3s ease-out forwards;
  position: relative;
  overflow: hidden;
}

.token-modal-header {
  padding: 24px 24px 16px;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.token-modal-title {
  font-size: 20px;
  font-weight: 600;
  margin: 0;
}

.token-modal-close {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  color: white;
  font-size: 20px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.2s;
}

.token-modal-close:hover {
  background: rgba(255, 255, 255, 0.3);
}

.token-modal-body {
  padding: 32px 24px;
  text-align: center;
}

.token-instruction {
  font-size: 16px;
  color: #6b7280;
  margin-bottom: 24px;
  line-height: 1.5;
}

.token-display {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 20px;
  border-radius: 12px;
  margin-bottom: 24px;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.token-display span {
  font-size: 36px;
  font-weight: bold;
  font-family: 'Monaco', 'Menlo', monospace;
  letter-spacing: 8px;
  display: block;
}

.token-input-group {
  margin-bottom: 20px;
}

.token-label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
  margin-bottom: 8px;
  text-align: left;
}

.token-input {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #d1d5db;
  border-radius: 8px;
  font-size: 18px;
  font-family: 'Monaco', 'Menlo', monospace;
  letter-spacing: 4px;
  text-align: center;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.token-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.token-input.error {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.token-error {
  background: #fef2f2;
  color: #dc2626;
  padding: 12px;
  border-radius: 8px;
  font-size: 14px;
  margin-top: 12px;
  border: 1px solid #fecaca;
}

.token-modal-footer {
  padding: 16px 24px;
  background: #f9fafb;
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  flex-wrap: wrap;
}

.token-btn-cancel {
  padding: 10px 20px;
  border: 2px solid #d1d5db;
  background: white;
  color: #6b7280;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.token-btn-cancel:hover {
  background: #f3f4f6;
  border-color: #9ca3af;
}

.token-btn-verify {
  padding: 10px 24px;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}

.token-btn-verify:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}

.token-btn-verify:active {
  transform: translateY(0);
}

.token-btn-regenerate {
  padding: 10px 16px;
  background: #f59e0b;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s;
  font-size: 14px;
}

.token-btn-regenerate:hover {
  background: #d97706;
  transform: translateY(-1px);
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes modalAppear {
  from { 
    transform: scale(0.95) translateY(-20px);
    opacity: 0;
  }
  to { 
    transform: scale(1) translateY(0);
    opacity: 1;
  }
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-5px); }
  75% { transform: translateX(5px); }
}

.token-modal-content.shake {
  animation: shake 0.5s ease-in-out;
}

/* Responsive Design */
@media (max-width: 640px) {
  .token-modal-content {
    width: 95%;
    margin: 20px;
  }
  
  .token-modal-footer {
    flex-direction: column;
  }
  
  .token-btn-cancel,
  .token-btn-verify,
  .token-btn-regenerate {
    width: 100%;
    justify-content: center;
  }
  
  .token-display span {
    font-size: 28px;
    letter-spacing: 6px;
  }
}
</style>
@endsection

@push('scripts')
  <script src="{{ asset('js/cash.js') }}" defer></script>
@endpush
