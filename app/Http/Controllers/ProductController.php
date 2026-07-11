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
        $categories = DB::table('product_categories')->orderBy('name')->get();
        $uoms = DB::table('uoms')->orderBy('name')->get();

        $view = request()->routeIs('kasir.products.new')
            ? 'kasir.products.create'
            : 'products.create';

        return view($view, [
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
            'hpp'             => ['required', 'numeric', 'min:0'],
            'selling_price'   => ['required', 'numeric', 'min:0'],
            
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

            // Logika untuk menggabungkan HPP ke dalam kolom notes (legacy)
            $notes = $data['notes'] ?? '';
            $hppValue = null;
            if (!empty($data['hpp'])) {
                $hppValue = (float) $data['hpp'];
                // Hapus entri hpp lama dari notes jika ada
                $notes = preg_replace('/hpp\s*:\s*[0-9\.]+\s*/i', '', $notes);
            }

            // Siapkan data produk untuk disimpan
            $productData = [
                'sku'            => $data['sku'],
                'name'           => $data['name'],
                'category_id'    => $categoryId,
                'uom_id'         => $data['uom_id'],
                'hpp'            => $hppValue,
                'selling_price'  => !empty($data['selling_price']) ? (float) $data['selling_price'] : null,
                'notes'          => $notes,
                'material'       => $data['material'] ?? null,
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

            $redirect = request()->routeIs('kasir.products.store')
                ? redirect()->route('kasir.products.new')
                : redirect()->route('products.create');

            return $redirect->with('success', 'Produk "' . $data['name'] . '" berhasil didaftarkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            // Kembalikan ke form dengan error dan input yang sudah diisi
            return redirect()->back()->withInput()->with('error', 'Gagal mendaftarkan produk: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan form edit produk (owner).
     */
    public function edit($id)
    {
        $product = DB::table('products')->where('id', $id)->first();
        abort_if(!$product, 404);

        $categories = DB::table('product_categories')->orderBy('name')->get();
        $uoms       = DB::table('uoms')->orderBy('name')->get();

        return view('products.edit', [
            'product'    => $product,
            'categories' => $categories,
            'uoms'       => $uoms,
        ]);
    }

    /**
     * Menyimpan perubahan data produk (owner).
     */
    public function update(Request $request, $id)
    {
        $product = DB::table('products')->where('id', $id)->first();
        abort_if(!$product, 404);

        $data = $request->validate([
            'sku'             => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($id)],
            'name'            => ['required', 'string', 'max:160'],
            'uom_id'          => ['required', 'integer', 'exists:uoms,id'],
            'hpp'             => ['required', 'numeric', 'min:0'],
            'selling_price'   => ['required', 'numeric', 'min:0'],

            'category_id'       => ['nullable', 'required_without:new_category_name', 'exists:product_categories,id'],
            'new_category_name' => ['nullable', 'required_without:category_id', 'string', 'max:100', 'unique:product_categories,name'],
            'new_category_code' => ['nullable', 'required_with:new_category_name', 'string', 'max:20', 'unique:product_categories,code'],

            'material'        => ['nullable', 'string', 'max:30'],
            'series'          => ['nullable', 'string', 'max:60'],
            'pattern_code'    => ['nullable', 'string', 'max:60'],
            'finish'          => ['nullable', 'string', 'max:40'],
            'length_cm'       => ['nullable', 'integer'],
            'width_mm'        => ['nullable', 'integer'],
            'thickness_mm'    => ['nullable', 'numeric'],
            'barcode'         => ['nullable', 'string', 'max:64', Rule::unique('products', 'barcode')->ignore($id)],
            'notes'           => ['nullable', 'string'],
        ], [
            'sku.unique' => 'SKU ini sudah digunakan oleh produk lain.',
            'category_id.required_without' => 'Pilih kategori atau isi nama kategori baru.',
            'new_category_name.required_without' => 'Pilih kategori atau isi nama kategori baru.',
            'new_category_name.unique' => 'Nama kategori ini sudah ada.',
            'new_category_code.required_with' => 'Kode untuk kategori baru wajib diisi.',
            'new_category_code.unique' => 'Kode kategori ini sudah ada.',
            'barcode.unique' => 'Barcode ini sudah digunakan oleh produk lain.',
        ]);

        $userId = (int) Auth::id();

        try {
            DB::beginTransaction();

            $categoryId = $data['category_id'] ?? null;

            if (!empty($data['new_category_name'])) {
                $categoryId = DB::table('product_categories')->insertGetId([
                    'code' => $data['new_category_code'],
                    'name' => $data['new_category_name'],
                ]);
            }

            $notes = $data['notes'] ?? '';
            $notes = preg_replace('/hpp\s*:\s*[0-9\.]+\s*/i', '', $notes);

            $productData = [
                'sku'            => $data['sku'],
                'name'           => $data['name'],
                'category_id'    => $categoryId,
                'uom_id'         => $data['uom_id'],
                'hpp'            => (float) $data['hpp'],
                'selling_price'  => (float) $data['selling_price'],
                'notes'          => $notes,
                'material'       => $data['material'] ?? null,
                'series'         => $data['series'] ?? null,
                'pattern_code'   => $data['pattern_code'] ?? null,
                'finish'         => $data['finish'] ?? null,
                'length_cm'      => $data['length_cm'] ?? null,
                'width_mm'       => $data['width_mm'] ?? null,
                'thickness_mm'   => $data['thickness_mm'] ?? null,
                'barcode'        => $data['barcode'] ?? null,
            ];

            DB::table('products')->where('id', $id)->update($productData);

            DB::table('audit_logs')->insert([
                'event'      => 'PRODUCT_UPDATED',
                'user_id'    => $userId,
                'ref_type'   => 'PRODUCT',
                'ref_id'     => $id,
                'payload'    => json_encode(['before' => (array) $product, 'after' => $productData]),
                'created_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('products.edit', $id)->with('success', 'Produk "' . $data['name'] . '" berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui produk: ' . $e->getMessage());
        }
    }
}
