@extends('layouts.dashboard', ['title' => 'Kelola Produk'])

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="space-y-1">
            <h1 class="text-xl md:text-2xl font-semibold">Kelola Produk</h1>
            <p class="text-slate-600">Daftar seluruh produk, ubah data, atau nonaktifkan produk yang sudah tidak dijual.</p>
        </div>
        <a href="{{ route('products.create') }}"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border text-white bg-brand hover:bg-brand/90 border-transparent">
            + Tambah Produk
        </a>
    </div>

    @if (session('success'))
        <div role="alert"
             class="rounded-xl border px-4 py-3 text-sm bg-emerald-50 border-emerald-200 text-emerald-700">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <form method="GET" action="{{ route('products.index') }}" class="flex flex-wrap gap-3">
        <input type="text" name="q" value="{{ $q }}" placeholder="Cari SKU / nama produk..."
               class="flex-1 min-w-[200px] h-10 px-3 rounded-xl border border-slate-200 text-sm bg-white
                      focus:outline-none focus:ring-2 focus:ring-brand/40">

        <select name="category_id"
                class="h-10 px-3 rounded-xl border border-slate-200 text-sm bg-white">
            <option value="">Semua Kategori</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected($catId == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>

        <select name="status"
                class="h-10 px-3 rounded-xl border border-slate-200 text-sm bg-white">
            <option value="all" @selected($status === 'all')>Semua Status</option>
            <option value="active" @selected($status === 'active')>Aktif</option>
            <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
        </select>

        <button type="submit"
                class="h-10 px-4 rounded-xl border border-slate-200 text-sm bg-white hover:bg-slate-100">
            Filter
        </button>
    </form>

    <div class="rounded-2xl border bg-white shadow-card border-slate-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200">
                    <th class="px-4 py-3">SKU</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Kategori</th>
                    <th class="px-4 py-3 text-right">HPP</th>
                    <th class="px-4 py-3 text-right">Harga Jual</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-3">{{ $product->sku }}</td>
                        <td class="px-4 py-3">{{ $product->name }}</td>
                        <td class="px-4 py-3">{{ $product->category_name ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $product->hpp, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $product->selling_price, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            @if($product->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">Aktif</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('products.edit', $product->id) }}" class="text-brand hover:underline">Edit</a>
                                <form action="{{ route('products.toggleActive', $product->id) }}" method="POST"
                                      onsubmit="return confirm('{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }} produk ini?');">
                                    @csrf
                                    <button type="submit" class="text-slate-500 hover:underline">
                                        {{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-slate-500">Tidak ada produk yang cocok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $products->links() }}
</div>
@endsection
