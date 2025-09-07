{{-- Menggunakan layout utama aplikasi (pastikan nama file sudah benar) --}}
@extends('layouts.dashboard')

{{-- Menentukan judul halaman dinamis di header --}}
@section('title', 'Buat Cabang Baru')

{{-- Konten utama halaman --}}
@section('content')

    {{-- === Bagian untuk Notifikasi (Pesan Sukses/Error) === --}}
    {{-- Pesan ini akan muncul setelah form disubmit dan halaman di-redirect --}}
    @if (session('success'))
        <div class="p-4 mb-6 text-sm text-green-800 rounded-lg bg-green-100 dark:bg-gray-800 dark:text-green-400" role="alert">
            <span class="font-bold">Berhasil!</span>
            {{-- Menggunakan {!! !!} agar tag <br> dari controller bisa dirender --}}
            {!! session('success') !!}
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 mb-6 text-sm text-red-800 rounded-lg bg-red-100 dark:bg-gray-800 dark:text-red-400" role="alert">
            <span class="font-bold">Gagal!</span> {{ session('error') }}
        </div>
    @endif
    {{-- ====================================================== --}}


    {{-- === Card Formulir === --}}
    <div class="bg-white dark:bg-panelDark p-6 rounded-xl shadow-card">
        
        <h2 class="text-xl font-semibold mb-1 text-slate-800 dark:text-slate-100">Formulir Cabang Baru</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Setelah cabang dibuat, akun kasir akan dibuat secara otomatis.</p>
        
        <form action="{{ route('admin.branches.store') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                {{-- Field: Nama Cabang --}}
                <div>
                    <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">Nama Cabang <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}"
                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                           placeholder="Contoh: Cabang Jakarta Pusat" required>
                    @error('name')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Field: Kode Cabang --}}
                <div>
                    <label for="code" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">Kode Cabang <span class="text-red-500">*</span></label>
                    <input type="text" id="code" name="code" value="{{ old('code') }}"
                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                           placeholder="3-5 huruf unik, e.g., JKP" required>
                    @error('code')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                
                {{-- Field: Alamat --}}
                <div class="md:col-span-2">
                    <label for="address" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">Alamat Lengkap</label>
                    <textarea id="address" name="address" rows="3"
                              class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                              placeholder="Jl. Merdeka No. 123...">{{ old('address') }}</textarea>
                </div>
                
                {{-- Field: Nomor Telepon --}}
                <div>
                    <label for="phone" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">Nomor Telepon</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                           placeholder="0812xxxxxxxx">
                </div>

            </div>
            
            {{-- Tombol Submit --}}
            <div class="mt-8 flex justify-end">
                <button type="submit"
                        class="px-6 py-2.5 bg-brand text-white font-medium text-sm leading-tight uppercase rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-0 transition duration-150 ease-in-out dark:bg-brandDark dark:hover:bg-indigo-700">
                    Simpan & Buat Akun Kasir
                </button>
            </div>
        </form>
    </div>

@endsection
