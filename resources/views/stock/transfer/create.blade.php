@extends('layouts.dashboard', ['title' => 'Transfer Stok Multi-Item'])

@section('content')
<!-- Loading Overlay -->
<div id="loadingOverlay" class="fixed inset-0 z-50 hidden bg-black/40 backdrop-blur-sm">
  <div class="h-full w-full flex items-center justify-center p-6">
    <div class="rounded-2xl border bg-white shadow-card border-slate-200 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)] px-6 py-5 flex items-center gap-3">
      <div class="h-5 w-5 rounded-full border-2 border-slate-300 border-t-transparent animate-spin"></div>
      <span class="text-sm">Memproses transfer...</span>
    </div>
  </div>
</div>

<!-- Frontend Token Verification Modal -->
<div id="tokenModal" class="fixed inset-0 z-40 hidden bg-black/40 backdrop-blur-sm">
  <div class="h-full w-full flex items-center justify-center p-6">
    <div class="w-full max-w-md rounded-2xl border bg-white shadow-card border-slate-200 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
      <div class="p-6">
        <!-- Modal Header -->
        <div class="flex items-start justify-between mb-4">
          <div class="flex items-start gap-3">
            <div class="h-10 w-10 rounded-lg bg-yellow-100 border border-yellow-200 grid place-items-center dark:bg-yellow-500/20 dark:border-yellow-500/30">
              <svg class="h-5 w-5 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
              </svg>
            </div>
            <div>
              <h3 class="text-lg font-semibold">Verifikasi Transfer</h3>
              <p class="text-sm text-slate-600 dark:text-slate-400">Konfirmasi dengan memasukkan kode verifikasi</p>
            </div>
          </div>
          <button id="closeTokenModal" type="button" class="p-1 rounded-md hover:bg-slate-100 dark:hover:bg-white/10">
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
          </button>
        </div>
        
        <!-- Transfer Summary -->
        <div class="mb-4 p-3 rounded-xl border bg-slate-50 border-slate-200 dark:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
          <h4 class="font-semibold mb-2 text-sm">Ringkasan Transfer:</h4>
          <div class="space-y-1 text-sm">
            <div class="flex justify-between">
              <span>Total Item:</span>
              <span id="modalTotalItems" class="font-semibold">0</span>
            </div>
            <div class="flex justify-between">
              <span>Total Qty:</span>
              <span id="modalTotalQty" class="font-semibold">0</span>
            </div>
            <div class="flex justify-between">
              <span>Cabang:</span>
              <span id="modalBranchInfo" class="font-semibold text-xs">-</span>
            </div>
          </div>
        </div>
        
        <!-- Token Display -->
        <div class="mb-6">
          <div class="rounded-xl border bg-gradient-to-r from-brand/10 to-brandDark/10 border-brand/20 p-4 text-center dark:from-brandDark/20 dark:to-brand/20">
            <p class="text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400 mb-2">Kode Verifikasi (5 Digit)</p>
            <div id="generatedToken" class="text-4xl font-mono font-bold tracking-wider text-brand dark:text-brandDark">00000</div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Masukkan kode di bawah untuk verifikasi</p>
          </div>
        </div>
        
        <!-- Token Input -->
        <div class="mb-6">
          <label class="block text-xs uppercase tracking-wide mb-2 text-slate-600 dark:text-slate-400">Masukkan Kode Verifikasi</label>
          <input type="text" id="tokenInput" maxlength="5" placeholder="00000" 
                 class="w-full text-center text-3xl font-mono font-bold tracking-widest bg-white border-2 border-slate-200 rounded-xl h-16 px-3 focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand/40 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
          <div id="tokenFeedback" class="mt-2 text-center">
            <p id="tokenError" class="hidden text-sm text-rose-500 font-medium">❌ Kode tidak sesuai</p>
            <p id="tokenSuccess" class="hidden text-sm text-emerald-500 font-medium">✅ Kode sesuai!</p>
          </div>
        </div>
        
        <!-- Modal Actions -->
        <div class="flex gap-3">
          <button id="generateNewToken" type="button" 
                  class="flex-1 h-11 px-4 rounded-xl border hover:bg-slate-100 border-slate-200 dark:hover:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
            <span class="flex items-center justify-center gap-2 text-sm">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
              </svg>
              Generate Ulang
            </span>
          </button>
          <button id="verifyAndSubmit" type="button" disabled
                  class="flex-1 h-11 px-4 rounded-xl text-white bg-brand hover:bg-brand/90 border-transparent disabled:opacity-50 disabled:cursor-not-allowed dark:bg-brandDark dark:hover:bg-brandDark/90">
            <span class="flex items-center justify-center gap-2 text-sm">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              Verifikasi & Transfer
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Main Container -->
<div class="space-y-6">
  <!-- Success Toast -->
  @if (session('success'))
  <div id="successToast" class="fixed top-20 right-6 z-40">
    <div class="rounded-xl border bg-emerald-50 border-emerald-200 text-emerald-700 shadow-card dark:bg-emerald-500/15 dark:border-emerald-500/30 dark:text-emerald-200">
      <div class="px-4 py-3 flex items-start gap-3">
        <div class="h-8 w-8 rounded-lg grid place-items-center bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200">
          <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
          </svg>
        </div>
        <div>
          <h4 class="font-semibold">Transfer Berhasil!</h4>
          <p class="text-sm">{{ session('success') }}</p>
        </div>
        <button id="closeSuccessToast" class="ml-4 p-1 rounded-md hover:bg-emerald-100/70 dark:hover:bg-white/10">
          <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
          </svg>
        </button>
      </div>
    </div>
  </div>
  @endif

  <!-- Error Alert -->
  @if ($errors->any())
  <div class="rounded-xl border px-4 py-3 text-sm bg-rose-50 border-rose-200 text-rose-700 dark:bg-rose-500/15 dark:border-rose-500/30 dark:text-rose-200">
    <div class="flex items-start gap-3">
      <div class="mt-0.5">
        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
      </div>
      <div>
        <h4 class="font-semibold">Terjadi Kesalahan</h4>
        @foreach ($errors->all() as $error)
          <p>{{ $error }}</p>
        @endforeach
      </div>
    </div>
  </div>
  @endif

  <!-- Header Section -->
  <div class="flex items-center justify-between">
    <div class="flex items-start gap-3">
      <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-brand/20 to-brandDark/20 border border-brand/20 grid place-items-center dark:from-brandDark/20 dark:to-brand/20">
        <svg class="h-5 w-5 text-brand dark:text-brandDark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m0-4l-4-4"/>
        </svg>
      </div>
      <div>
        <h1 class="text-xl md:text-2xl font-semibold">Transfer Stok Multi-Item</h1>
        <p class="text-slate-600 dark:text-slate-400">Transfer banyak barang sekaligus dengan stok real-time di dropdown</p>
      </div>
    </div>
    <a href="{{ url()->previous() }}" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border hover:bg-slate-100 border-slate-200 dark:hover:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
      </svg>
      <span>Kembali</span>
    </a>
  </div>

  <!-- Main Form Card -->
  <div class="rounded-2xl border bg-white shadow-card border-slate-200 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
    <div class="p-6 md:p-7">
      <!-- Form Header -->
      <div class="flex items-start justify-between mb-6">
        <div class="flex items-start gap-3">
          <div class="h-10 w-10 rounded-lg bg-slate-100 border border-slate-200 grid place-items-center dark:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
          </div>
          <div>
            <h2 class="text-base font-semibold">Formulir Transfer Multi-Item</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">Stok ditampilkan real-time di dropdown produk</p>
          </div>
        </div>
        <div>
          <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs bg-gradient-to-r from-brand/10 to-brandDark/10 border border-brand/20 text-brand dark:text-brandDark">
            REF: TRF{{ date('ymdHis') }}
          </span>
        </div>
      </div>

      <div class="h-px w-full bg-slate-200/70 dark:bg-white/10"></div>

      <!-- Form Content -->
      <div class="mt-6">
        <form method="post" action="{{ route('stock.transfer.store') }}" id="transferForm" class="space-y-8">
          @csrf

          <!-- Step 1: Branch Selection -->
          <div class="space-y-4">
            <div class="flex items-center gap-3">
              <span class="h-7 w-7 rounded-full grid place-items-center text-xs font-semibold bg-brand text-white">1</span>
              <h3 class="font-semibold">Pilih Cabang Transfer</h3>
            </div>
            
            <div class="grid md:grid-cols-[1fr_auto_1fr] items-end gap-4">
              <!-- Branch From -->
              <div>
                <label class="block text-xs uppercase tracking-wide mb-2 text-slate-600 dark:text-slate-400">
                  <span class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1"/>
                    </svg>
                    Cabang Asal
                  </span>
                </label>
                <div class="relative">
                  <select name="branch_from_id" id="branchFromSelect" required
                          class="w-full appearance-none bg-white border border-slate-200 rounded-xl h-11 px-3 pr-9 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                    <option value="" disabled {{ old('branch_from_id') ? '' : 'selected' }}>Pilih cabang asal</option>
                    @foreach ($branches as $branch)
                      <option value="{{ $branch->id }}" @selected(old('branch_from_id') == $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                  </select>
                  <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                  </div>
                </div>
                @error('branch_from_id')
                  <p class="mt-1 text-sm text-rose-500">{{ $message }}</p>
                @enderror
              </div>

              <!-- Transfer Arrow -->
              <div class="hidden md:flex items-center justify-center">
                <div class="h-10 w-10 rounded-lg bg-gradient-to-r from-brand/20 to-brandDark/20 border border-brand/20 grid place-items-center">
                  <svg class="h-5 w-5 text-brand dark:text-brandDark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                  </svg>
                </div>
              </div>

              <!-- Branch To -->
              <div>
                <label class="block text-xs uppercase tracking-wide mb-2 text-slate-600 dark:text-slate-400">
                  <span class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1"/>
                    </svg>
                    Cabang Tujuan
                  </span>
                </label>
                <div class="relative">
                  <select name="branch_to_id" id="branchToSelect" required
                          class="w-full appearance-none bg-white border border-slate-200 rounded-xl h-11 px-3 pr-9 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                    <option value="" disabled {{ old('branch_to_id') ? '' : 'selected' }}>Pilih cabang tujuan</option>
                    @foreach ($branches as $branch)
                      <option value="{{ $branch->id }}" @selected(old('branch_to_id') == $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                  </select>
                  <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                  </div>
                </div>
                @error('branch_to_id')
                  <p class="mt-1 text-sm text-rose-500">{{ $message }}</p>
                @enderror
              </div>
            </div>
          </div>

          <!-- Step 2: Items Selection -->
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <span class="h-7 w-7 rounded-full grid place-items-center text-xs font-semibold bg-brand text-white">2</span>
                <h3 class="font-semibold">Detail Barang Transfer</h3>
                <div id="loadingProducts" class="hidden flex items-center gap-2 text-sm text-slate-600">
                  <div class="h-4 w-4 rounded-full border-2 border-slate-300 border-t-transparent animate-spin"></div>
                  <span>Memuat produk...</span>
                </div>
              </div>
              <button type="button" id="addItemBtn" disabled
                      class="inline-flex items-center gap-2 h-9 px-3 rounded-lg border text-sm hover:bg-brand hover:text-white hover:border-brand transition-colors border-slate-200 disabled:opacity-50 disabled:cursor-not-allowed dark:hover:bg-brandDark dark:border-[rgba(148,163,184,.12)]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Tambah Barang
              </button>
            </div>

            <!-- Items Container -->
            <div id="itemsContainer" class="space-y-4">
              <!-- Item Row Template akan diinsert di sini -->
            </div>

            <!-- Summary Box -->
            <div id="transferSummary" class="hidden rounded-xl border bg-gradient-to-r from-brand/5 to-brandDark/5 border-brand/20 p-4">
              <div class="flex items-center justify-between font-medium">
                <div class="flex items-center gap-4 text-sm">
                  <div class="flex items-center gap-2">
                    <span class="text-slate-600 dark:text-slate-400">Total Items:</span>
                    <span id="totalItems" class="font-bold text-brand dark:text-brandDark">0</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="text-slate-600 dark:text-slate-400">Total Quantity:</span>
                    <span id="totalQty" class="font-bold text-brand dark:text-brandDark">0</span>
                  </div>
                </div>
                <div class="text-xs text-slate-500">
                  <span id="itemsList"></span>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 3: Notes -->
          <div class="space-y-4">
            <div class="flex items-center gap-3">
              <span class="h-7 w-7 rounded-full grid place-items-center text-xs font-semibold bg-brand text-white">3</span>
              <h3 class="font-semibold">Catatan Transfer</h3>
              <span class="text-xs text-slate-500 dark:text-slate-400">Opsional</span>
            </div>
            
            <div>
              <label class="block text-xs uppercase tracking-wide mb-2 text-slate-600 dark:text-slate-400">
                <span class="inline-flex items-center gap-2">
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                  Keterangan
                </span>
              </label>
              <textarea name="notes" rows="4" placeholder="Catatan transfer (opsional)... Contoh: Mutasi stock opname, Penyesuaian inventory, dll."
                        class="w-full bg-white border border-slate-200 rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">{{ old('notes') }}</textarea>
            </div>
          </div>

          <!-- Submit Section -->
          <div class="pt-4 border-t border-slate-200 dark:border-white/10">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
              <div class="text-sm inline-flex items-start gap-2 text-slate-600 dark:text-slate-400">
                <svg class="h-4 w-4 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <span>Transfer memerlukan verifikasi kode 5 digit sebelum diproses</span>
              </div>
              <button type="button" id="submitTransferBtn" disabled
                      class="inline-flex items-center gap-3 h-12 px-6 rounded-xl border text-white bg-gradient-to-r from-brand to-brandDark hover:from-brand/90 hover:to-brandDark/90 border-transparent disabled:opacity-50 disabled:cursor-not-allowed disabled:from-slate-300 disabled:to-slate-400">
                <div class="inline-flex items-center gap-2">
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                  </svg>
                  <span>Proses Transfer Multi-Item</span>
                </div>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Item Row Template (Hidden) -->
<template id="itemRowTemplate">
  <div class="item-row rounded-xl border bg-white border-slate-200 p-4 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)] transition-all hover:shadow-md">
    <div class="flex items-start justify-between mb-3">
      <div class="flex items-center gap-2">
        <span class="item-number h-6 w-6 rounded-full grid place-items-center text-xs font-semibold bg-gradient-to-r from-brand to-brandDark text-white">1</span>
        <h4 class="font-medium">Item #<span class="item-number-text">1</span></h4>
      </div>
      <button type="button" class="remove-item-btn p-1.5 rounded-lg hover:bg-rose-100 text-rose-600 dark:hover:bg-rose-500/20 transition-colors">
        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
        </svg>
      </button>
    </div>
    
    <div class="grid md:grid-cols-2 gap-4">
      <!-- Product Selection -->
      <div>
        <label class="block text-xs uppercase tracking-wide mb-2 text-slate-600 dark:text-slate-400">Produk</label>
        <div class="relative">
          <select class="product-select w-full appearance-none bg-white border border-slate-200 rounded-xl h-11 px-3 pr-9 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]" required>
            <option value="" disabled selected>Pilih cabang asal dulu</option>
          </select>
          <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </div>
        </div>
      </div>
      
      <!-- Quantity -->
      <div>
        <label class="block text-xs uppercase tracking-wide mb-2 text-slate-600 dark:text-slate-400">Jumlah Transfer</label>
        <div class="relative">
          <input type="number" class="qty-input w-full bg-white border border-slate-200 rounded-xl h-11 px-3 pr-12 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]"
                 step="0.01" min="0.01" placeholder="0.00" required>
          <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 dark:text-slate-400">
            <span class="uom-text text-xs font-medium">pcs</span>
          </div>
        </div>
        <!-- Validation Message -->
        <p class="validation-message hidden mt-1 text-sm text-rose-500 font-medium"></p>
      </div>
    </div>
  </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Global variables
    let itemCounter = 0;
    let generatedToken = '';
    let availableProducts = []; // Store products with stock
    
    // DOM elements
    const transferForm = document.getElementById('transferForm');
    const branchFromSelect = document.getElementById('branchFromSelect');
    const branchToSelect = document.getElementById('branchToSelect');
    const itemsContainer = document.getElementById('itemsContainer');
    const addItemBtn = document.getElementById('addItemBtn');
    const submitTransferBtn = document.getElementById('submitTransferBtn');
    const tokenModal = document.getElementById('tokenModal');
    const generateNewTokenBtn = document.getElementById('generateNewToken');
    const verifyAndSubmitBtn = document.getElementById('verifyAndSubmit');
    const tokenInput = document.getElementById('tokenInput');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const loadingProducts = document.getElementById('loadingProducts');
    
    // Close modals and toasts
    document.getElementById('closeTokenModal')?.addEventListener('click', () => {
        tokenModal.classList.add('hidden');
    });
    
    document.getElementById('closeSuccessToast')?.addEventListener('click', () => {
        document.getElementById('successToast')?.remove();
    });
    
    // Auto hide toast
    setTimeout(() => document.getElementById('successToast')?.remove(), 5000);
    
    // Add first item on load (disabled until branch selected)
    addItemRow();
    
    // Add item button
    addItemBtn.addEventListener('click', addItemRow);
    
    // Branch From change - LOAD PRODUCTS WITH STOCK
    branchFromSelect.addEventListener('change', function() {
        loadProductsWithStock(this.value);
        updateSubmitButton();
    });
    
    // Branch To change
    branchToSelect.addEventListener('change', updateSubmitButton);
    
    // Submit button - show token modal
    submitTransferBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            return;
        }
        
        updateModalSummary();
        tokenModal.classList.remove('hidden');
        generateFrontendToken();
    });
    
    // Generate new token
    generateNewTokenBtn.addEventListener('click', generateFrontendToken);
    
    // Verify and submit
    verifyAndSubmitBtn.addEventListener('click', function() {
        const inputToken = tokenInput.value.trim();
        
        if (inputToken !== generatedToken) {
            showTokenError();
            return;
        }
        
        tokenModal.classList.add('hidden');
        loadingOverlay.classList.remove('hidden');
        transferForm.submit();
    });
    
    // Token input validation
    tokenInput.addEventListener('input', function() {
        const currentValue = this.value.trim();
        const tokenError = document.getElementById('tokenError');
        const tokenSuccess = document.getElementById('tokenSuccess');
        
        tokenError.classList.add('hidden');
        tokenSuccess.classList.add('hidden');
        tokenInput.classList.remove('border-rose-300', 'border-emerald-300');
        
        verifyAndSubmitBtn.disabled = currentValue.length !== 5;
        
        if (currentValue.length === 5) {
            if (currentValue === generatedToken) {
                tokenSuccess.classList.remove('hidden');
                tokenInput.classList.add('border-emerald-300');
                verifyAndSubmitBtn.disabled = false;
            } else {
                tokenError.classList.remove('hidden');
                tokenInput.classList.add('border-rose-300');
                verifyAndSubmitBtn.disabled = true;
            }
        }
    });
    
    // LOAD PRODUCTS WITH STOCK VIA AJAX
