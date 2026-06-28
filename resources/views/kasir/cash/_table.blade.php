<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
  <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
    <h3 class="text-sm font-semibold text-slate-900">Mutasi Kas</h3>
    <span class="text-xs text-slate-400">{{ $moves->total() }} transaksi</span>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 border-b border-slate-200">
        <tr>
          <th class="px-4 py-2.5 text-left text-xs font-medium text-slate-500">#</th>
          <th class="px-4 py-2.5 text-left text-xs font-medium text-slate-500">Tanggal</th>
          <th class="px-4 py-2.5 text-left text-xs font-medium text-slate-500">Kategori</th>
          <th class="px-4 py-2.5 text-left text-xs font-medium text-slate-500">Arah</th>
          <th class="px-4 py-2.5 text-right text-xs font-medium text-slate-500">Nominal</th>
          <th class="px-4 py-2.5 text-left text-xs font-medium text-slate-500">Catatan</th>
          <th class="px-4 py-2.5 text-left text-xs font-medium text-slate-500">User</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        @forelse ($moves as $m)
          <tr class="hover:bg-slate-50 transition-colors">
            <td class="px-4 py-3 text-xs text-slate-400">{{ $m->id }}</td>
            <td class="px-4 py-3 text-xs text-slate-600">{{ \Carbon\Carbon::parse($m->created_at)->format('d M Y H:i') }}</td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-xs font-medium">
                {{ $m->category }}
              </span>
            </td>
            <td class="px-4 py-3">
              @if ($m->direction === 'IN')
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-200">
                  <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/></svg>
                  IN
                </span>
              @else
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 text-xs font-semibold border border-rose-200">
                  <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/></svg>
                  OUT
                </span>
              @endif
            </td>
            <td class="px-4 py-3 text-right font-semibold tabular-nums {{ $m->direction === 'IN' ? 'text-emerald-700' : 'text-rose-700' }}">
              {{ $m->direction === 'IN' ? '+' : '-' }} Rp {{ number_format((float)$m->amount, 0, ',', '.') }}
            </td>
            <td class="px-4 py-3 text-xs text-slate-500 max-w-[200px] truncate">{{ $m->memo ?: '—' }}</td>
            <td class="px-4 py-3 text-xs text-slate-500">{{ $m->user_name ?: '—' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="px-4 py-12 text-center">
              <div class="flex flex-col items-center">
                <div class="h-12 w-12 rounded-2xl bg-slate-100 flex items-center justify-center mb-3">
                  <svg class="h-6 w-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <p class="text-sm font-medium text-slate-500">Belum ada mutasi</p>
                <p class="text-xs text-slate-400 mt-1">Pada periode yang dipilih</p>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($moves->hasPages())
    <div class="px-4 py-3 border-t border-slate-100 flex justify-center">
      {{ $moves->withQueryString()->links('pagination::tailwind') }}
    </div>
  @endif
</div>
