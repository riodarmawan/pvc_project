@extends('layouts.dashboard', ['title' => 'Edit Sisa Potongan'])

@section('content')
<div class="space-y-6">
    <div class="space-y-2">
        <h1 class="text-xl md:text-2xl font-semibold">Edit Sisa Potongan (ID: {{ $leftover->id }})</h1>
        <p class="text-slate-600 dark:text-slate-400">Perbarui informasi panjang atau kondisi untuk sisa potongan ini.</p>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border px-4 py-3 bg-rose-50 border-rose-200 text-rose-700 dark:bg-rose-500/15 dark:border-rose-500/30 dark:text-rose-200">
            <p class="font-semibold">Terjadi Kesalahan</p>
            <ul class="list-disc pl-5 mt-1 text-sm">
                @foreach ($errors->all() as $error)
                    <li class="leading-6">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Info produk & cabang (read-only) --}}
    <div class="rounded-2xl border bg-white shadow-card border-slate-200 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
        <div class="p-6 md:p-7 grid md:grid-cols-2 gap-4">
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400 mb-1">Produk</div>
                <div class="font-medium">
                    <span class="font-mono text-xs text-slate-600 dark:text-slate-400">[{{ $leftover->product_sku }}]</span>
                    {{ $leftover->product_name }}
                </div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400 mb-1">Cabang</div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg border bg-slate-100 border-slate-200 dark:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
                    <span class="h-2.5 w-2.5 rounded-full bg-brand/70"></span>
                    {{ $leftover->branch_name }}
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('leftovers.update', $leftover->id) }}" method="POST" id="formEditLeftover">
        @csrf
        @method('PUT')

        <div class="rounded-2xl border bg-white shadow-card border-slate-200 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            <div class="p-6 md:p-7 space-y-6">
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label for="length_m" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                            Panjang (dalam meter)
                        </label>
                        <input type="number" name="length_m" id="length_m"
                               value="{{ old('length_m', $leftover->length_m) }}" step="0.01" required
                               class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                    </div>

                    <div class="space-y-2">
                        <label for="condition" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                            Kondisi
                        </label>
                        <select id="condition" name="condition" required
                                class="w-full appearance-none bg-white border border-slate-200 rounded-xl h-11 px-3 pr-9 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                            <option value="GOOD" @selected(old('condition', $leftover->condition) == 'GOOD')>Baik</option>
                            <option value="DAMAGED" @selected(old('condition', $leftover->condition) == 'DAMAGED')>Rusak</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                    <a href="{{ route('leftovers.index') }}"
                       class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border hover:bg-slate-100 border-slate-200 dark:hover:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
                        &larr; Kembali ke Daftar
                    </a>
                    <button type="submit" id="btnSave"
                            class="inline-flex items-center gap-2 h-11 px-5 rounded-xl border text-white bg-brand hover:bg-brand/90 border-transparent dark:bg-brandDark dark:hover:bg-brandDark/90">
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- JS kecil: normalisasi koma→titik & cegah submit ganda --}}
<script>
(function(){
  const form = document.getElementById('formEditLeftover');
  const input = document.getElementById('length_m');
  const btn = document.getElementById('btnSave');

  form?.addEventListener('submit', function(){
    if (input && typeof input.value === 'string') {
      input.value = input.value.replace(',', '.');
    }
    if (btn) {
      btn.disabled = true;
      btn.classList.add('opacity-70','cursor-not-allowed');
    }
  });
})();
</script>
@endsection
