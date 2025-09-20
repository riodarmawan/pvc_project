@extends('layouts.app')

@section('content')
@php
  $currency = fn($n) => 'Rp '.number_format((float)$n,0,',','.');
@endphp

<div class="flex items-center justify-between mb-4">
  <h2 class="text-lg font-semibold text-gray-800">Buat Projek Terpasang</h2>
  <a href="{{ route('projects.index') }}"
     class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-sm hover:bg-slate-200 shadow-soft-lg">
    ← Kembali
  </a>
</div>



{{-- ========== KATALOG ==========
     Menampilkan stok (STORE) & HPP --}}
<div class="bg-white rounded-xl shadow-soft-lg p-4 mt-4">
  <div class="flex items-center justify-between mb-3">
    <h3 class="text-sm font-semibold text-gray-700">Katalog Produk</h3>
    <form action="{{ route('projects.cart.clear') }}" method="POST" class="ml-auto">
      @csrf
      <button class="text-xs px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100">
        Kosongkan Cart
      </button>
    </form>
  </div>

  <div class="overflow-hidden rounded-lg border border-gray-200">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-3 py-2 text-left font-medium text-gray-600">SKU</th>
          <th class="px-3 py-2 text-left font-medium text-gray-600">Nama</th>
          <th class="px-3 py-2 text-left font-medium text-gray-600">UOM</th>
          <th class="px-3 py-2 text-right font-medium text-gray-600">Stok</th>
          <th class="px-3 py-2 text-right font-medium text-gray-600">Harga</th>
          <th class="px-3 py-2 text-right font-medium text-gray-600">Qty</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 bg-white">
        @forelse ($products as $p)
          <tr>
            <td class="px-3 py-2 text-gray-700">{{ $p->sku }}</td>
            <td class="px-3 py-2 text-gray-800">{{ $p->name }}</td>
            <td class="px-3 py-2 text-gray-600">
              @php $u = $p->uom_id ? \DB::table('uoms')->where('id',$p->uom_id)->value('code') : '-'; @endphp
              {{ $u ?: '-' }}
            </td>
            <td class="px-3 py-2 text-right text-gray-700">{{ number_format($p->stock_qty,0,',','.') }}</td>
            <td class="px-3 py-2 text-right text-gray-800">{{ $currency($p->hpp) }}</td>
            <td class="px-3 py-2">
              <form action="{{ route('projects.cart.add') }}" method="POST" class="flex items-center justify-end gap-2">
                @csrf
                <input type="hidden" name="type" value="material">
                <input type="hidden" name="product_id" value="{{ $p->id }}">
                <input type="hidden" name="uom_id" value="{{ $p->uom_id }}">
                <input type="number" step="0.001" min="0.001" name="qty"
                       class="w-24 text-right px-2 py-1 border rounded-lg"
                       value="1">
                <button class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs hover:bg-blue-700">
                  Tambah
                </button>
              </form>
            </td>
            <td class="px-3 py-2"></td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="px-3 py-4 text-center text-gray-500">Tidak ada produk.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- ========== CUSTOMER ========== --}}
<div class="bg-white rounded-xl shadow-soft-lg p-4 mt-4">
  <div class="flex items-center justify-between mb-3">
    <h3 class="text-sm font-semibold text-gray-700">Customer</h3>
  </div>

  @php $c = session('project.customer'); @endphp
  @if ($c)
    <div class="p-3 rounded-lg border border-emerald-200 bg-emerald-50 text-sm text-emerald-800 mb-4">
      <div class="font-medium">Terpilih: {{ $c['name'] }}</div>
      <div class="text-xs text-emerald-700">Telp: {{ $c['phone'] ?? '—' }} · {{ $c['address'] ?? '—' }}</div>
    </div>
  @else
    <div class="p-3 rounded-lg border border-amber-200 bg-amber-50 text-sm text-amber-800 mb-4">
      Belum ada customer terpilih.
    </div>
  @endif

  <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
    <form action="{{ route('projects.customer.quick') }}" method="POST" class="md:col-span-7 bg-gray-50 p-3 rounded-lg border">
      @csrf
      <div class="text-xs font-semibold text-gray-600 mb-2">Buat Cepat Customer</div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="block text-xs text-gray-600 mb-1">Nama</label>
          <input name="name" required class="w-full px-3 py-2 border rounded-lg" placeholder="Nama customer">
        </div>
        <div>
          <label class="block text-xs text-gray-600 mb-1">Telepon</label>
          <input name="phone" class="w-full px-3 py-2 border rounded-lg" placeholder="08xxxxxxxxxx">
        </div>
        <div class="md:col-span-2">
          <label class="block text-xs text-gray-600 mb-1">Alamat</label>
          <textarea name="address" rows="2" class="w-full px-3 py-2 border rounded-lg" placeholder="Alamat..."></textarea>
        </div>
      </div>
      <div class="mt-3">
        <button class="px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700">
          Simpan & Pilih
        </button>
      </div>
    </form>

    <form action="{{ route('projects.customer.select') }}" method="POST" class="md:col-span-5 bg-gray-50 p-3 rounded-lg border">
      @csrf
      <div class="text-xs font-semibold text-gray-600 mb-2">Pilih Cepat</div>
      <select name="customer_id" class="w-full px-3 py-2 border rounded-lg mb-3">
        @foreach ($customers as $row)
          <option value="{{ $row->id }}">{{ $row->name }} @if($row->phone) — {{ $row->phone }} @endif</option>
        @endforeach
      </select>
      <button class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">
        Pilih
      </button>
    </form>
  </div>
