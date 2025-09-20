{{-- Ringkasan sebelum finalize --}}
@php
  $currency  = fn($n) => 'Rp '.number_format((float)$n,0,',','.');
  $materials = $cart['materials'] ?? [];
  $lefts     = $cart['leftovers'] ?? [];
  $services  = session('project.services', []);

  // Fallback kalkulasi di view jika $sum belum ada/berbeda
  $calcMaterials = collect($materials)->sum(function($m){
    $qty   = (float)($m['qty']   ?? 0);
    $price = (float)($m['price'] ?? 0);  // HPP sudah ditaruh saat add
    return $qty * $price;
  });

  $calcLeftovers = collect($lefts)->sum(function($l){
    $len   = (float)($l['used_length_m'] ?? 0);
    $price = (float)($l['price_m'] ?? ($l['price'] ?? 0)); // alias aman
    return $len * $price;
  });

  $serviceTotal = collect($services)->sum(fn($s)=> (float)($s['price'] ?? 0));

  // Ambil dari controller bila tersedia, kalau tidak pakai fallback di atas
  $totalMaterials = (float)($sum['total_materials'] ?? $calcMaterials);
  $totalLeftovers = (float)($sum['total_leftovers'] ?? $calcLeftovers);
  $grandTotal     = (float)($sum['grand_total']     ?? ($totalMaterials + $totalLeftovers + $serviceTotal));
@endphp

<div class="bg-white rounded-xl shadow-soft-lg p-4">
  <h3 class="text-sm font-semibold text-gray-700 mb-3">Ringkasan</h3>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    {{-- MATERIALS --}}
    <div class="border rounded-lg overflow-hidden">
      <div class="px-3 py-2 bg-gray-50 text-xs font-semibold text-gray-600">Materials</div>
      <div class="p-3 text-sm space-y-1">
        @forelse ($materials as $m)
          @php
            $qty   = (float)($m['qty'] ?? 0);
            $price = (float)($m['price'] ?? 0);
            $sub   = $qty * $price;
          @endphp
          <div class="grid grid-cols-12 gap-2 py-1 border-b last:border-0">
            <div class="col-span-6 text-gray-800">
              {{ $m['name'] }} <span class="text-gray-500 text-xs">({{ $m['uom'] }})</span>
            </div>
            <div class="col-span-2 text-right text-gray-700">{{ number_format($qty,3) }}</div>
            <div class="col-span-2 text-right text-gray-700">{{ $currency($price) }}</div>
            <div class="col-span-2 text-right text-gray-900 font-medium">{{ $currency($sub) }}</div>
          </div>
        @empty
          <div class="text-gray-500">—</div>
        @endforelse

        <div class="flex items-center justify-between pt-2 mt-2 border-t">
          <span class="text-gray-700 font-semibold">Total Materials</span>
          <span class="text-gray-900 font-semibold">{{ $currency($totalMaterials) }}</span>
        </div>
      </div>
    </div>

    {{-- LEFTOVERS (pemakaian potongan) --}}
    <div class="border rounded-lg overflow-hidden">
      <div class="px-3 py-2 bg-gray-50 text-xs font-semibold text-gray-600">Pemakaian Potongan</div>
      <div class="p-3 text-sm space-y-1">
        @forelse ($lefts as $l)
          @php
            $len   = (float)($l['used_length_m'] ?? 0);
            $price = (float)($l['price_m'] ?? ($l['price'] ?? 0));  // gunakan price_m
            $sub   = $len * $price;
          @endphp
          <div class="grid grid-cols-12 gap-2 py-1 border-b last:border-0">
            <div class="col-span-6 text-gray-800">{{ $l['name'] }}</div>
            <div class="col-span-2 text-right text-gray-700">{{ number_format($len,3) }} m</div>
            <div class="col-span-2 text-right text-gray-700">{{ $currency($price) }}</div>
            <div class="col-span-2 text-right text-gray-900 font-medium">{{ $currency($sub) }}</div>
          </div>
        @empty
          <div class="text-gray-500">—</div>
        @endforelse

        <div class="flex items-center justify-between pt-2 mt-2 border-t">
          <span class="text-gray-700 font-semibold">Total Potongan Sisa</span>
          <span class="text-gray-900 font-semibold">{{ $currency($totalLeftovers) }}</span>
        </div>
      </div>
    </div>

    {{-- SERVICES --}}
    <div class="md:col-span-2 border rounded-lg overflow-hidden">
      <div class="px-3 py-2 bg-gray-50 text-xs font-semibold text-gray-600">Services</div>
      <div class="p-3 text-sm">
        @forelse ($services as $s)
          <div class="flex items-center justify-between py-1 border-b last:border-0">
            <span class="text-gray-800">{{ $s['name'] }}</span>
            <span class="text-gray-700">{{ $currency($s['price']) }}</span>
          </div>
        @empty
          <div class="text-gray-500">—</div>
        @endforelse

        <div class="flex items-center justify-between mt-3 pt-3 border-t">
          <span class="font-semibold text-gray-800">Total Jasa</span>
          <span class="font-semibold text-gray-900">{{ $currency($serviceTotal) }}</span>
        </div>

    <!-- TAMBAHKAN INI: Subtotal sebelum diskon -->
    <div class="flex items-center justify-between border-t pt-2 mt-2">
      <span class="font-medium text-gray-700">Subtotal</span>
      <span class="font-medium text-gray-800" id="subtotal-amount" data-subtotal="{{ (float)$sum['grand_total'] }}">
        {{ $currency($sum['grand_total']) }}
      </span>
    </div>
    
    <!-- TAMBAHKAN INI: Diskon -->
    <div class="flex items-center justify-between">
      <span class="text-red-600">Diskon</span>
      <span class="text-red-600" id="discount-amount">Rp 0</span>
    </div>
    
    <!-- UPDATE INI: Grand Total setelah diskon -->
    <div class="flex items-center justify-between border-t pt-2 mt-2">
      <span class="font-semibold text-gray-800">Grand Total</span>
      <span class="font-semibold text-gray-900" id="grand-total" data-grand="{{ (float)$sum['grand_total'] }}">
        {{ $currency($sum['grand_total']) }}
      </span>
    </div>
      </div>
    </div>
  </div>

  {{-- Form Finalize (opsional) --}}
  <form action="{{ route('projects.finalize') }}" method="POST" class="mt-4">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div>
        <label class="block text-xs text-gray-600 mb-1">Judul Proyek</label>
        <input name="title" required class="w-full px-3 py-2 border rounded-lg" placeholder="Nama/Alamat Proyek">
      </div>
      <div>
        <label class="block text-xs text-gray-600 mb-1">Customer (opsional)</label>
        <input type="number" name="customer_id" class="w-full px-3 py-2 border rounded-lg"
               value="{{ session('project.customer.id') }}">
        <p class="text-xs text-gray-500 mt-1">Otomatis terisi jika sudah memilih customer.</p>
      </div>
    </div>

    <div class="mt-4 flex items-center justify-between">
      <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="with_billing" value="1" class="rounded border-gray-300">
        <span>Siapkan Billing POS dari Service</span>
      </label>

      <button class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700">
        Finalize Proyek
      </button>
    </div>
  </form>
</div>
