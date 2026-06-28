{{-- Menggunakan layout utama aplikasi (pastikan nama file sudah benar) --}}
@extends('layouts.dashboard')

{{-- Menentukan judul halaman dinamis di header --}}
@section('title', 'Impor Produk dari Excel')

{{-- Konten utama halaman --}}
@section('content')

 {{-- === Bagian untuk Notifikasi (Pesan Sukses/Error) === --}}
 @if (session('success'))
 <div class="p-4 mb-6 text-sm text-green-800 rounded-lg bg-green-100 " role="alert">
 <span class="font-bold">Berhasil!</span> {{ session('success') }}
 </div>
 @endif

 {{-- Menampilkan pesan error validasi impor yang lebih kompleks --}}
 @if (session('import_error'))
 <div class="p-4 mb-6 text-sm text-red-800 rounded-lg bg-red-100 " role="alert">
 {!! session('import_error') !!} {{-- Menggunakan {!! !!} agar tag HTML dari controller bisa dirender --}}
 </div>
 @endif
 {{-- ====================================================== --}}


 {{-- === Card Formulir === --}}
 <div class="bg-white p-6 rounded-xl shadow-card">
 
 <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
 <div>
 <h2 class="text-xl font-semibold text-slate-800 ">Impor Produk Massal</h2>
 <p class="text-sm text-slate-500 mt-1">Daftarkan banyak produk sekaligus menggunakan file Excel.</p>
 </div>
 <a href="{{ asset('templates/products-template.xlsx') }}" download
 class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md">
 <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
 Unduh Template
 </a>
 </div>

 <div class="border-t border-slate-200 pt-6">
 <form action="{{ route('admin.products.import.process') }}" method="POST" enctype="multipart/form-data">
 @csrf
 
 <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
 
 {{-- Field: Pilih Cabang --}}
 <div>
 <label for="branch_id" class="block mb-2 text-sm font-medium text-gray-900 ">
 1. Pilih Cabang Tujuan <span class="text-red-500">*</span>
 </label>
 <p class="text-xs text-slate-500 mb-2">Stok awal dari produk yang diimpor akan dicatat di cabang ini.</p>
 <select id="branch_id" name="branch_id" required
 class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 ">
 <option value="" disabled selected>-- Pilih Cabang --</option>
 @foreach ($branches as $branch)
 <option value="{{ $branch->id }}">{{ $branch->name }} (Kode: {{ $branch->code }})</option>
 @endforeach
 </select>
 @error('branch_id')
 <p class="mt-2 text-sm text-red-600 ">{{ $message }}</p>
 @enderror
 </div>

 {{-- Field: Unggah File --}}
 <div>
 <label for="file" class="block mb-2 text-sm font-medium text-gray-900 ">
 2. Unggah File Excel <span class="text-red-500">*</span>
 </label>
 <p class="text-xs text-slate-500 mb-2">Gunakan template yang sudah diunduh untuk menghindari error.</p>
 <input type="file" id="file" name="file" required accept=".xlsx, .xls"
 class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none ">
 @error('file')
 <p class="mt-2 text-sm text-red-600 ">{{ $message }}</p>
 @enderror
 </div>
 </div>
 
 {{-- Tombol Submit --}}
 <div class="mt-8 flex justify-end border-t border-slate-200 pt-6">
 <button type="submit"
 class="px-8 py-2.5 bg-brand text-white font-medium text-sm leading-tight uppercase rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-0 transition duration-150 ease-in-out ">
 Mulai Proses Impor
 </button>
 </div>
 </form>
 </div>
 </div>

@endsection
