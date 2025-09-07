@extends('layouts.dashboard', ['title' => 'Daftarkan Supplier Baru'])

@section('content')
<div class="space-y-6">
    <div class="space-y-2">
        <h1 class="text-xl md:text-2xl font-semibold">Daftarkan Supplier Baru</h1>
        <p class="text-slate-600 dark:text-slate-400">Isi data supplier untuk mencatat data pembelian.</p>
    </div>

    @if ($errors->any())
        <div role="alert"
             class="rounded-xl border px-4 py-3 text-sm bg-rose-50 border-rose-200 text-rose-700
                    dark:bg-rose-500/15 dark:border-rose-500/30 dark:text-rose-200">
            <p class="font-semibold">Terjadi Kesalahan</p>
            <ul class="list-disc pl-5 mt-1 space-y-1">
                @foreach ($errors->all() as $error)
                    <li class="leading-6">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border bg-white shadow-card border-slate-200
                dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
        <form action="{{ route('suppliers.store') }}" method="POST" id="formSupplier" class="p-6 md:p-7 space-y-6">
            @csrf

            <div class="space-y-2">
                <label for="name" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                    Nama Supplier
                </label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                       class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                              focus:outline-none focus:ring-2 focus:ring-brand/40
                              dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            </div>

            <div class="space-y-2">
                <label for="phone" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                    Nomor Telepon (Opsional)
                </label>
                <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                       class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                              focus:outline-none focus:ring-2 focus:ring-brand/40
                              dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]"
                       placeholder="+62 812 3456 7890">
            </div>

            <div class="space-y-2">
                <label for="address" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                    Alamat (Opsional)
                </label>
                <textarea name="address" id="address" rows="3"
                          class="w-full bg-white border border-slate-200 rounded-xl p-3 text-sm
                                 focus:outline-none focus:ring-2 focus:ring-brand/40
                                 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">{{ old('address') }}</textarea>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                <a href="{{ route('purchase.direct.create') }}"
                   class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border hover:bg-slate-100 border-slate-200
                          dark:hover:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
                    &larr; Kembali ke Form Pembelian
                </a>
                <button type="submit" id="btnSaveSupplier"
                        class="inline-flex items-center gap-2 h-11 px-5 rounded-xl border text-white bg-brand hover:bg-brand/90 border-transparent
                               dark:bg-brandDark dark:hover:bg-brandDark/90">
                    Simpan Supplier
                </button>
            </div>
        </form>
    </div>
</div>

{{-- JS kecil: trim input & cegah submit ganda --}}
<script>
(function(){
  const form = document.getElementById('formSupplier');
  const btn  = document.getElementById('btnSaveSupplier');
  form?.addEventListener('submit', () => {
    // Trim sederhana sebelum submit
    ['name','phone','address'].forEach(id => {
      const el = document.getElementById(id);
      if (el && typeof el.value === 'string') el.value = el.value.trim();
    });
    if (btn) {
      btn.disabled = true;
      btn.classList.add('opacity-70','cursor-not-allowed');
    }
  });
})();
</script>
@endsection
