@extends('layouts.dashboard', ['title' => 'Buat Jurnal Manual'])

@section('content')
<div class="space-y-6">

 <form method="POST" action="{{ route('accounting.journal.store') }}" id="journalForm" class="space-y-6">
 @csrf

 {{-- Entry Info --}}
 <div class="rounded-2xl border bg-white shadow-card border-slate-200">
 <div class="p-6 md:p-7">
 <div class="flex items-start gap-3 mb-6">
 <div class="h-10 w-10 rounded-lg bg-slate-100 border border-slate-200 grid place-items-center">
 <svg width="24" height="24" class="h-5 w-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
 </svg>
 </div>
 <div>
 <h3 class="text-base font-semibold">Informasi Jurnal</h3>
 <p class="text-sm text-slate-500">Isi tanggal dan deskripsi jurnal</p>
 </div>
 </div>

 <div class="grid md:grid-cols-2 gap-4">
 <div>
 <label for="date" class="block text-xs uppercase tracking-wide text-slate-500 mb-2">Tanggal</label>
 <input type="date" name="date" id="date" value="{{ old('date', date('Y-m-d')) }}" required
 class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40">
 </div>
 <div>
 <label for="description" class="block text-xs uppercase tracking-wide text-slate-500 mb-2">Deskripsi</label>
 <input type="text" name="description" id="description" value="{{ old('description') }}" required
 class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40"
 placeholder="Deskripsi jurnal">
 </div>
 </div>
 </div>
 </div>

 {{-- Journal Lines --}}
 <div class="rounded-2xl border bg-white shadow-card border-slate-200">
 <div class="p-6 md:p-7">
 <div class="flex items-start justify-between mb-6">
 <div class="flex items-start gap-3">
 <div class="h-10 w-10 rounded-lg bg-slate-100 border border-slate-200 grid place-items-center">
 <svg width="24" height="24" class="h-5 w-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h2a2 2 0 002-2z"/>
 </svg>
 </div>
 <div>
 <h3 class="text-base font-semibold">Baris Jurnal</h3>
 <p class="text-sm text-slate-500">Tambahkan baris debit/kredit</p>
 </div>
 </div>

 <button type="button" onclick="addLine()"
 class="inline-flex items-center gap-2 h-9 px-3 rounded-lg border border-slate-200 hover:bg-slate-100 text-xs font-medium text-slate-700 transition">
 <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
 </svg>
 Tambah Baris
 </button>
 </div>

 <div id="linesContainer" class="space-y-3">
 <div class="journal-line" data-index="0">
 <div class="grid md:grid-cols-12 gap-3 items-end">
 <div class="md:col-span-5">
 <label class="block text-xs uppercase tracking-wide text-slate-500 mb-2">Akun</label>
 <select name="lines[0][account_id]" required
 class="w-full appearance-none bg-white border border-slate-200 rounded-xl h-11 px-3 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40">
 <option value="">Pilih Akun</option>
 @foreach($accounts as $account)
 <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
 @endforeach
 </select>
 </div>
 <div class="md:col-span-3">
 <label class="block text-xs uppercase tracking-wide text-slate-500 mb-2">Debit</label>
 <input type="number" name="lines[0][debit]" value="0" min="0" step="any"
 class="line-debit w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40"
 oninput="recalcTotals()">
 </div>
 <div class="md:col-span-3">
 <label class="block text-xs uppercase tracking-wide text-slate-500 mb-2">Kredit</label>
 <input type="number" name="lines[0][credit]" value="0" min="0" step="any"
 class="line-credit w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40"
 oninput="recalcTotals()">
 </div>
 <div class="md:col-span-1 flex items-end justify-center">
 <button type="button" onclick="removeLine(this)"
 class="h-11 w-11 rounded-xl border border-rose-200 hover:bg-rose-50 text-rose-600 grid place-items-center transition" title="Hapus Baris">
 <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
 </svg>
 </button>
 </div>
 </div>
 <div class="mt-2">
 <input type="text" name="lines[0][memo]" placeholder="Memo (opsional)"
 class="w-full bg-white border border-slate-200 rounded-lg h-9 px-3 text-xs focus:outline-none focus:ring-2 focus:ring-brand/40">
 </div>
 </div>
 </div>

 {{-- Totals --}}
 <div class="mt-6 pt-4 border-t border-slate-200">
 <div class="grid md:grid-cols-4 gap-4">
 <div class="md:col-span-2"></div>
 <div class="flex items-center justify-between px-4 py-3 rounded-xl bg-slate-50 border border-slate-200">
 <span class="text-sm font-medium text-slate-600">Total Debit</span>
 <span id="totalDebit" class="text-sm font-semibold">Rp 0</span>
 </div>
 <div class="flex items-center justify-between px-4 py-3 rounded-xl bg-slate-50 border border-slate-200">
 <span class="text-sm font-medium text-slate-600">Total Kredit</span>
 <span id="totalCredit" class="text-sm font-semibold">Rp 0</span>
 </div>
 </div>
 <div id="balanceStatus" class="mt-3 text-center text-sm font-medium text-slate-500"></div>
 </div>
 </div>
 </div>

 {{-- Submit --}}
 <div class="flex items-center justify-end gap-3">
 <a href="{{ route('accounting.journal') }}"
 class="inline-flex items-center gap-2 h-11 px-5 rounded-xl border border-slate-200 hover:bg-slate-100 text-sm font-medium text-slate-700 transition">
 Batal
 </a>
 <button type="submit" id="submitBtn" disabled
 class="inline-flex items-center gap-2 h-11 px-6 rounded-xl bg-slate-900 text-white hover:bg-slate-800 text-sm font-medium transition disabled:opacity-50 disabled:cursor-not-allowed">
 <svg width="20" height="20" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
 </svg>
 Simpan Jurnal
 </button>
 </div>
 </form>