function loadProductsWithStock(branchId) {
    if (!branchId) {
        availableProducts = [];
        updateAllProductDropdowns();
        addItemBtn.disabled = true;
        return;
    }
    
    // Show loading
    loadingProducts.classList.remove('hidden');
    addItemBtn.disabled = true;
    
    // Clear existing product dropdowns
    availableProducts = [];
    updateAllProductDropdowns();
    
    // ✅ GANTI: Ambil data dari window object (server-side data)
    const branchProducts = window.productsByBranch[branchId] || [];
    
    console.log('✅ Products loaded from server-side data:', branchProducts);
    
    // Store products with stock info
    availableProducts = branchProducts;
    
    // Update all product dropdowns
    updateAllProductDropdowns();
    
    // Enable add item button
    addItemBtn.disabled = false;
    
    // Hide loading
    loadingProducts.classList.add('hidden');
}

    
    // UPDATE ALL PRODUCT DROPDOWNS WITH STOCK
    function updateAllProductDropdowns() {
        const productSelects = document.querySelectorAll('.product-select');
        
        productSelects.forEach(select => {
            const currentValue = select.value;
            
            // Clear options
            select.innerHTML = '';
            
            if (availableProducts.length === 0) {
                select.innerHTML = '<option value="" disabled selected>Pilih cabang asal dulu</option>';
                select.disabled = true;
                return;
            }
            
            // Add default option
            select.innerHTML = '<option value="" disabled selected>Pilih produk</option>';
            select.disabled = false;
            
            // Add product options with stock display - FORMAT: [SKU] Name (UOM) (Stock)
            availableProducts.forEach(product => {
                const option = document.createElement('option');
                option.value = product.id;
                option.textContent = product.display_text; // Already formatted from controller
                option.dataset.sku = product.sku;
                option.dataset.name = product.name;
                option.dataset.uom = product.uom;
                option.dataset.stock = product.stock;
                
                select.appendChild(option);
                
                // Restore previous selection if applicable
                if (currentValue == product.id) {
                    select.value = currentValue;
                }
            });
        });
    }
    
    function addItemRow() {
        itemCounter++;
        const template = document.getElementById('itemRowTemplate');
        const clone = template.content.cloneNode(true);
        
        // Update item number
        const itemNumbers = clone.querySelectorAll('.item-number, .item-number-text');
        itemNumbers.forEach(el => {
            el.textContent = itemCounter;
        });
        
        // Set form names
        const productSelect = clone.querySelector('.product-select');
        const qtyInput = clone.querySelector('.qty-input');
        
        productSelect.name = `items[${itemCounter-1}][product_id]`;
        qtyInput.name = `items[${itemCounter-1}][qty]`;
        
        // Add event listeners
        const removeBtn = clone.querySelector('.remove-item-btn');
        removeBtn.addEventListener('click', function() {
            removeItemRow(this.closest('.item-row'));
        });
        
        productSelect.addEventListener('change', function() {
            handleProductChange(this);
        });
        
        qtyInput.addEventListener('input', function() {
            validateQuantity(this);
            updateSummary();
            updateSubmitButton();
        });
        
        itemsContainer.appendChild(clone);
        
        // Update dropdown with available products
        updateAllProductDropdowns();
        
        updateSummary();
        updateSubmitButton();
        
        // Disable add button if max items reached
        if (itemCounter >= 25) {
            addItemBtn.disabled = true;
            addItemBtn.textContent = 'Max 25 items';
        }
    }
    
    function removeItemRow(row) {
        if (document.querySelectorAll('.item-row').length <= 1) {
            alert('❌ Minimal harus ada 1 item untuk transfer');
            return;
        }
        
        row.remove();
        updateItemNumbers();
        updateSummary();
        updateSubmitButton();
        
        // Re-enable add button
        addItemBtn.disabled = !branchFromSelect.value;
        addItemBtn.innerHTML = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg> Tambah Barang';
    }
    
    function updateItemNumbers() {
        const rows = document.querySelectorAll('.item-row');
        itemCounter = rows.length;
        
        rows.forEach((row, index) => {
            const numbers = row.querySelectorAll('.item-number, .item-number-text');
            numbers.forEach(el => {
                el.textContent = index + 1;
            });
            
            // Update form names
            const productSelect = row.querySelector('.product-select');
            const qtyInput = row.querySelector('.qty-input');
            productSelect.name = `items[${index}][product_id]`;
            qtyInput.name = `items[${index}][qty]`;
        });
    }
    
    function handleProductChange(select) {
        const row = select.closest('.item-row');
        const uomText = row.querySelector('.uom-text');
        
        if (!select.value) {
            updateSubmitButton();
            return;
        }
        
        // Get product info from selected option (stock already available)
        const selectedOption = select.options[select.selectedIndex];
        const uom = selectedOption.dataset.uom || 'pcs';
        const stock = parseFloat(selectedOption.dataset.stock || 0);
        
        // Update UOM display
        uomText.textContent = uom;
        
        // Store stock for validation (no need for additional AJAX call)
        select.dataset.availableStock = stock;
        
        // Validate current quantity
        const qtyInput = row.querySelector('.qty-input');
        if (qtyInput.value) {
            validateQuantity(qtyInput);
        }
        
        updateSubmitButton();
    }
    
    function validateQuantity(input) {
        const row = input.closest('.item-row');
        const productSelect = row.querySelector('.product-select');
        const validationMessage = row.querySelector('.validation-message');
        const availableStock = parseFloat(productSelect.dataset.availableStock || 0);
        const requestedQty = parseFloat(input.value || 0);
        
        validationMessage.classList.add('hidden');
        input.classList.remove('border-rose-300');
        
        if (requestedQty > availableStock) {
            validationMessage.textContent = `❌ Jumlah melebihi stok tersedia (${availableStock.toFixed(2)})`;
            validationMessage.classList.remove('hidden');
            input.classList.add('border-rose-300');
            return false;
        }
        
        return true;
    }
    
    function validateForm() {
        // Check branches
        if (!branchFromSelect.value) {
            alert('❌ Pilih cabang asal terlebih dahulu');
            branchFromSelect.focus();
            return false;
        }
        
        if (!branchToSelect.value) {
            alert('❌ Pilih cabang tujuan terlebih dahulu');
            branchToSelect.focus();
            return false;
        }
        
        if (branchFromSelect.value === branchToSelect.value) {
            alert('❌ Cabang asal dan tujuan tidak boleh sama');
            branchToSelect.focus();
            return false;
        }
        
        // Check items
        const rows = document.querySelectorAll('.item-row');
        const selectedProducts = new Set();
        
        for (let row of rows) {
            const productSelect = row.querySelector('.product-select');
            const qtyInput = row.querySelector('.qty-input');
            
            if (!productSelect.value) {
                alert('❌ Pilih produk untuk semua item');
                productSelect.focus();
                return false;
            }
            
            if (selectedProducts.has(productSelect.value)) {
                alert('❌ Produk tidak boleh duplikat. Gunakan 1 baris per produk.');
                productSelect.focus();
                return false;
            }
            selectedProducts.add(productSelect.value);
            
            if (!qtyInput.value || parseFloat(qtyInput.value) <= 0) {
                alert('❌ Isi jumlah yang valid untuk semua item');
                qtyInput.focus();
                return false;
            }
            
            if (!validateQuantity(qtyInput)) {
                alert('❌ Ada item dengan jumlah melebihi stok tersedia');
                qtyInput.focus();
                return false;
            }
        }
        
        return true;
    }
    
    function updateSummary() {
        const rows = document.querySelectorAll('.item-row');
        const summary = document.getElementById('transferSummary');
        const totalItemsEl = document.getElementById('totalItems');
        const totalQtyEl = document.getElementById('totalQty');
        const itemsListEl = document.getElementById('itemsList');
        
        let totalItems = 0;
        let totalQty = 0;
        let itemNames = [];
        
        rows.forEach(row => {
            const productSelect = row.querySelector('.product-select');
            const qtyInput = row.querySelector('.qty-input');
            
            if (productSelect.value && qtyInput.value && parseFloat(qtyInput.value) > 0) {
                totalItems++;
                totalQty += parseFloat(qtyInput.value);
                
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                const sku = selectedOption.dataset.sku;
                if (sku) {
                    itemNames.push(sku);
                }
            }
        });
        
        totalItemsEl.textContent = totalItems;
        totalQtyEl.textContent = totalQty.toFixed(2);
        itemsListEl.textContent = itemNames.length > 0 ? itemNames.slice(0, 3).join(', ') + (itemNames.length > 3 ? '...' : '') : '';
        
        if (totalItems > 0) {
            summary.classList.remove('hidden');
        } else {
            summary.classList.add('hidden');
        }
    }
    
    function updateSubmitButton() {
        const rows = document.querySelectorAll('.item-row');
        let isValid = branchFromSelect.value && branchToSelect.value && branchFromSelect.value !== branchToSelect.value;
        
        if (isValid) {
            for (let row of rows) {
                const productSelect = row.querySelector('.product-select');
                const qtyInput = row.querySelector('.qty-input');
                
                if (!productSelect.value || !qtyInput.value || parseFloat(qtyInput.value) <= 0) {
                    isValid = false;
                    break;
                }
                
                if (!validateQuantity(qtyInput)) {
                    isValid = false;
                    break;
                }
            }
        }
        
        submitTransferBtn.disabled = !isValid;
    }
    
    function generateFrontendToken() {
        // Generate 5 digit random number (frontend only)
        generatedToken = String(Math.floor(Math.random() * 90000) + 10000);
        document.getElementById('generatedToken').textContent = generatedToken;
        
        // Clear token input
        tokenInput.value = '';
        tokenInput.focus();
        
        // Reset feedback
        document.getElementById('tokenError').classList.add('hidden');
        document.getElementById('tokenSuccess').classList.add('hidden');
        tokenInput.classList.remove('border-rose-300', 'border-emerald-300');
        verifyAndSubmitBtn.disabled = true;
    }
    
    function showTokenError() {
        const tokenError = document.getElementById('tokenError');
        const tokenSuccess = document.getElementById('tokenSuccess');
        
        tokenError.classList.remove('hidden');
        tokenSuccess.classList.add('hidden');
        tokenInput.classList.add('border-rose-300');
        tokenInput.classList.remove('border-emerald-300');
        
        // Shake animation
        tokenInput.animate([
            { transform: 'translateX(0)' },
            { transform: 'translateX(-10px)' },
            { transform: 'translateX(10px)' },
            { transform: 'translateX(-10px)' },
            { transform: 'translateX(0)' }
        ], {
            duration: 500,
            easing: 'ease-in-out'
        });
    }
    
    function updateModalSummary() {
        const rows = document.querySelectorAll('.item-row');
        let totalItems = 0;
        let totalQty = 0;
        
        rows.forEach(row => {
            const productSelect = row.querySelector('.product-select');
            const qtyInput = row.querySelector('.qty-input');
            
            if (productSelect.value && qtyInput.value && parseFloat(qtyInput.value) > 0) {
                totalItems++;
                totalQty += parseFloat(qtyInput.value);
            }
        });
        
        document.getElementById('modalTotalItems').textContent = totalItems;
        document.getElementById('modalTotalQty').textContent = totalQty.toFixed(2);
        
        const branchFrom = branchFromSelect.options[branchFromSelect.selectedIndex]?.text || '';
        const branchTo = branchToSelect.options[branchToSelect.selectedIndex]?.text || '';
        document.getElementById('modalBranchInfo').textContent = `${branchFrom} → ${branchTo}`;
    }
});
</script>
<script>
// ✅ Embed data dari server ke JavaScript
window.productsByBranch = @json($productsByBranch);
window.branches = @json($branches);

console.log('📦 Products by branch loaded:', window.productsByBranch);
</script>

@endsection
