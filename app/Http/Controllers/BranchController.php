<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Pastikan DB facade di-import
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Branch;
use App\Models\User;
// use App\Models\StockLocation; // <-- 1. HAPUS BARIS INI

class BranchController extends Controller
{
    /**
     * Menampilkan formulir untuk membuat cabang baru.
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.branches.create', [
            'title' => 'Buat Cabang Baru'
        ]);
    }

    /**
     * Menyimpan cabang baru dan secara otomatis membuat akun kasir serta lokasi stok.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'    => 'required|string|max:100|unique:branches,name',
            'code'    => 'required|string|max:20|unique:branches,code',
            'address' => 'nullable|string',
            'phone'   => 'nullable|string|max:30',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.branches.create')
                        ->withErrors($validator)
                        ->withInput();
        }

        $generatedUsername = '';
        $generatedPassword = '';

        DB::beginTransaction();
        try {
            // Buat Cabang Baru
            $branch = new Branch();
            $branch->name = $request->name;
            $branch->code = strtoupper($request->code);
            $branch->address = $request->address;
            $branch->phone = $request->phone;
            $branch->is_active = 1;
            $branch->save();

            // 2. [PERBAIKAN] Ubah ke DB::table() untuk konsistensi
            DB::table('stock_locations')->insert([
                'branch_id' => $branch->id,
                'code'      => 'STORE',
                'name'      => 'Main Store ' . strtolower($branch->code),
                'type'      => 'STORE'
            ]);

            // Buat Akun Kasir Otomatis
            $kasir = new User();
            $generatedUsername = 'kasir' . strtolower($branch->code);
            $passwordBase = Str::slug($branch->name, '');
            $randomDigits = random_int(1000, 9999);
            $generatedPassword = $passwordBase . $randomDigits;

            $kasir->full_name = 'Kasir ' . $branch->name;
            $kasir->username = $generatedUsername;
            $kasir->password_hash = Hash::make($generatedPassword);
            $kasir->role_id = 3; // Role ID Kasir
            $kasir->default_branch_id = $branch->id;
            $kasir->is_active = 1;
            $kasir->save();
            
            // Hubungkan user ke cabang melalui tabel pivot 'user_branches'
            $kasir->branches()->attach($branch->id);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.branches.create')
                ->with('error', 'Gagal membuat cabang. Terjadi kesalahan: ' . $e->getMessage())
                ->withInput();
        }

        // Redirect dengan pesan sukses
        $successMessage = "Cabang '{$branch->name}' berhasil dibuat. Akun kasir telah dibuat otomatis dengan detail:<br>"
                        . "<strong>Username:</strong> {$generatedUsername}<br>"
                        . "<strong>Password:</strong> {$generatedPassword}";

        return redirect()->route('admin.branches.create')
            ->with('success', $successMessage);
    }
}
