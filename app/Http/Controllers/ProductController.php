<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Menampilkan form untuk membuat produk baru.
     */
    public function create()
    {
        // Ambil data master untuk mengisi dropdown di form
        $categories = DB::table('product_categories')->orderBy('name')->get();
        $uoms = DB::table('uoms')->orderBy('name')->get();

        return view('products.create', [
            'categories' => $categories,
            'uoms'       => $uoms,
        ]);
    }

    /**
     * Menyimpan produk baru ke database.
     * Termasuk logika untuk membuat kategori baru dan menyimpan HPP di notes.
     */
    public function store(Request $request)
    {
        // Validasi input form, termasuk validasi untuk HPP
        $data = $request->validate([
            'sku'             => ['required', 'string', 'max:50', 'unique:products,sku'],
            'name'            => ['required', 'string', 'max:160'],
            'uom_id'          => ['required', 'integer', 'exists:uoms,id'],
            'hpp'             => ['nullable', 'numeric', 'min:0'], // Validasi untuk HPP
            
            // Kategori: salah satu harus diisi, tapi tidak keduanya
            'category_id'       => ['nullable', 'required_without:new_category_name', 'exists:product_categories,id'],
            'new_category_name' => ['nullable', 'required_without:category_id', 'string', 'max:100', 'unique:product_categories,name'],
            'new_category_code' => ['nullable', 'required_with:new_category_name', 'string', 'max:20', 'unique:product_categories,code'],
            
            // Atribut produk (opsional)
            'material'        => ['nullable', 'string', 'max:30'],
            'series'          => ['nullable', 'string', 'max:60'],
            'pattern_code'    => ['nullable', 'string', 'max:60'],
            'finish'          => ['nullable', 'string', 'max:40'],
            'length_cm'       => ['nullable', 'integer'],
            'width_mm'        => ['nullable', 'integer'],
            'thickness_mm'    => ['nullable', 'numeric'],
            'barcode'         => ['nullable', 'string', 'max:64', 'unique:products,barcode'],
            'notes'           => ['nullable', 'string'],
        ], [
            'sku.unique' => 'SKU ini sudah digunakan oleh produk lain.',
            'category_id.required_without' => 'Pilih kategori atau isi nama kategori baru.',
            'new_category_name.required_without' => 'Pilih kategori atau isi nama kategori baru.',
            'new_category_name.unique' => 'Nama kategori ini sudah ada.',
            'new_category_code.required_with' => 'Kode untuk kategori baru wajib diisi.',
            'new_category_code.unique' => 'Kode kategori ini sudah ada.',
        ]);

        $userId = (int) Auth::id();
        
        try {
            DB::beginTransaction();

            $categoryId = $data['category_id'] ?? null;

            // Cek apakah pengguna membuat kategori baru
            if (!empty($data['new_category_name'])) {
                $categoryId = DB::table('product_categories')->insertGetId([
                    'code' => $data['new_category_code'],
                    'name' => $data['new_category_name'],
                ]);
            }

            // Logika untuk menggabungkan HPP ke dalam kolom notes
            $notes = $data['notes'] ?? '';
            if (!empty($data['hpp'])) {
                // Hapus entri hpp lama jika ada, untuk menghindari duplikasi
                $notes = preg_replace('/hpp\s*:\s*[0-9\.]+\s*/i', '', $notes);
                // Tambahkan hpp baru ke dalam string notes
                $notes .= (trim($notes) ? ' ' : '') . 'hpp:' . $data['hpp'];
            }

            // Siapkan data produk untuk disimpan
            $productData = [
                'sku'          => $data['sku'],
                'name'         => $data['name'],
                'category_id'  => $categoryId,
                'uom_id'       => $data['uom_id'],
                'notes'        => $notes, // Gunakan notes yang sudah diformat dengan HPP
                'material'     => $data['material'] ?? null,
                'series'       => $data['series'] ?? null,
                'pattern_code' => $data['pattern_code'] ?? null,
                'finish'       => $data['finish'] ?? null,
                'length_cm'    => $data['length_cm'] ?? null,
                'width_mm'     => $data['width_mm'] ?? null,
                'thickness_mm' => $data['thickness_mm'] ?? null,
                'barcode'      => $data['barcode'] ?? null,
                'is_active'    => 1, // Default aktif saat dibuat
            ];

            // Simpan produk baru
            $productId = DB::table('products')->insertGetId($productData);

            // Catat di audit log
            DB::table('audit_logs')->insert([
                'event'    => 'PRODUCT_CREATED',
                'user_id'  => $userId,
                'ref_type' => 'PRODUCT',
                'ref_id'   => $productId,
                'payload'  => json_encode($productData),
                'created_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('products.create')
                             ->with('success', 'Produk "' . $data['name'] . '" berhasil didaftarkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            // Kembalikan ke form dengan error dan input yang sudah diisi
            return redirect()->back()->withInput()->with('error', 'Gagal mendaftarkan produk: ' . $e->getMessage());
        }
    }
}
