@extends('layouts.dashboard', ['title' => 'Transfer Stok'])

@section('content')
<!-- Loading Overlay -->
<div id="loadingOverlay"
     class="fixed inset-0 z-50 hidden bg-black/40 backdrop-blur-sm">
  <div class="h-full w-full flex items-center justify-center p-6">
    <div class="rounded-2xl border bg-white shadow-card border-slate-200
                dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)] px-6 py-5 flex items-center gap-3">
      <div class="h-5 w-5 rounded-full border-2 border-slate-300 border-t-transparent animate-spin"></div>
      <span class="text-sm">Memproses transfer...</span>
    </div>
  </div>
</div>

<!-- Main Container -->
<div class="space-y-6">
  <!-- Success Toast -->
  @if (session('success'))
  <div id="successToast"
       class="fixed top-20 right-6 z-40">
    <div class="rounded-xl border bg-emerald-50 border-emerald-200 text-emerald-700 shadow-card
                dark:bg-emerald-500/15 dark:border-emerald-500/30 dark:text-emerald-200">
      <div class="px-4 py-3 flex items-start gap-3">
        <div class="h-8 w-8 rounded-lg grid place-items-center bg-emerald-100 text-emerald-700
                    dark:bg-emerald-500/20 dark:text-emerald-200">
          <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
          </svg>
        </div>
        <div>
          <h4 class="font-semibold">Transfer Berhasil!</h4>
          <p class="text-sm">{{ session('success') }}</p>
        </div>
        <button id="closeSuccessToast"
                class="ml-4 p-1 rounded-md hover:bg-emerald-100/70 dark:hover:bg-white/10">
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
  <div class="rounded-xl border px-4 py-3 text-sm bg-rose-50 border-rose-200 text-rose-700
              dark:bg-rose-500/15 dark:border-rose-500/30 dark:text-rose-200">
    <div class="flex items-start gap-3">
      <div class="mt-0.5">
        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
      </div>
      <div>
        <h4 class="font-semibold">Terjadi Kesalahan</h4>
        <p>{{ $errors->first() }}</p>
      </div>
    </div>
  </div>
  @endif

  <!-- Header Section -->
  <div class="flex items-center justify-between">
    <div class="flex items-start gap-3">
      <div class="h-10 w-10 rounded-lg bg-slate-100 border border-slate-200 grid place-items-center
                  dark:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m0-4l-4-4"/>
        </svg>
      </div>
      <div>
        <h1 class="text-xl md:text-2xl font-semibold">Transfer Stok</h1>
        <p class="text-slate-600 dark:text-slate-400">Kelola perpindahan stok antar cabang</p>
      </div>
    </div>
    <a href="{{ url()->previous() }}"
       class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border hover:bg-slate-100 border-slate-200
              dark:hover:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
      </svg>
      <span>Kembali</span>
    </a>
  </div>

  <!-- Main Form Card -->
  <div class="rounded-2xl border bg-white shadow-card border-slate-200
              dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
    <div class="p-6 md:p-7">

      <!-- Card Header -->
      <div class="flex items-start justify-between mb-6">
        <div class="flex items-start gap-3">
          <div class="h-10 w-10 rounded-lg bg-slate-100 border border-slate-200 grid place-items-center
                      dark:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
          </div>
          <div>
            <h2 class="text-base font-semibold">Formulir Transfer</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">Isi data transfer dengan lengkap dan benar</p>
          </div>
        </div>
        <div>
          <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs bg-slate-100 border border-slate-200
                        dark:bg-white/5 dark:border-[rgba(148,163,184,.12)]">REF: TRANSFER-{{ date('His') }}</span>
        </div>
      </div>

      <!-- Decorative Elements -->
      <div class="h-px w-full bg-slate-200/70 dark:bg-white/10"></div>
      <div class="sr-only"></div>

      <!-- Form Content -->
      <div class="mt-6">
        <form method="post" action="{{ route('stock.transfer.store') }}" id="transferForm" class="space-y-8">
          @csrf

          <!-- Branch Selection Section -->
          <div class="space-y-4">
            <div class="flex items-center gap-3">
              <span class="h-7 w-7 rounded-full grid place-items-center text-xs font-semibold bg-slate-100 border border-slate-200
                            dark:bg-white/5 dark:border-[rgba(148,163,184,.12)]">1</span>
              <h3 class="font-semibold">Pilih Cabang</h3>
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
                  <select name="branch_from_id" required
                          class="w-full appearance-none bg-white border border-slate-200 rounded-xl h-11 px-3 pr-9 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                    <option value="" disabled {{ old('branch_from_id') ? '' : 'selected' }}>Pilih cabang asal</option>
                    @foreach ($branches as $br)
                      <option value="{{ $br->id }}" @selected(old('branch_from_id') == $br->id)>{{ $br->name }}</option>
                    @endforeach
                  </select>
                  <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                  </div>
                </div>
                @error('branch_from_id')
                  <p class="mt-1 text-sm text-rose-500 flex items-start gap-1">
                    <svg class="h-4 w-4 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ $message }}</span>
                  </p>
                @enderror
              </div>

              <!-- Transfer Arrow -->
              <div class="hidden md:flex items-center justify-center">
                <div class="h-10 w-10 rounded-lg bg-slate-100 border border-slate-200 grid place-items-center
                            dark:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                  </svg>
                </div>
              </div>

              <!-- Branch To -->
              <div>
                <label class="block text-xs uppercase tracking-wide mb-2 text-slate-600 dark:text-slate-400">
                  <span class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2-2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1"/>
                    </svg>
                    Cabang Tujuan
                  </span>
                </label>
                <div class="relative">
                  <select name="branch_to_id" required
                          class="w-full appearance-none bg-white border border-slate-200 rounded-xl h-11 px-3 pr-9 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                    <option value="" disabled {{ old('branch_to_id') ? '' : 'selected' }}>Pilih cabang tujuan</option>
                    @foreach ($branches as $br)
                      <option value="{{ $br->id }}" @selected(old('branch_to_id') == $br->id)>{{ $br->name }}</option>
                    @endforeach
                  </select>
                  <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                  </div>
                </div>
                @error('branch_to_id')
                  <p class="mt-1 text-sm text-rose-500 flex items-start gap-1">
                    <svg class="h-4 w-4 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ $message }}</span>
                  </p>
                @enderror
              </div>
            </div>
          </div>

          <!-- Product Selection Section -->
          <div class="space-y-4">
            <div class="flex items-center gap-3">
              <span class="h-7 w-7 rounded-full grid place-items-center text-xs font-semibold bg-slate-100 border border-slate-200
                            dark:bg-white/5 dark:border-[rgba(148,163,184,.12)]">2</span>
              <h3 class="font-semibold">Detail Produk</h3>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
              <!-- Product Selection -->
              <div>
                <label class="block text-xs uppercase tracking-wide mb-2 text-slate-600 dark:text-slate-400">
                  <span class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    Produk
                  </span>
                </label>
                <div class="relative">
                  <select name="items[0][product_id]" required
                          class="w-full appearance-none bg-white border border-slate-200 rounded-xl h-11 px-3 pr-9 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                    <option value="" disabled {{ data_get(old('items.0'), 'product_id') ? '' : 'selected' }}>Pilih produk yang akan ditransfer</option>
                    @foreach ($products as $p)
                      <option value="{{ $p->id }}" @selected((string)data_get(old('items.0'), 'product_id') === (string)$p->id)>
                        [{{ $p->sku }}] {{ $p->name }}
                      </option>
                    @endforeach
                  </select>
                  <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                  </div>
                </div>
                @error('items.0.product_id')
                  <p class="mt-1 text-sm text-rose-500 flex items-start gap-1">
                    <svg class="h-4 w-4 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ $message }}</span>
                  </p>
                @enderror
              </div>

              <!-- Quantity -->
              <div>
                <label class="block text-xs uppercase tracking-wide mb-2 text-slate-600 dark:text-slate-400">
                  <span class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                    </svg>
                    Jumlah
                  </span>
                </label>
                <div class="relative">
                  <input type="number" 
                         name="items[0][qty]" 
                         step="0.01" 
                         min="0.01"
                         value="{{ data_get(old('items.0'), 'qty') }}"
                         placeholder="0.00" 
                         required
                         class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 pr-12 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                  <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 dark:text-slate-400">
                    <span class="text-xs">pcs</span>
                  </div>
                </div>
                @error('items.0.qty')
                  <p class="mt-1 text-sm text-rose-500 flex items-start gap-1">
                    <svg class="h-4 w-4 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ $message }}</span>
                  </p>
                @enderror
              </div>
            </div>
          </div>

          <!-- Notes Section -->
          <div class="space-y-4">
            <div class="flex items-center gap-3">
              <span class="h-7 w-7 rounded-full grid place-items-center text-xs font-semibold bg-slate-100 border border-slate-200
                            dark:bg-white/5 dark:border-[rgba(148,163,184,.12)]">3</span>
              <h3 class="font-semibold">Catatan Tambahan</h3>
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
              <textarea name="notes" rows="4" placeholder="Tambahkan catatan untuk transfer ini... (contoh: Mutasi antar cabang, Stock opname, dll)"
                        class="w-full bg-white border border-slate-200 rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">{{ old('notes') }}</textarea>
            </div>
          </div>

          <!-- Submit Section -->
          <div class="pt-2">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
              <div class="text-sm inline-flex items-start gap-2 text-slate-600 dark:text-slate-400">
                <svg class="h-4 w-4 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <span>Pastikan semua data sudah benar sebelum diproses</span>
              </div>

              <button type="submit" id="submitTransfer"
                      class="inline-flex items-center gap-3 h-11 px-5 rounded-xl border text-white bg-brand hover:bg-brand/90 border-transparent
                             dark:bg-brandDark dark:hover:bg-brandDark/90">
                <div id="spinnerBtn" class="hidden h-4 w-4 rounded-full border-2 border-white/60 border-t-transparent animate-spin"></div>
                <div class="inline-flex items-center gap-2">
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                  </svg>
                  <span>Proses Transfer Sekarang</span>
                </div>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