</div>

@push('scripts')
<script>
let lineIndex = 1;

function addLine() {
 const container = document.getElementById('linesContainer');
 const accountOptions = container.querySelector('select').innerHTML;
 const html = `
 <div class="journal-line" data-index="${lineIndex}">
 <div class="grid md:grid-cols-12 gap-3 items-end">
 <div class="md:col-span-5">
 <label class="block text-xs uppercase tracking-wide text-slate-500 mb-2">Akun</label>
 <select name="lines[${lineIndex}][account_id]" required
 class="w-full appearance-none bg-white border border-slate-200 rounded-xl h-11 px-3 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40">
 ${accountOptions}
 </select>
 </div>
 <div class="md:col-span-3">
 <label class="block text-xs uppercase tracking-wide text-slate-500 mb-2">Debit</label>
 <input type="number" name="lines[${lineIndex}][debit]" value="0" min="0" step="any"
 class="line-debit w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40"
 oninput="recalcTotals()">
 </div>
 <div class="md:col-span-3">
 <label class="block text-xs uppercase tracking-wide text-slate-500 mb-2">Kredit</label>
 <input type="number" name="lines[${lineIndex}][credit]" value="0" min="0" step="any"
 class="line-credit w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40"
 oninput="recalcTotals()">
 </div>
 <div class="md:col-span-1 flex items-end justify-center">
 <button type="button" onclick="removeLine(this)"
 class="h-11 w-11 rounded-xl border border-rose-200 hover:bg-rose-50 text-rose-600 grid place-items-center transition" title="Hapus Baris">
 <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
 </svg>
 </button>
 </div>
 </div>
 <div class="mt-2">
 <input type="text" name="lines[${lineIndex}][memo]" placeholder="Memo (opsional)"
 class="w-full bg-white border border-slate-200 rounded-lg h-9 px-3 text-xs focus:outline-none focus:ring-2 focus:ring-brand/40">
 </div>
 </div>`;
 container.insertAdjacentHTML('beforeend', html);
 lineIndex++;
 recalcTotals();
}

function removeLine(btn) {
 const line = btn.closest('.journal-line');
 const container = document.getElementById('linesContainer');
 if (container.querySelectorAll('.journal-line').length <= 2) {
 alert('Minimal harus ada 2 baris jurnal.');
 return;
 }
 line.remove();
 recalcTotals();
}

function recalcTotals() {
 let totalDebit = 0;
 let totalCredit = 0;
 document.querySelectorAll('.journal-line').forEach(line => {
 totalDebit += parseFloat(line.querySelector('.line-debit').value) || 0;
 totalCredit += parseFloat(line.querySelector('.line-credit').value) || 0;
 });

 const fmt = n => 'Rp ' + n.toLocaleString('id-ID');
 document.getElementById('totalDebit').textContent = fmt(totalDebit);
 document.getElementById('totalCredit').textContent = fmt(totalCredit);

 const status = document.getElementById('balanceStatus');
 const submitBtn = document.getElementById('submitBtn');

 if (totalDebit === 0 && totalCredit === 0) {
 status.textContent = '';
 status.className = 'mt-3 text-center text-sm font-medium';
 submitBtn.disabled = true;
 } else if (Math.abs(totalDebit - totalCredit) < 0.01) {
 status.textContent = 'Seimbang';
 status.className = 'mt-3 text-center text-sm font-medium text-emerald-600';
 submitBtn.disabled = false;
 } else {
 status.textContent = `Tidak seimbang (selisih: ${fmt(Math.abs(totalDebit - totalCredit))})`;
 status.className = 'mt-3 text-center text-sm font-medium text-rose-600';
 submitBtn.disabled = true;
 }
}

recalcTotals();
</script>
@endpush
@endsection