</div>

{{-- ========== LEFTOVER PANEL ========== --}}
<div class="bg-white rounded-xl shadow-soft-lg p-4 mt-4">
  <h3 class="text-sm font-semibold text-gray-700 mb-3">Potongan Sisa Tersedia</h3>

  <div class="overflow-hidden rounded-lg border border-gray-200">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-3 py-2 text-left font-medium text-gray-600">SKU</th>
          <th class="px-3 py-2 text-left font-medium text-gray-600">Produk</th>
          <th class="px-3 py-2 text-right font-medium text-gray-600">Panjang (m)</th>
          <th class="px-3 py-2 text-right font-medium text-gray-600">Harga (per m)</th> {{-- baru --}}
          <th class="px-3 py-2 text-right font-medium text-gray-600">Pakai (m)</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>

      <tbody class="divide-y divide-gray-100 bg-white">
        @forelse ($leftovers as $lp)
          <tr>
            <td class="px-3 py-2 text-gray-700">{{ $lp->sku }}</td>
            <td class="px-3 py-2 text-gray-800">{{ $lp->product_name }}</td>
            <td class="px-3 py-2 text-right text-gray-700">{{ number_format($lp->length_m, 0) }}</td>

            {{-- harga per meter dikirim lewat form yang sama --}}
            <td class="px-3 py-2">
              <input
                form="lf-{{ $lp->id }}"
                name="price"
                type="number"
                min="0"
                step="0"
                inputmode="decimal"
                class="w-28 text-right px-2 py-1 border rounded-lg"
                placeholder="0">
            </td>

            <td class="px-3 py-2">
              <form
                id="lf-{{ $lp->id }}"
                action="{{ route('projects.cart.add') }}"
                method="POST"
                class="flex items-center justify-end gap-2">
                @csrf
                <input type="hidden" name="type" value="leftover">
                <input type="hidden" name="piece_id" value="{{ $lp->id }}">
                <input
                  type="number"
                  step="0"
                  min="0"
                  max="{{ $lp->length_m }}"
                  name="used_length_m"
                  inputmode="decimal"
                  class="w-28 text-right px-2 py-1 border rounded-lg"
                  value="{{ $lp->length_m }}">
              </form>
            </td>

            <td class="px-3 py-2">
              <button
                form="lf-{{ $lp->id }}"
                class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs hover:bg-emerald-700">
                Pakai
              </button>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="px-3 py-4 text-center text-gray-500">Tidak ada potongan sisa.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>


