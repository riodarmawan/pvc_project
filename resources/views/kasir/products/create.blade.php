@extends('layouts.app', ['title' => 'Produk Baru'])

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
  <div class="flex items-center justify-between">
    <h1 class="text-xl font-semibold">Tambah Produk Baru</h1>
    <a href="{{ route('kasir.home') }}"
       class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm">
      ← Kembali ke Katalog
    </a>
  </div>

  <form method="post" action="{{ route('kasir.products.store') }}" class="space-y-6">
    @csrf

    {{-- Bagian 1: Identitas & Kategori --}}
    @include('kasir.products._form_main', [
      'categories' => $categories,
      'uoms'       => $uoms
    ])

    {{-- Bagian 2: Atribut Teknis --}}
    @include('kasir.products._form_attributes')

    {{-- Bagian 3: HPP, Preview Harga Jual, Stok Awal --}}
    @include('kasir.products._form_price_stock', [
      'branch' => $branch,
      'markup' => $markup
    ])

    <div class="flex items-center justify-end gap-2">
      <a href="{{ route('kasir.home') }}" class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200">
        Batal
      </a>
      <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
        Simpan Produk
      </button>
    </div>
  </form>
</div>
@endsection

@push('scripts')
  <script src="{{ asset('js/product_new.js') }}" defer></script>
@endpush
