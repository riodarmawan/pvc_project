@extends('layouts.app')

@section('content')
@php
  $currency = fn($n) => 'Rp '.number_format((float)$n,0,',','.');
  $matSum = collect($cart['materials'] ?? [])->sum(fn($m) => (float)($m['qty'] ?? 0) * (float)($m['price'] ?? 0));
  $lefts = $cart['leftovers'] ?? [];
  $leftoverTotal = collect($lefts)->sum(function($r){
    $len = (float)($r['used_length_m'] ?? 0);
    $pr  = (float)($r['price_m'] ?? ($r['price'] ?? 0));
    return $len * $pr;
  });
  $serviceTotal = collect($services ?? [])->sum(fn($s) => (float)($s['price'] ?? 0));
  $grandTotal = $matSum + $leftoverTotal + $serviceTotal;

  // Group products by category for filter chips
  $categories = $products->pluck('name')->map(function($name) { return \Illuminate\Support\Str::limit($name, 20); });
@endphp

<style>
  .proj-scroll { height: calc(100vh - 120px); }
  @media (max-width: 1023px) { .proj-scroll { height: auto; } }
</style>

<div class="flex items-center justify-between mb-3">
  <div class="flex items-center gap-3">
    <h1 class="text-lg font-bold text-gray-800">Buat Projek Terpasang</h1>
    @if(($cart['materials'] ?? []) || ($cart['leftovers'] ?? []) || ($cart['services'] ?? []))
      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
        {{ count($cart['materials'] ?? []) }} material · {{ count($cart['leftovers'] ?? []) }} sisa · {{ count($services ?? []) }} jasa
      </span>
    @endif
  </div>
  <a href="{{ route('projects.index') }}" class="text-sm text-slate-500 hover:text-slate-700 flex items-center gap-1">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
    Kembali
  </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

  {{-- ============================================================
       LEFT COLUMN: CATALOG + LEFTOVER + SERVICES
       ============================================================ --}}
  <div class="lg:col-span-7 space-y-4">

    {{-- === CATALOG === --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      {{-- Search + Filter --}}
      <div class="p-3 border-b border-gray-100 bg-gray-50/80">
        <div class="flex items-center gap-2 mb-2">
          <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" id="catalog-search" placeholder="Cari produk (nama, SKU)..."
                   class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
          </div>
          <button onclick="clearCatalogSearch()" class="px-2 py-2 text-gray-400 hover:text-gray-600" title="Reset pencarian">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <div class="flex gap-1.5 overflow-x-auto pb-1 scrollbar-hide" id="category-chips">
          <button onclick="filterCategory('all')" data-cat="all" class="cat-chip active px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap transition-all">
            Semua
          </button>
          @php
            $cats = $products->groupBy(function($p) {
              return \DB::table('product_categories')->where('id', $p->category_id)->value('name') ?? 'Lainnya';
            });
          @endphp
          @foreach($cats as $catName => $catProducts)
            <button onclick="filterCategory('{{ Str::slug($catName) }}')" data-cat="{{ Str::slug($catName) }}" class="cat-chip px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap transition-all">
              {{ $catName }} <span class="opacity-60">({{ $catProducts->count() }})</span>
            </button>
          @endforeach
        </div>
      </div>

      {{-- Product Grid --}}
      <div class="p-3 max-h-[60vh] overflow-y-auto" id="catalog-grid">
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-2" id="product-list">
          @forelse ($products as $p)
            <div class="product-card group border border-gray-200 rounded-lg p-2.5 hover:border-emerald-300 hover:shadow-sm transition-all cursor-default"
                 data-name="{{ strtolower($p->name) }}"
                 data-sku="{{ strtolower($p->sku) }}"
                 data-cat="{{ Str::slug(\DB::table('product_categories')->where('id', $p->category_id)->value('name') ?? 'lainnya') }}">
              <div class="flex items-start justify-between gap-2">
                <div class="min-w-0 flex-1">
                  <div class="text-xs text-gray-500 truncate">{{ $p->sku }}</div>
                  <div class="text-sm font-medium text-gray-800 truncate">{{ $p->name }}</div>
                  <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">
                      {{ \DB::table('uoms')->where('id',$p->uom_id)->value('code') ?? '-' }}
                    </span>
                    <span class="text-xs {{ $p->stock_qty > 10 ? 'text-emerald-600' : ($p->stock_qty > 0 ? 'text-amber-600' : 'text-red-600') }}">
                      Stok: {{ number_format($p->stock_qty,0,',','.') }}
                    </span>
                  </div>
                </div>
                <div class="text-right shrink-0">
                  <div class="text-xs text-gray-400">HPP</div>
                  <div class="text-sm font-semibold text-gray-800">{{ $currency($p->hpp) }}</div>
                </div>
              </div>
              {{-- Quick add --}}
              <form action="{{ route('projects.cart.add') }}" method="POST" class="mt-2 flex items-center gap-1.5">
                @csrf
                <input type="hidden" name="type" value="material">
                <input type="hidden" name="product_id" value="{{ $p->id }}">
                <input type="hidden" name="uom_id" value="{{ $p->uom_id }}">
                <input type="number" step="0.001" min="0.001" name="qty" value="1"
                       class="w-16 text-center text-xs px-1.5 py-1 border rounded-md focus:ring-1 focus:ring-emerald-500">
                <button class="flex-1 px-2 py-1 rounded-md bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700 active:scale-95 transition-all">
                  + Tambah
                </button>
              </form>
            </div>
          @empty
            <div class="col-span-full py-8 text-center text-gray-400 text-sm">
              <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
              Tidak ada produk ditemukan.
            </div>
          @endforelse
        </div>
      </div>
    </div>

    {{-- === LEFTOVER PANEL === --}}
    @if(count($leftovers ?? []) > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="p-3 border-b border-gray-100 bg-amber-50/80">
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
          <h3 class="text-sm font-semibold text-amber-800">Potongan Sisa Tersedia</h3>
        </div>
      </div>
      <div class="p-3">
        <div class="space-y-2">
          @foreach ($leftovers as $lp)
            <div class="flex items-center gap-3 p-2.5 border border-amber-200 rounded-lg bg-amber-50/50">
              <div class="flex-1 min-w-0">
                <div class="text-sm font-medium text-gray-800">{{ $lp->product_name }}</div>
                <div class="text-xs text-gray-500">{{ $lp->sku }} · {{ number_format($lp->length_m, 0) }}m tersedia</div>
              </div>
              <form id="lf-{{ $lp->id }}" action="{{ route('projects.cart.add') }}" method="POST" class="flex items-center gap-1.5">
                @csrf
                <input type="hidden" name="type" value="leftover">
                <input type="hidden" name="piece_id" value="{{ $lp->id }}">
                <input name="price" type="number" min="0" step="0" inputmode="decimal" placeholder="Harga/m"
                       class="w-24 text-right text-xs px-2 py-1.5 border rounded-md" form="lf-{{ $lp->id }}">
                <input type="number" step="0" min="0" max="{{ $lp->length_m }}" name="used_length_m" value="{{ $lp->length_m }}" inputmode="decimal"
                       class="w-16 text-right text-xs px-2 py-1.5 border rounded-md">
                <button form="lf-{{ $lp->id }}" class="px-3 py-1.5 rounded-md bg-amber-600 text-white text-xs font-medium hover:bg-amber-700">
                  Pakai
                </button>
              </form>
            </div>
          @endforeach
        </div>
      </div>
    </div>
    @endif

    {{-- === SERVICES === --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="p-3 border-b border-gray-100 bg-purple-50/80">
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          <h3 class="text-sm font-semibold text-purple-800">Jasa / Service</h3>
        </div>
      </div>
      <div class="p-3">
        <form action="{{ route('projects.cart.add') }}" method="POST" class="flex items-end gap-2 mb-3">
          @csrf
          <input type="hidden" name="type" value="service">
          <div class="flex-1">
            <label class="block text-xs text-gray-500 mb-1">Nama</label>
            <input name="name" type="text" required class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-purple-500" placeholder="Ongkir, Tukang, dll">
          </div>
          <div class="w-36">
            <label class="block text-xs text-gray-500 mb-1">Harga</label>
            <input name="price" type="number" min="0" step="0.01" required class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-purple-500" placeholder="0">
          </div>
          <button class="px-4 py-2 rounded-lg bg-purple-600 text-white text-sm font-medium hover:bg-purple-700 shrink-0">
            + Tambah
          </button>
        </form>

        @if(count($services ?? []) > 0)
          <div class="space-y-1.5">
            @foreach($services as $s)
              <div class="flex items-center gap-2 p-2 border rounded-lg bg-gray-50">
                <form action="{{ route('projects.cart.update') }}" method="POST" class="flex items-center gap-2 flex-1">
                  @csrf
                  <input type="hidden" name="kind" value="service">
                  <input type="hidden" name="row_id" value="{{ $s['row_id'] }}">
                  <input name="name" type="text" required value="{{ $s['name'] }}" class="flex-1 px-2 py-1.5 text-sm border rounded-md">
                  <input name="price" type="number" min="0" step="0.01" required value="{{ $s['price'] }}" class="w-28 text-right px-2 py-1.5 text-sm border rounded-md">
                  <button class="px-2 py-1.5 text-xs text-blue-600 hover:bg-blue-50 rounded-md" title="Update">Update</button>
                </form>
                <form action="{{ route('projects.cart.remove') }}" method="POST">
                  @csrf
                  <input type="hidden" name="kind" value="service">
                  <input type="hidden" name="row_id" value="{{ $s['row_id'] }}">
                  <button class="px-2 py-1.5 text-xs text-red-500 hover:bg-red-50 rounded-md" title="Hapus">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  </button>
                </form>
              </div>
            @endforeach
          </div>
        @else
          <div class="text-xs text-gray-400 text-center py-2">Belum ada jasa.</div>
        @endif
      </div>
    </div>
  </div>

  {{-- ============================================================
       RIGHT COLUMN: STICKY CART + SUMMARY + PAYMENT
       ============================================================ --}}
  <div class="lg:col-span-5">
    <div class="lg:sticky lg:top-20 space-y-4">

      {{-- === CART === --}}
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-3 border-b border-gray-100 bg-emerald-50/80 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
            <h3 class="text-sm font-semibold text-emerald-800">Keranjang Proyek</h3>
          </div>
          @if(($cart['materials'] ?? []) || ($cart['leftovers'] ?? []))
            <form action="{{ route('projects.cart.clear') }}" method="POST" class="inline">
              @csrf
              <button onclick="return confirm('Kosongkan semua item?')" class="text-xs text-red-500 hover:text-red-700 font-medium">Kosongkan</button>
            </form>
          @endif
        </div>

        <div class="p-3 max-h-[50vh] overflow-y-auto">
          {{-- Materials --}}
          @if(count($cart['materials'] ?? []) > 0)
            <div class="mb-3">
              <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Materials</div>
              <div class="space-y-1">
                @foreach($cart['materials'] as $m)
                  @php
                    $price = (float)($m['price'] ?? 0);
                    $sub = (float)($m['qty'] ?? 0) * $price;
                  @endphp
                  <div class="flex items-center gap-2 p-2 rounded-lg bg-gray-50 border border-gray-100">
                    <div class="flex-1 min-w-0">
                      <div class="text-sm font-medium text-gray-800 truncate">{{ $m['name'] }}</div>
                      <div class="text-xs text-gray-500">{{ $m['uom'] }} · {{ $m['qty'] }} × {{ $currency($price) }}</div>
                    </div>
                    <div class="text-sm font-semibold text-gray-800 shrink-0">{{ $currency($sub) }}</div>
                    <form action="{{ route('projects.cart.remove') }}" method="POST" class="shrink-0">
                      @csrf
                      <input type="hidden" name="kind" value="material">
                      <input type="hidden" name="row_id" value="{{ $m['row_id'] }}">
                      <button class="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 rounded" title="Hapus">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                      </button>
                    </form>
                  </div>
                @endforeach
              </div>
            </div>
          @endif

          {{-- Leftovers --}}
          @if(count($cart['leftovers'] ?? []) > 0)
            <div class="mb-3">
              <div class="text-xs font-semibold text-amber-600 uppercase tracking-wide mb-1.5">Potongan Sisa</div>
              <div class="space-y-1">
                @foreach($cart['leftovers'] as $l)
                  @php
                    $pm = (float)($l['price_m'] ?? ($l['price'] ?? 0));
                    $lm = (float)($l['used_length_m'] ?? 0);
                    $lsub = $lm * $pm;
                  @endphp
                  <div class="flex items-center gap-2 p-2 rounded-lg bg-amber-50 border border-amber-100">
                    <div class="flex-1 min-w-0">
                      <div class="text-sm font-medium text-gray-800 truncate">{{ $l['name'] }}</div>
                      <div class="text-xs text-gray-500">{{ $lm }}m × {{ $currency($pm) }}/m</div>
                    </div>
                    <div class="text-sm font-semibold text-gray-800 shrink-0">{{ $currency($lsub) }}</div>
                    <form action="{{ route('projects.cart.remove') }}" method="POST" class="shrink-0">
                      @csrf
                      <input type="hidden" name="kind" value="leftover">
                      <input type="hidden" name="row_id" value="{{ $l['row_id'] }}">
                      <button class="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 rounded" title="Hapus">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                      </button>
                    </form>
                  </div>
                @endforeach
              </div>
            </div>
          @endif

          {{-- Empty state --}}
          @if(empty($cart['materials'] ?? []) && empty($cart['leftovers'] ?? []))
            <div class="text-center py-6">
              <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
              <p class="text-sm text-gray-400">Belum ada item di keranjang.</p>
              <p class="text-xs text-gray-400 mt-1">Pilih produk dari katalog di sebelah kiri.</p>
            </div>
          @endif
        </div>
      </div>

      {{-- === CUSTOMER + SUMMARY + PAYMENT === --}}
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        {{-- Customer --}}
        <div class="p-3 border-b border-gray-100">
          <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Customer</div>
          @php $c = session('project.customer'); @endphp
          @if($c)
            <div class="flex items-center gap-2 p-2 rounded-lg bg-emerald-50 border border-emerald-200">
              <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700 font-semibold text-sm">
                {{ strtoupper(substr($c['name'] ?? 'X', 0, 1)) }}
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-sm font-medium text-emerald-800">{{ $c['name'] }}</div>
                <div class="text-xs text-emerald-600">{{ $c['phone'] ?? '—' }} · {{ $c['address'] ?? '—' }}</div>
              </div>
            </div>
          @else
            <div class="flex items-center gap-3">
              <div class="flex-1">
                <select id="customer-select" class="w-full px-3 py-2 text-sm border rounded-lg">
                  <option value="">Pilih customer...</option>
                  @foreach($customers as $row)
                    <option value="{{ $row->id }}">{{ $row->name }} @if($row->phone) — {{ $row->phone }} @endif</option>
                  @endforeach
                </select>
              </div>
              <form action="{{ route('projects.customer.select') }}" method="POST" class="flex gap-1">
                @csrf
                <input type="hidden" name="customer_id" id="customer-id-input" value="">
                <button class="px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Pilih</button>
              </form>
            </div>
          @endif
        </div>

        {{-- Summary --}}
        <div class="p-3 border-b border-gray-100">
          <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Ringkasan</div>
          <div class="space-y-1.5">
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Materials</span>
              <span class="font-medium">{{ $currency($matSum) }}</span>
            </div>
            @if($leftoverTotal > 0)
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Potongan Sisa</span>
              <span class="font-medium">{{ $currency($leftoverTotal) }}</span>
            </div>
            @endif
            @if($serviceTotal > 0)
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Jasa</span>
              <span class="font-medium">{{ $currency($serviceTotal) }}</span>
            </div>
            @endif
            <div class="flex justify-between text-sm pt-2 border-t border-gray-200">
              <span class="font-bold text-gray-800">Grand Total</span>
              <span class="font-bold text-emerald-700 text-lg">{{ $currency($grandTotal) }}</span>
            </div>
          </div>
        </div>

        {{-- Payment --}}
        <form action="{{ route('projects.finalize') }}" method="POST" class="p-3">
          @csrf
          <div class="space-y-3">
            <div>
              <label class="block text-xs text-gray-500 mb-1">Judul Proyek <span class="text-red-500">*</span></label>
              <input name="title" required class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-emerald-500" placeholder="Nama/Alamat Proyek">
            </div>
            <input type="hidden" name="customer_id" value="{{ session('project.customer.id') ?? '' }}">

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-gray-500 mb-1">Metode Bayar</label>
                <select name="pay_method" class="w-full px-3 py-2 text-sm border rounded-lg" required>
                  <option value="CASH">💵 CASH</option>
                  <option value="CARD">💳 CARD</option>
                  <option value="QR">📱 QR</option>
                  <option value="TRANSFER">🏦 TRANSFER</option>
                </select>
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">Nominal Bayar <span class="text-red-500">*</span></label>
                <input type="number" name="pay_amount" id="pay-amount" step="0.01" min="0" required
                       class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-emerald-500" placeholder="0">
              </div>
            </div>

            <div>
              <label class="block text-xs text-gray-500 mb-1">Ref / Catatan</label>
              <input type="text" name="pay_ref" class="w-full px-3 py-2 text-sm border rounded-lg" placeholder="No ref (opsional)">
            </div>

            <div class="flex items-center justify-between p-2.5 rounded-lg bg-slate-100 border border-slate-200">
              <span class="text-sm text-slate-600">Kembalian</span>
              <span class="text-base font-bold text-emerald-700" id="pay-change">Rp 0</span>
            </div>

            <button type="submit" id="btn-finalize"
                    class="w-full py-3 rounded-lg bg-emerald-600 text-white font-semibold hover:bg-emerald-700 active:scale-[0.98] transition-all text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                    {{ $grandTotal <= 0 ? 'disabled' : '' }}>
              Finalize & Bayar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<style>
  .cat-chip { background: #f1f5f9; color: #64748b; }
  .cat-chip.active { background: #059669; color: white; }
  .cat-chip:hover:not(.active) { background: #e2e8f0; }
  .scrollbar-hide::-webkit-scrollbar { display: none; }
  .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // === Customer select sync ===
  const custSel = document.getElementById('customer-select');
  const custInput = document.getElementById('customer-id-input');
  const custHidden = document.querySelector('input[name="customer_id"][type="hidden"]');
  if (custSel) {
    custSel.addEventListener('change', function() {
      if (custInput) custInput.value = this.value;
      if (custHidden) custHidden.value = this.value;
    });
  }

  // === Catalog search ===
  const searchInput = document.getElementById('catalog-search');
  if (searchInput) {
    searchInput.addEventListener('input', debounce(applyFilters, 200));
  }

  // === Category filter ===
  window.filterCategory = function(cat) {
    document.querySelectorAll('.cat-chip').forEach(c => c.classList.remove('active'));
    document.querySelector(`[data-cat="${cat}"]`)?.classList.add('active');
    applyFilters();
  };

  window.clearCatalogSearch = function() {
    if (searchInput) searchInput.value = '';
    filterCategory('all');
  };

  function applyFilters() {
    const query = (searchInput?.value || '').toLowerCase().trim();
    const activeCat = document.querySelector('.cat-chip.active')?.dataset?.cat || 'all';
    const cards = document.querySelectorAll('.product-card');

    cards.forEach(card => {
      const name = card.dataset.name || '';
      const sku = card.dataset.sku || '';
      const cat = card.dataset.cat || '';
      const matchSearch = !query || name.includes(query) || sku.includes(query);
      const matchCat = activeCat === 'all' || cat === activeCat;
      card.style.display = (matchSearch && matchCat) ? '' : 'none';
    });
  }

  // === Payment change calculation ===
  const grandTotal = {{ $grandTotal }};
  const payInput = document.getElementById('pay-amount');
  const payChange = document.getElementById('pay-change');

  function fmt(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); }

  if (payInput && payChange) {
    payInput.addEventListener('input', function() {
      const paid = Number(this.value || 0);
      const change = paid - grandTotal;
      payChange.textContent = fmt(change > 0 ? change : 0);
      payChange.className = change >= 0
        ? 'text-base font-bold text-emerald-700'
        : 'text-base font-bold text-red-600';
    });
  }

  // === Debounce utility ===
  function debounce(fn, ms) {
    let t;
    return function(...args) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), ms);
    };
  }
});
</script>

@endsection