{{-- ========== JASA / SERVICE ========== --}}
<div class="bg-white rounded-xl shadow-soft-lg p-4 mt-4">
  <h3 class="text-sm font-semibold text-gray-700 mb-3">Jasa / Service</h3>

  {{-- Form tambah service --}}
  <form action="{{ route('projects.cart.add') }}" method="POST" class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-4">
    @csrf
    <input type="hidden" name="type" value="service">
    <div class="md:col-span-7">
      <label class="block text-xs text-gray-600 mb-1">Nama Service</label>
      <input name="name" type="text" required class="w-full px-3 py-2 border rounded-lg" placeholder="Contoh: Ongkir, Tukang, Konsultasi">
    </div>
    <div class="md:col-span-3">
      <label class="block text-xs text-gray-600 mb-1">Harga</label>
      <input name="price" type="number" min="0" step="0.01" required class="w-full px-3 py-2 border rounded-lg" placeholder="0">
    </div>
    <div class="md:col-span-2 flex items-end">
      <button class="w-full px-3 py-2 rounded-lg bg-purple-600 text-white text-sm hover:bg-purple-700">Tambah Service</button>
    </div>
  </form>

  {{-- Daftar service dengan titik-titik --}}
  <div class="border rounded-lg overflow-hidden">
    <div class="px-3 py-2 bg-gray-50 text-xs font-semibold text-gray-600">Daftar Service</div>
    <div class="p-3">
      @php $services = session('project.services', []); @endphp
      @forelse ($services as $s)
        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
          {{-- Nama service --}}
          <div class="flex items-center text-gray-700">
            <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
              <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a1 1 0 001 1h6a1 1 0 001-1V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
            </svg>
            <span class="font-medium">{{ $s['name'] }}</span>
          </div>

          {{-- Titik-titik pemisah --}}
          <div class="flex-1 mx-3 border-b border-dotted border-gray-300"></div>

          {{-- Harga dan tombol hapus --}}
          <div class="flex items-center gap-3">
            <span class="text-gray-800 font-semibold">Rp {{ number_format($s['price'], 0, ',', '.') }}</span>
            
            <form action="{{ route('projects.cart.remove') }}" method="POST" class="inline-block">
              @csrf
              <input type="hidden" name="kind" value="service">
              <input type="hidden" name="row_id" value="{{ $s['row_id'] }}">
              <button type="submit" class="text-xs px-2 py-1 rounded bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 transition-colors duration-150">
                Hapus
              </button>
            </form>
          </div>
        </div>
      @empty
        <div class="text-center py-6 text-gray-500">
          <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l2.293 2.293a1 1 0 11-1.414 1.414L5 6.414V8a1 1 0 01-2 0V4zm9 1a1 1 0 010-2h4a1 1 0 011 1v4a1 1 0 01-2 0V6.414l-2.293 2.293a1 1 0 11-1.414-1.414L13.586 5H12zm-9 7a1 1 0 012 0v1.586l2.293-2.293a1 1 0 111.414 1.414L6.414 15H8a1 1 0 010 2H4a1 1 0 01-1-1v-4zm13-1a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 010-2h1.586l-2.293-2.293a1 1 0 111.414-1.414L15 13.586V12a1 1 0 011-1z" clip-rule="evenodd"></path>
          </svg>
          <p class="text-sm">Belum ada service.</p>
        </div>
      @endforelse
    </div>
  </div>
</div>

{{-- ========== RINGKASAN & PEMBAYARAN ========== --}}
@php
  $serviceTotal = collect($services)->sum(fn($s)=> (float)($s['price'] ?? 0));
    $lefts = $cart['leftovers'] ?? [];
