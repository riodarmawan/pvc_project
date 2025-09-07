@php
  /** @var \Illuminate\Pagination\LengthAwarePaginator $products */
@endphp

<div class="rounded-2xl border border-slate-200 bg-white shadow-card overflow-hidden">
  {{-- Header --}}
  <div class="px-4 sm:px-6 py-4 border-b border-slate-200">
    <h3 class="inline-flex items-center gap-2 text-base font-semibold text-slate-900">
      <span class="h-9 w-9 rounded-xl bg-gradient-to-br from-indigo-400/25 to-blue-400/25 grid place-items-center">
        <svg viewBox="0 0 20 20" class="h-4 w-4 text-indigo-600" fill="currentColor">
          <path fill-rule="evenodd" d="M10 2L3 7v11a2 2 0 002 2h10a2 2 0 002-2V7l-7-5zM8 15V9h4v6H8z" clip-rule="evenodd"></path>
        </svg>
      </span>
      Daftar Produk
    </h3>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="text-left text-slate-500">
        <tr class="border-b border-slate-200">
          <th class="py-3 px-4 sm:px-6">Produk</th>
          <th class="py-3 px-4 sm:px-6 text-right w-32">Harga</th>
          <th class="py-3 px-4 sm:px-6 text-center w-24">Stok</th>
          <th class="py-3 px-4 sm:px-6 text-center w-48">Tambah ke Keranjang</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200">
        @forelse ($products as $p)
          @include('kasir.partials._product_row', ['p' => $p])
        @empty
          <tr>
            <td colspan="4" class="py-12 px-4 sm:px-6">
              <div class="max-w-md mx-auto text-center space-y-3">
                <div class="mx-auto h-14 w-14 rounded-2xl bg-slate-100 grid place-items-center">
                  <svg viewBox="0 0 24 24" class="h-7 w-7 text-slate-400" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                  </svg>
                </div>
                <div>
                  <h3 class="text-base font-semibold text-slate-900">Tidak ada produk</h3>
                  <p class="text-sm text-slate-500">Produk tidak ditemukan dengan kriteria pencarian.</p>
                </div>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="mt-4 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-card">
  {{ $products->links() }}
</div>
