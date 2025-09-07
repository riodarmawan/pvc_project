@extends('layouts.dashboard', ['title' => 'Input Pembelian Langsung'])

@section('content')
<div class="space-y-6">
    <div class="space-y-2">
        <h1 class="text-xl md:text-2xl font-semibold">Input Pembelian Langsung</h1>
        <p class="text-slate-600 dark:text-slate-400">
            Catat pembelian barang dari supplier. Harga akan diambil dari pembelian terakhir secara otomatis.
        </p>
    </div>

    {{-- Notifikasi Sukses dan Error --}}
    @if (session('success'))
        <div class="rounded-xl border px-4 py-3 text-sm bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-500/15 dark:border-emerald-500/30 dark:text-emerald-200">
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-xl border px-4 py-3 text-sm bg-rose-50 border-rose-200 text-rose-700 dark:bg-rose-500/15 dark:border-rose-500/30 dark:text-rose-200">
            <p class="font-semibold">Terjadi Kesalahan</p>
            <ul class="list-disc pl-5 mt-1">
                @foreach ($errors->all() as $error)
                    <li class="leading-6">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('purchase.direct.store') }}" method="POST" id="formDirectPurchase">
        @csrf

        {{-- Kartu: Supplier & Cabang --}}
        <div class="rounded-2xl border bg-white shadow-card border-slate-200 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            <div class="p-6 md:p-7 grid md:grid-cols-2 gap-4">
                {{-- Supplier --}}
                <div class="space-y-2">
                    <label for="supplier_id" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Supplier</label>
                    <div class="flex items-center gap-2">
                        <select id="supplier_id" name="supplier_id" required
                                class="w-full appearance-none bg-white border border-slate-200 rounded-xl h-11 px-3 pr-9 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                            <option value="">Pilih Supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                        <a href="{{ route('suppliers.create') }}"
                           class="shrink-0 inline-flex items-center h-11 px-3 rounded-xl border hover:bg-slate-100 border-slate-200 dark:hover:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
                            Supplier Baru
                        </a>
                    </div>
                </div>

                {{-- Cabang --}}
                <div class="space-y-2">
                    <label for="branch_id" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Cabang Penerima</label>
                    <select id="branch_id" name="branch_id" required
                            class="w-full appearance-none bg-white border border-slate-200 rounded-xl h-11 px-3 pr-9 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                        <option value="">Pilih Cabang</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Invoice No (full width) --}}
                <div class="md:col-span-2 space-y-2">
                    <label for="invoice_no" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Nomor Invoice Supplier</label>
                    <input type="text" name="invoice_no" id="invoice_no" value="{{ old('invoice_no') }}" required
                           class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                </div>
            </div>
        </div>

        {{-- Kartu: Detail Barang --}}
        <div class="rounded-2xl border bg-white shadow-card border-slate-200 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            <div class="p-6 md:p-7 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold">Detail Barang</h2>
                        <p class="text-sm text-slate-600 dark:text-slate-400">Tambahkan baris barang yang dibeli.</p>
                    </div>
                    <a href="{{ route('products.create') }}"
                       class="inline-flex items-center h-10 px-3 rounded-xl border hover:bg-slate-100 border-slate-200 dark:hover:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
                        + Daftarkan Produk Baru
                    </a>
                </div>

                <div id="item-container" class="space-y-3">
                    {{-- Baris produk akan ditambahkan oleh JavaScript --}}
                </div>

                <button type="button" id="add-item-btn"
                        class="mt-2 inline-flex items-center h-10 px-4 rounded-xl border bg-slate-100 hover:bg-slate-200 border-slate-200 dark:bg-white/5 dark:hover:bg-white/10 dark:border-[rgba(148,163,184,.12)]">
                    + Tambah Barang
                </button>
            </div>
        </div>

        {{-- Aksi --}}
        <div class="flex justify-end">
            <button type="submit" id="btnSubmitPurchase"
                    class="inline-flex items-center gap-2 h-11 px-5 rounded-xl border text-white bg-brand hover:bg-brand/90 border-transparent dark:bg-brandDark dark:hover:bg-brandDark/90">
                Simpan Pembelian
            </button>
        </div>
    </form>
</div>

