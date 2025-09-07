<div class="bg-white rounded-xl shadow overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50">
      <tr>
        <th class="p-3 text-left">#</th>
        <th class="p-3 text-left">Tanggal</th>
        <th class="p-3 text-left">Kategori</th>
        <th class="p-3 text-left">Arah</th>
        <th class="p-3 text-right">Nominal</th>
        <th class="p-3 text-left">Catatan</th>
        <th class="p-3 text-left">User</th>
      </tr>
    </thead>
    <tbody class="divide-y">
      @forelse ($moves as $m)
        <tr>
          <td class="p-3">#{{ $m->id }}</td>
          <td class="p-3">{{ $m->created_at }}</td>
          <td class="p-3">{{ $m->category }}</td>
          <td class="p-3">
            @if ($m->direction === 'IN')
              <span class="px-2 py-1 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">IN</span>
            @else
              <span class="px-2 py-1 rounded bg-rose-50 text-rose-700 border border-rose-200">OUT</span>
            @endif
          </td>
          <td class="p-3 text-right">
            Rp {{ number_format((float)$m->amount, 2, ',', '.') }}
          </td>
          <td class="p-3">{{ $m->memo ?: '—' }}</td>
          <td class="p-3">{{ $m->user_name ?: '—' }}</td>
        </tr>
      @empty
        <tr><td colspan="7" class="p-6 text-center text-gray-500">Belum ada mutasi pada periode ini.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="mt-3">
  {{ $moves->links() }}
</div>
