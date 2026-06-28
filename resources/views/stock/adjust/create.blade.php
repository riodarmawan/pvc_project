@extends('layouts.dashboard', ['title' => 'Penyesuaian Stok'])

@section('content')
<div class="space-y-6">

 {{-- Notifikasi Sukses dan Error --}}
 @if (session('success'))
 <div class="rounded-xl border bg-emerald-50 border-emerald-200 text-emerald-700 ">
 <div class="px-4 py-3 flex items-start gap-3">
 <div class="mt-0.5">
 <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="opacity-90">
 <path fill-rule="evenodd" d="M2.25 12a9.75 9.75 0 1 1 19.5 0 9.75 9.75 0 0 1-19.5 0Zm13.36-2.59a.75.75 0 1 0-1.22-.92l-3.9 5.18-1.73-1.73a.75.75 0 0 0-1.06 1.06l2.4 2.4c.31.31.81.27 1.06-.08l4.51-5.91Z" clip-rule="evenodd"/>
 </svg>
 </div>
 <div class="font-medium">{{ session('success') }}</div>
 </div>
 </div>
 @endif
 @if ($errors->any())
 <div class="rounded-xl border px-4 py-3 text-sm bg-rose-50 border-rose-200 text-rose-700 ">
 {{ $errors->first() }}
 </div>
 @endif

 {{-- Header --}}
 <div class="flex items-center justify-between">
 <h1 class="text-xl md:text-2xl font-semibold">Penyesuaian Stok</h1>
 <a href="{{ url()->previous() }}"
 class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border hover:bg-slate-100 border-slate-200
 ">
 &larr; Kembali
 </a>
 </div>

 {{-- Kartu Form --}}
 <div class="rounded-2xl border bg-white shadow-card border-slate-200 ">
 <div class="p-6 md:p-7 space-y-6">
 <div class="flex items-center justify-between">
 <div class="text-sm font-semibold">Formulir Penyesuaian</div>
 <span class="text-xs px-2.5 py-1 rounded-full bg-slate-100 border border-slate-200 ">Ref: ADJUST</span>
 </div>

 {{-- Pemilihan Cabang (jika user tidak punya cabang default) --}}
 @if (empty($active_branch))
 <div class="space-y-2">
 <label class="block text-xs uppercase tracking-wide text-slate-600 ">Cabang</label>
 <select name="branch_id" form="form-adjust" required
 class="w-full appearance-none bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 ">
 <option value="" disabled selected>Pilih cabang</option>
 @foreach ($branches as $br)
 <option value="{{ $br->id }}" @selected(old('branch_id') == $br->id)>{{ $br->name }}</option>
 @endforeach
 </select>
 <p class="text-sm text-slate-600 ">Sistem akan memakai/membuat lokasi <b>AVAILABLE</b> untuk cabang terpilih.</p>
 </div>
 @else
 <div class="flex items-center gap-3 text-sm">
 <span class="text-slate-600 ">Cabang:</span>
 <span class="inline-flex items-center gap-2 px-3 py-1 rounded-lg border bg-slate-100 border-slate-200 ">
 <span class="h-2.5 w-2.5 rounded-full bg-brand/70"></span>
 {{ $active_branch->name }}
 </span>
 </div>
 @endif

 <form id="form-adjust" method="post" action="{{ route('stock.adjust.store') }}" class="space-y-6">
 @csrf
 @if (!empty($active_branch))
 <input type="hidden" name="branch_id" value="{{ $active_branch->id }}">
 @endif

 {{-- Produk --}}
 <div class="space-y-2">
 <label class="block text-xs uppercase tracking-wide text-slate-600 ">Produk</label>
 <select name="product_id" required
 class="w-full appearance-none bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 ">
 <option value="" disabled selected>Pilih produk</option>
 @foreach ($products as $p)
 <option value="{{ $p->id }}" @selected(old('product_id') == $p->id)>
 [{{ $p->sku }}] {{ $p->name }}
 </option>
 @endforeach
 </select>
 @error('product_id') <p class="text-sm text-rose-500">{{ $message }}</p> @enderror
 </div>

 <div class="grid md:grid-cols-2 gap-4">
 {{-- Input Kuantitas Baru --}}
 <div class="space-y-2">
 <label class="block text-xs uppercase tracking-wide text-slate-600 ">Jumlah Seharusnya (Qty Baru)</label>
 <input type="number" name="new_qty" step="0.01" min="0" value="{{ old('new_qty') }}"
 placeholder="Contoh: 90.00" required
 class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 ">
 @error('new_qty') <p class="text-sm text-rose-500">{{ $message }}</p> @enderror
 <p class="text-sm text-slate-600 ">Masukkan jumlah stok fisik yang ada saat ini. Sistem akan menghitung selisihnya secara otomatis.</p>
 </div>

 {{-- Alasan Penyesuaian --}}
 <div class="space-y-2">
 <label class="block text-xs uppercase tracking-wide text-slate-600 ">Alasan Penyesuaian</label>
 <input type="text" name="reason" value="{{ old('reason') }}"
 placeholder="Mis: Hasil Stock Opname / Barang Rusak"
 class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 ">
 @error('reason') <p class="text-sm text-rose-500">{{ $message }}</p> @enderror
 </div>
 </div>

 {{-- Tombol Aksi --}}
 <div class="flex flex-wrap items-center gap-3">
 <button type="submit" id="submitAdjust"
 class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border text-white bg-brand hover:bg-brand/90 border-transparent ">
 Simpan Penyesuaian
 </button>

 <details class="group">
 <summary class="flex items-center gap-2 cursor-pointer select-none px-3 py-2 rounded-xl border hover:bg-slate-100 border-slate-200 ">
 <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
 class="transition-transform group-open:rotate-180">
 <path d="M6 9l6 6 6-6"/>
 </svg>
 Tips
 </summary>
 <div class="mt-2 text-sm text-slate-600 px-1">
 Gunakan desimal (koma atau titik) untuk akurasi. Contoh: untuk mengurangi stok dari 120 menjadi 90, cukup masukkan <code>90</code> di kolom "Jumlah Seharusnya".
 </div>
 </details>
 </div>
 </form>
 </div>
 </div>
</div>

{{-- JS: cegah submit ganda & normalisasi angka --}}
<script>
(function(){
 const form = document.getElementById('form-adjust');
 if (!form) return;

 form.addEventListener('submit', function(e){
 const qty = form.querySelector('input[name="new_qty"]');
 if (qty && typeof qty.value === 'string') {
 qty.value = qty.value.replace(',', '.'); // dukung koma sebagai desimal
 }

 const btn = document.getElementById('submitAdjust');
 if (btn) {
 btn.disabled = true;
 btn.classList.add('opacity-70','cursor-not-allowed');
 }
 });
})();
</script>
@endsection
