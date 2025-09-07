@extends('layouts.app')

@section('content')
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-lg font-semibold text-gray-800">Daftar Projek Terpasang</h2>
    <a href="{{ route('projects.create') }}"
       class="px-4 py-2 rounded-xl bg-blue-600 text-white text-sm hover:bg-blue-700 shadow-soft-lg">
      + Buat Proyek
    </a>
  </div>

  <div class="bg-white rounded-xl shadow-soft-lg overflow-hidden">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-3 py-2 text-left font-medium text-gray-600">Kode</th>
          <th class="px-3 py-2 text-left font-medium text-gray-600">Judul</th>
          <th class="px-3 py-2 text-left font-medium text-gray-600">Customer</th>
          <th class="px-3 py-2 text-left font-medium text-gray-600">Status</th>
          <th class="px-3 py-2 text-left font-medium text-gray-600">Tanggal</th>
          <th class="px-3 py-2 text-right font-medium text-gray-600">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 bg-white">
        @forelse ($projects as $p)
          <tr>
            <td class="px-3 py-2 font-medium text-gray-900">{{ $p->code }}</td>
            <td class="px-3 py-2 text-gray-800">{{ $p->title }}</td>
            <td class="px-3 py-2 text-gray-700">{{ $p->customer_name ?? '—' }}</td>
<td class="px-3 py-2">
  @php
    $badgeClass = match($p->status) {
      'READY_TO_BILL' => 'bg-amber-50 text-amber-700 border border-amber-200',
      'PAID'          => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
      'PARTIAL'       => 'bg-indigo-50 text-indigo-700 border border-indigo-200',
      'WAITING_RETURN'=> 'bg-sky-50 text-sky-700 border border-sky-200',
      default         => 'bg-gray-50 text-gray-700 border border-gray-200',
    };
  @endphp
  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs {{ $badgeClass }}">
    {{ $p->status }}
  </span>
</td>

            <td class="px-3 py-2 text-gray-600">{{ \Carbon\Carbon::parse($p->created_at)->format('d/m/Y H:i') }}</td>
            <td class="px-3 py-2">
              <div class="flex items-center justify-end gap-2">
                @if($p->status === 'READY_TO_BILL')
                  <a href="{{ route('projects.bill.show', $p->id) }}"
                     class="px-3 py-1.5 rounded-lg text-xs bg-emerald-600 text-white hover:bg-emerald-700">
                    Bayar
                  </a>
                @endif
                <a href="{{ route('projects.print.invoice.byproject', $p->id) }}"
                   class="px-3 py-1.5 rounded-lg text-xs bg-indigo-600 text-white hover:bg-indigo-700">
                  Invoice
                </a>
                <a href="{{ route('projects.return.form', $p->id) }}"
                   class="px-3 py-1.5 rounded-lg text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100">
                  Return/Sisa
                </a>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="px-3 py-6 text-center text-gray-500">Belum ada proyek.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">
    {{ $projects->links() }}
  </div>
@endsection