$leftoverTotal = collect($lefts)->sum(function($r){
    $len = (float)($r['used_length_m'] ?? 0);
    // Gunakan 'price_m' dulu, jika tidak ada baru fallback ke 'price'
    $pr  = (float)($r['price_m'] ?? ($r['price'] ?? 0));
    return $len * $pr;
});
@endphp
<div class="bg-white rounded-xl shadow-soft-lg p-4 mt-4">
  <h3 class="text-sm font-semibold text-gray-700 mb-3">Ringkasan & Pembayaran</h3>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="border rounded-lg overflow-hidden">
      <div class="px-3 py-2 bg-gray-50 text-xs font-semibold text-gray-600">Ringkasan</div>
      <div class="p-3 text-sm space-y-1">
        <div class="flex items-center justify-between">
          <span class="text-gray-700">Total Materials</span>
          <span class="font-medium text-gray-900">{{ $currency($sum['total_materials']) }}</span>
        </div>
        <div class="flex items-center justify-between mt-2">
          <span class="text-gray-800">Total Potongan Sisa</span>
          <span class="font-semibold text-gray-900">
            Rp {{ number_format($leftoverTotal,0,',','.') }}
          </span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-gray-700">Total Jasa</span>
          <span class="font-medium text-gray-900">{{ $currency($sum['total_services']) }}</span>
        </div>
        
        <!-- SUBTOTAL SEBELUM DISKON -->
        <div class="flex items-center justify-between border-t pt-2 mt-2">
          <span class="font-medium text-gray-700">Subtotal</span>
          <span class="font-medium text-gray-800" id="subtotal-amount" data-subtotal="{{ (float)$sum['grand_total'] }}">
            {{ $currency($sum['grand_total']) }}
          </span>
        </div>
        
        <!-- DISKON -->
        <div class="flex items-center justify-between">
          <span class="text-red-600">Diskon</span>
          <span class="text-red-600" id="discount-amount">Rp 0</span>
        </div>
        
        <!-- GRAND TOTAL SETELAH DISKON -->
        <div class="flex items-center justify-between border-t pt-2 mt-2">
          <span class="font-semibold text-gray-800">Grand Total</span>
          <span class="font-semibold text-gray-900" id="grand-total" data-grand="{{ (float)$sum['grand_total'] }}">
            {{ $currency($sum['grand_total']) }}
          </span>
        </div>
      </div>
    </div>

    <form action="{{ route('projects.finalize') }}" method="POST" class="border rounded-lg overflow-hidden">
      @csrf
      <div class="px-3 py-2 bg-gray-50 text-xs font-semibold text-gray-600">Pembayaran</div>
      <div class="p-3 text-sm space-y-3">
        <div>
          <label class="block text-xs text-gray-600 mb-1">Judul Proyek</label>
          <input name="title" required class="w-full px-3 py-2 border rounded-lg" placeholder="Nama/Alamat Proyek">
        </div>
        <div>
          <label class="block text-xs text-gray-600 mb-1">Customer (opsional)</label>
          <input type="number" name="customer_id" class="w-full px-3 py-2 border rounded-lg"
                 value="{{ session('project.customer.id') }}">
        </div>
        
        <!-- DISKON & CATATAN -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs text-gray-600 mb-1">Diskon Total</label>
            <input type="number" name="discount" id="discount-input" step="0.01" min="0" 
                   class="w-full px-3 py-2 border rounded-lg" 
                   placeholder="Masukkan nilai diskon">
            <p class="text-xs text-gray-500 mt-1">Masukkan nilai diskon dalam rupiah</p>
          </div>
          <div>
            <label class="block text-xs text-gray-600 mb-1">Catatan</label>
            <textarea name="notes" rows="3" 
                      class="w-full px-3 py-2 border rounded-lg resize-both" 
                      style="min-height: 76px; max-height: 200px;"
                      placeholder="Catatan tambahan untuk proyek ini..."></textarea>
          </div>
        </div>
        
        <!-- METODE PEMBAYARAN (TANPA REF/NO) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs text-gray-600 mb-1">Metode Pembayaran</label>
            <select name="pay_method" class="w-full px-3 py-2 border rounded-lg" required>
              <option value="CASH">CASH</option>
              <option value="CARD">CARD</option>
              <option value="QR">QR</option>
              <option value="TRANSFER">TRANSFER</option>
            </select>
          </div>
          <div>
            <label class="block text-xs text-gray-600 mb-1">Nominal Bayar</label>
            <input type="number" name="pay_amount" id="pay-amount" step="0.01" min="0"
                   class="w-full px-3 py-2 border rounded-lg" required placeholder="Masukkan jumlah pembayaran">
          </div>
        </div>

        <div class="flex items-center justify-between rounded-lg bg-slate-50 border border-slate-200 px-3 py-2">
          <span class="text-sm text-slate-700">Kembalian</span>
          <span class="text-base font-semibold text-emerald-700" id="pay-change">Rp 0</span>
        </div>

        <div class="pt-2 text-right">
          <button class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700">
            Finalize & Bayar
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- JS untuk kalkulasi diskon dan kembalian --}}
<script>
  (function(){
    const subtotalElement = document.getElementById('subtotal-amount');
    const discountInput = document.getElementById('discount-input');
    const discountDisplay = document.getElementById('discount-amount');
    const grandTotalElement = document.getElementById('grand-total');
    const payInput = document.getElementById('pay-amount');
    const changeOutput = document.getElementById('pay-change');
    
    const subtotal = Number(subtotalElement?.dataset?.subtotal || 0);
    
    function fmt(n){ return 'Rp ' + (Math.round(n)).toLocaleString('id-ID'); }
    
    function updateTotals(){
      const discountValue = Number(discountInput?.value || 0);
      const grandTotal = Math.max(0, subtotal - discountValue);
      
      // Update tampilan
      if (discountDisplay) discountDisplay.textContent = fmt(discountValue);
      if (grandTotalElement) {
        grandTotalElement.textContent = fmt(grandTotal);
        grandTotalElement.dataset.grand = grandTotal;
      }
      
      // Update kembalian
      updateChange();
    }
    
    function updateChange(){
      const grandTotal = Number(grandTotalElement?.dataset?.grand || 0);
      const paid = Number(payInput?.value || 0);
      const change = Math.max(0, paid - grandTotal);
      if (changeOutput) changeOutput.textContent = fmt(change);
    }
    
    // Event listeners
    if (discountInput) discountInput.addEventListener('input', updateTotals);
    if (payInput) payInput.addEventListener('input', updateChange);
    
    // Initial update
    updateTotals();
  })();
</script>


@endsection
