@extends('layouts.dashboard', ['title' => 'Laporan Riwayat Penjualan'])

@section('content')
<div class="space-y-6">
    <div class="space-y-2">
        <h1 class="text-xl md:text-2xl font-semibold">Laporan Riwayat Penjualan</h1>
        <p class="text-slate-600 dark:text-slate-400">Jejak audit lengkap dari semua transaksi penjualan POS.</p>
    </div>

    @if (session('error'))
        <div class="rounded-xl border px-4 py-3 text-sm bg-rose-50 border-rose-200 text-rose-700 dark:bg-rose-500/15 dark:border-rose-500/30 dark:text-rose-200">
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <!-- Revenue Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Revenue Card -->
        <div class="rounded-2xl border bg-white shadow-card border-slate-200 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Total Pendapatan</p>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white">
                            Rp {{ $formattedTotalRevenue }}
                        </p>
                    </div>
                    <div class="p-3 rounded-xl bg-emerald-100 dark:bg-emerald-500/15">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-600 dark:text-emerald-400">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($statistics))
            <!-- Total Transactions Card -->
            <div class="rounded-2xl border bg-white shadow-card border-slate-200 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Total Transaksi</p>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white">
                                {{ number_format($statistics['total_transactions']) }}
                            </p>
                        </div>
                        <div class="p-3 rounded-xl bg-blue-100 dark:bg-blue-500/15">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600 dark:text-blue-400">
                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                                <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Average Daily Revenue Card -->
            <div class="rounded-2xl border bg-white shadow-card border-slate-200 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Rata-rata Harian</p>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white">
                                Rp {{ $statistics['avg_daily_revenue'] }}
                            </p>
                        </div>
                        <div class="p-3 rounded-xl bg-purple-100 dark:bg-purple-500/15">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-purple-600 dark:text-purple-400">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Average Transaction Value Card -->
            <div class="rounded-2xl border bg-white shadow-card border-slate-200 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Rata-rata Transaksi</p>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white">
                                Rp {{ $statistics['avg_transaction_value'] }}
                            </p>
                        </div>
                        <div class="p-3 rounded-xl bg-orange-100 dark:bg-orange-500/15">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-orange-600 dark:text-orange-400">
                                <polyline points="22,12 18,12 15,21 9,3 6,12 2,12"></polyline>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Filter Section -->
    <div class="rounded-2xl border bg-white shadow-card border-slate-200 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
        <form action="{{ route('reports.transactions.index') }}" method="GET" class="p-6 md:p-7">
            <div class="grid md:grid-cols-3 gap-4">
                {{-- Rentang Tanggal --}}
                <div class="md:col-span-2">
                    <label class="block text-xs uppercase tracking-wide mb-2 text-slate-600 dark:text-slate-400">Rentang Tanggal</label>
                    <div class="flex items-center gap-2">
                        <input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}"
                               class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                        <span class="text-slate-500 dark:text-slate-400">s/d</span>
                        <input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}"
                               class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                    </div>
                </div>

                {{-- Cabang --}}
                <div>
                    <label for="branch_id" class="block text-xs uppercase tracking-wide mb-2 text-slate-600 dark:text-slate-400">Cabang</label>
                    <select name="branch_id" id="branch_id"
                            class="w-full appearance-none bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                        <option value="">Semua Cabang</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(isset($filters['branch_id']) && $filters['branch_id'] == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Tombol Aksi --}}
            <div class="flex flex-wrap items-center gap-3 pt-5">
                <button type="submit"
                        class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border bg-slate-100 hover:bg-slate-200 border-slate-200 dark:bg-white/5 dark:hover:bg-white/10 dark:border-[rgba(148,163,184,.12)]">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 20 20" fill="currentColor" class="opacity-80">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                    Filter
                </button>
                <a href="{{ route('reports.transactions.index') }}" title="Reset Filter"
                   class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border hover:bg-slate-100 border-slate-200 dark:hover:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 20 20" fill="currentColor" class="opacity-80">
                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 011.601-1.019 7.002 7.002 0 019.999 3.585 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                    </svg>
                    Reset
                </a>
                
                @if(!empty($statistics))
                    <div class="ml-auto text-sm text-slate-600 dark:text-slate-400">
                        Periode: {{ $statistics['period_days'] }} hari
                    </div>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabel Laporan -->
    <div class="rounded-2xl border bg-white shadow-card border-slate-200 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
        <div class="p-6 md:p-7">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-slate-600 dark:text-slate-400">
                        <tr class="border-b border-slate-200 dark:border-[rgba(148,163,184,.12)]">
                            <th class="py-3 pr-4">Tanggal</th>
                            <th class="py-3 pr-4">Referensi</th>
                            <th class="py-3 pr-4">Pelanggan</th>
                            <th class="py-3 pr-4">Cabang</th>
                            <th class="py-3 pr-0 text-right">Nilai Transaksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200 dark:divide-[rgba(148,163,184,.12)]">
                        @forelse ($transactions as $tx)
                            <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                                <td class="py-3 pr-4 align-top">
                                    {{ \Carbon\Carbon::parse($tx->transaction_date)->isoFormat('D MMM Y, HH:mm') }}
                                </td>
                                <td class="py-3 pr-4 align-top">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                            POS
                                        </span>
                                        <div>
                                            <div class="font-medium">#{{ $tx->transaction_id }}</div>
                                            <div class="text-slate-600 dark:text-slate-400">{{ Str::limit($tx->description, 40) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 pr-4 align-top">{{ $tx->customer_name ?? '-' }}</td>
                                <td class="py-3 pr-4 align-top">{{ $tx->branch_name }}</td>
                                <td class="py-3 pr-0 align-top text-right">
                                    <span class="font-medium">Rp {{ number_format($tx->transaction_value, 0, ',', '.') }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-10">
                                    <div class="text-center space-y-2">
                                        <div class="mx-auto w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400">
                                                <circle cx="11" cy="11" r="8"></circle>
                                                <path d="m21 21-4.35-4.35"></path>
                                            </svg>
                                        </div>
                                        <p class="font-medium text-slate-600 dark:text-slate-400">Tidak ada data penjualan</p>
                                        <p class="text-slate-500 dark:text-slate-500">Coba ubah atau reset filter yang Anda gunakan.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($transactions->hasPages())
                <div class="flex items-center justify-between pt-6 border-t border-slate-200 dark:border-[rgba(148,163,184,.12)]">
                    <div class="text-sm text-slate-600 dark:text-slate-400">
                        Menampilkan {{ $transactions->firstItem() }} hingga {{ $transactions->lastItem() }} dari {{ $transactions->total() }} transaksi
                    </div>
                    <div>
                        {{ $transactions->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Enhanced JavaScript untuk auto-submit dan UX improvements -->
<script>
(function(){
    const form = document.querySelector('form[action="{{ route('reports.transactions.index') }}"]');
    if (!form) return;

    const startDate = form.querySelector('input[name="start_date"]');
    const endDate = form.querySelector('input[name="end_date"]');
    const branchSelect = form.querySelector('#branch_id');
    const submitBtn = form.querySelector('button[type="submit"]');

    // Fungsi debounce untuk menghindari terlalu banyak request
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Auto-submit dengan loading state
    function submitFormWithLoading() {
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Loading...';
        }
        form.submit();
    }

    // Auto-submit saat branch berubah
    if (branchSelect) {
        branchSelect.addEventListener('change', debounce(submitFormWithLoading, 300));
    }

    // Auto-submit saat kedua tanggal terisi
    function checkDateRange() {
        if (startDate?.value && endDate?.value) {
            submitFormWithLoading();
        }
    }

    if (startDate && endDate) {
        startDate.addEventListener('change', debounce(checkDateRange, 500));
        endDate.addEventListener('change', debounce(checkDateRange, 500));
    }

    // Reset loading state jika form tidak ter-submit
    setTimeout(() => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 20 20" fill="currentColor" class="opacity-80"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" /></svg>Filter';
        }
    }, 1000);
})();
</script>
@endsection