{{-- Template baris item --}}
<template id="tpl-item-row">
    <div class="rounded-xl border bg-slate-50/60 border-slate-200 p-3 md:p-4 dark:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
        <div class="grid md:grid-cols-12 gap-3">
            {{-- Produk --}}
            <div class="md:col-span-5">
                <label class="block text-xs uppercase tracking-wide mb-1 text-slate-600 dark:text-slate-400">Produk</label>
                @isset($products)
                    <select name="items[__INDEX__][product_id]" required
                            class="w-full appearance-none bg-white border border-slate-200 rounded-lg h-10 px-3 pr-9 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                        <option value="">Pilih Produk</option>
                        @foreach ($products as $p)
                            <option value="{{ $p->id }}">[{{ $p->sku }}] {{ $p->name }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="number" min="1" step="1" placeholder="ID Produk"
                           name="items[__INDEX__][product_id]" required
                           class="w-full bg-white border border-slate-200 rounded-lg h-10 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                @endisset
            </div>

            {{-- Qty --}}
            <div class="md:col-span-3">
                <label class="block text-xs uppercase tracking-wide mb-1 text-slate-600 dark:text-slate-400">Qty</label>
                <input type="number" inputmode="decimal" step="0.01" min="0.01" placeholder="0.00"
                       name="items[__INDEX__][qty]" required
                       class="w-full bg-white border border-slate-200 rounded-lg h-10 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            </div>

            {{-- Harga Satuan (opsional, kosong = auto) --}}
            <div class="md:col-span-3">
                <label class="block text-xs uppercase tracking-wide mb-1 text-slate-600 dark:text-slate-400">Harga Satuan</label>
                <input type="number" inputmode="decimal" step="0.01" min="0" placeholder="Otomatis jika kosong"
                       name="items[__INDEX__][unit_price]"
                       class="w-full bg-white border border-slate-200 rounded-lg h-10 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            </div>

            {{-- Remove --}}
            <div class="md:col-span-1 flex items-end">
                <button type="button" data-action="remove-row"
                        class="w-full h-10 rounded-lg border text-rose-700 bg-rose-50 hover:bg-rose-100 border-rose-200
                               dark:text-rose-200 dark:bg-rose-500/15 dark:hover:bg-rose-500/25 dark:border-rose-500/30">
                    Hapus
                </button>
            </div>
        </div>
    </div>
</template>

{{-- JS: tambah/hapus baris, normalisasi angka, cegah submit ganda --}}
<script>
(function(){
  const container = document.getElementById('item-container');
  const tpl = document.getElementById('tpl-item-row');
  const addBtn = document.getElementById('add-item-btn');
  const form = document.getElementById('formDirectPurchase');
  const submitBtn = document.getElementById('btnSubmitPurchase');
  let indexCounter = 0;

  function addRow(prefill = {}) {
    if (!tpl || !container) return;
    const html = tpl.innerHTML.replaceAll('__INDEX__', String(indexCounter++));
    const wrapper = document.createElement('div');
    wrapper.innerHTML = html.trim();
    const row = wrapper.firstElementChild;

    // prefill (opsional)
    if (prefill.product_id) row.querySelector('[name$="[product_id]"]').value = prefill.product_id;
    if (prefill.qty) row.querySelector('[name$="[qty]"]').value = prefill.qty;
    if (prefill.unit_price) row.querySelector('[name$="[unit_price]"]').value = prefill.unit_price;

    // listeners: normalisasi koma -> titik
    row.querySelectorAll('input[type="number"]').forEach(inp => {
      inp.addEventListener('change', () => {
        if (typeof inp.value === 'string') inp.value = inp.value.replace(',', '.');
      });
    });

    // remove
    row.querySelector('[data-action="remove-row"]').addEventListener('click', () => {
      row.remove();
      // pastikan selalu ada minimal 1 baris
      if (container.children.length === 0) addRow();
    });

    container.appendChild(row);
  }

  // tombol tambah
  addBtn?.addEventListener('click', () => addRow());

  // minimal 1 baris saat load
  if (container && container.children.length === 0) addRow();

  // submit: normalisasi semua angka + cegah submit ganda
  form?.addEventListener('submit', () => {
    form.querySelectorAll('input[type="number"]').forEach(inp => {
      if (typeof inp.value === 'string') inp.value = inp.value.replace(',', '.');
    });
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.classList.add('opacity-70','cursor-not-allowed');
    }
  });
})();
</script>
@endsection