{{-- JS: overlay loading, toast close, validasi ringan, normalisasi angka --}}
<script>
(function(){
  const form = document.getElementById('transferForm');
  const overlay = document.getElementById('loadingOverlay');
  const spinnerBtn = document.getElementById('spinnerBtn');
  const submitBtn = document.getElementById('submitTransfer');

  // Close success toast
  document.getElementById('closeSuccessToast')?.addEventListener('click', () => {
    document.getElementById('successToast')?.remove();
  });
  // Auto hide toast
  setTimeout(() => document.getElementById('successToast')?.remove(), 5000);

  form?.addEventListener('submit', (e) => {
    // ringan: cegah cabang sama
    const from = form.querySelector('select[name="branch_from_id"]')?.value || '';
    const to   = form.querySelector('select[name="branch_to_id"]')?.value || '';
    if (from && to && from === to) {
      e.preventDefault();
      alert('Cabang asal dan tujuan tidak boleh sama.');
      return;
    }

    // normalisasi qty (koma -> titik)
    const qty = form.querySelector('input[name="items[0][qty]"]');
    if (qty && typeof qty.value === 'string') qty.value = qty.value.replace(',', '.');

    // tampilkan overlay + spinner, cegah submit ganda
    overlay?.classList.remove('hidden');
    if (spinnerBtn) spinnerBtn.classList.remove('hidden');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.classList.add('opacity-80','cursor-not-allowed');
    }
  });
})();
</script>
@endsection
