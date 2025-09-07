@extends('layouts.dashboard', ['title' => 'Manajemen Sisa Potongan'])

@section('content')
<div class="space-y-6">
    <div class="space-y-2">
        <h1 class="text-xl md:text-2xl font-semibold">Manajemen Sisa Potongan</h1>
        <p class="text-slate-600 dark:text-slate-400">Kelola semua sisa potongan dari proyek yang telah selesai.</p>
    </div>

    @if (session('success'))
        <div class="rounded-xl border px-4 py-3 text-sm bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-500/15 dark:border-emerald-500/30 dark:text-emerald-200">
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if (session('error'))
        <div class="rounded-xl border px-4 py-3 text-sm bg-rose-50 border-rose-200 text-rose-700 dark:bg-rose-500/15 dark:border-rose-500/30 dark:text-rose-200">
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <!-- Tabel Daftar Sisa Potongan -->
    <div class="rounded-2xl border bg-white shadow-card border-slate-200 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
        <div class="p-6 md:p-7">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-slate-600 dark:text-slate-400">
                        <tr class="border-b border-slate-200 dark:border-[rgba(148,163,184,.12)]">
                            <th class="py-3 pr-4">ID</th>
                            <th class="py-3 pr-4">Produk</th>
                            <th class="py-3 pr-4">Cabang</th>
                            <th class="py-3 pr-4">Panjang (m)</th>
                            <th class="py-3 pr-4">Kondisi</th>
                            <th class="py-3 pr-4">Tgl Dibuat</th>
                            <th class="py-3 pr-0 text-right"><span>Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-[rgba(148,163,184,.12)]">
                        @forelse ($leftovers as $leftover)
                            <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                                <td class="py-3 pr-4 align-top">{{ $leftover->id }}</td>
                                <td class="py-3 pr-4 align-top">
                                    <div class="font-mono text-xs text-slate-600 dark:text-slate-400">[{{ $leftover->product_sku }}]</div>
                                    <div class="font-medium">{{ $leftover->product_name }}</div>
                                </td>
                                <td class="py-3 pr-4 align-top">{{ $leftover->branch_name }}</td>
                                <td class="py-3 pr-4 align-top">{{ number_format($leftover->length_m, 2) }} m</td>
                                <td class="py-3 pr-4 align-top">
                                    @if($leftover->condition == 'GOOD')
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                            BAIK
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">
                                            RUSAK
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 pr-4 align-top">{{ \Carbon\Carbon::parse($leftover->created_at)->format('d M Y H:i') }}</td>
                                <td class="py-3 pr-0 align-top">
                                    {{-- ===== PERUBAHAN DI SINI ===== --}}
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('leftovers.edit', $leftover->id) }}"
                                           class="inline-flex items-center h-9 px-3 rounded-lg border hover:bg-slate-100 border-slate-200 dark:hover:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
                                            Edit
                                        </a>
                                        <form action="{{ route('leftovers.destroy', $leftover->id) }}" method="POST"
                                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus sisa potongan ini? Tindakan ini tidak dapat dibatalkan.');"
                                              class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center h-9 px-3 rounded-lg border text-rose-700 bg-rose-50 hover:bg-rose-100 border-rose-200
                                                           dark:text-rose-200 dark:bg-rose-500/15 dark:hover:bg-rose-500/25 dark:border-rose-500/30">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                    {{-- ============================== --}}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-10">
                                    <div class="text-center text-slate-600 dark:text-slate-400">
                                        Tidak ada data sisa potongan yang ditemukan.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        {{ $leftovers->links() }}
    </div>
</div>
@endsection
