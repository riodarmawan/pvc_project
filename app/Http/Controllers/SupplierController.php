<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    /**
     * Menampilkan halaman form untuk membuat supplier baru.
     */
    public function create()
    {
        // Cukup tampilkan view-nya saja
        return view('suppliers.create');
    }

    /**
     * Menyimpan data supplier baru ke database.
     */
    public function store(Request $request)
    {
        // Validasi input
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:160', 'unique:suppliers,name'],
            'phone'   => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
        ], [
            'name.unique' => 'Nama supplier ini sudah terdaftar.',
        ]);

        // Simpan ke database
        $supplierId = DB::table('suppliers')->insertGetId([
            'name'    => $data['name'],
            'phone'   => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
        ]);

        // Catat di audit log
        DB::table('audit_logs')->insert([
            'event'    => 'SUPPLIER_CREATED',
            'user_id'  => Auth::id(),
            'ref_type' => 'SUPPLIER',
            'ref_id'   => $supplierId,
            'payload'  => json_encode($data),
            'created_at' => now(),
        ]);

        // Redirect kembali ke halaman input pembelian dengan pesan sukses
        return redirect()->route('purchase.direct.create')
                         ->with('success', 'Supplier baru "' . $data['name'] . '" berhasil didaftarkan.');
    }
}
