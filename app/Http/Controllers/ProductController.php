<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Menampilkan daftar produk dengan filter dan pencarian
     * METHOD BARU - ditambahkan
     */
public function index(Request $request)
{
    $query = DB::table('products as p')
        ->leftJoin('product_categories as pc', 'p.category_id', '=', 'pc.id')
        ->leftJoin('uoms as u', 'p.uom_id', '=', 'u.id')
        ->select(
            'p.*',
            'pc.name as category_name',
            'pc.code as category_code',
            'u.name as uom_name',
            'u.code as uom_code'
        );
        // ❌ HAPUS: ->where('p.is_active', 1);

    // Filter berdasarkan branch jika user bukan owner
    if (auth()->user()->role_id != 1) {
        $userBranchId = auth()->user()->branch_id ?? null;
        if ($userBranchId) {
            $query->where(function($q) use ($userBranchId) {
                $q->where('p.branch_id', $userBranchId)
                  ->orWhereNull('p.branch_id');
            });
        }
    }

    // Filter pencarian
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('p.sku', 'LIKE', "%{$search}%")
              ->orWhere('p.name', 'LIKE', "%{$search}%")
              ->orWhere('p.barcode', 'LIKE', "%{$search}%")
              ->orWhere('p.material', 'LIKE', "%{$search}%")
              ->orWhere('p.series', 'LIKE', "%{$search}%");
        });
    }

    // Filter berdasarkan kategori
    if ($request->filled('category_id')) {
        $query->where('p.category_id', $request->category_id);
    }

    // ⭐ TAMBAHAN: Filter berdasarkan status (aktif/nonaktif/semua)
    if ($request->filled('status')) {
        $status = $request->input('status');
        if ($status === 'active') {
            $query->where('p.is_active', 1);
        } elseif ($status === 'inactive') {
            $query->where('p.is_active', 0);
        }
        // Jika 'all' atau kosong, tidak ada filter tambahan
    }

    $products = $query->orderBy('p.name')->paginate(20);

    // Data untuk dropdown filter
    $categories = DB::table('product_categories')->orderBy('name')->get();

    return view('products.index', [
        'title' => 'Manajemen Produk',
        'products' => $products,
        'categories' => $categories
    ]);
}


    /**
     * Menampilkan detail produk
     * METHOD BARU - ditambahkan
     */
    public function show($id)
    {
        $query = DB::table('products as p')
            ->leftJoin('product_categories as pc', 'p.category_id', '=', 'pc.id')
            ->leftJoin('uoms as u', 'p.uom_id', '=', 'u.id')
            ->select(
                'p.*',
                'pc.name as category_name',
                'pc.code as category_code',
                'u.name as uom_name',
                'u.code as uom_code'
            )
            ->where('p.id', $id);

        // Tambahkan join branch jika kolom branch_id ada
        if ($this->columnExists('products', 'branch_id')) {
            $query->leftJoin('branches as b', 'p.branch_id', '=', 'b.id')
                  ->addSelect('b.name as branch_name');
        }

        $product = $query->first();

        if (!$product) {
            return redirect()->route('products.index')->with('error', 'Produk tidak ditemukan.');
        }

        // Cek authorization jika ada branch_id
        if ($this->columnExists('products', 'branch_id') && 
            auth()->user()->role_id != 1 && 
            $product->branch_id && 
            $product->branch_id != auth()->user()->branch_id) {
            return redirect()->route('products.index')->with('error', 'Anda tidak memiliki akses untuk melihat produk ini.');
        }

        // Get stock information
        $stockInfo = $this->getProductStockInfo($id);
        
        // Extract HPP from notes
        $hpp = $this->extractHppFromNotes($product->notes);

        return view('products.show', [
            'title' => 'Detail Produk',
            'product' => $product,
            'stockInfo' => $stockInfo,
            'hpp' => $hpp
        ]);
    }

    /**
     * Menampilkan form edit produk
     * METHOD BARU - ditambahkan
     */
    public function edit($id)
    {
        $product = DB::table('products')->where('id', $id)->first();

        if (!$product) {
            return redirect()->route('products.index')->with('error', 'Produk tidak ditemukan.');
        }

        // Cek authorization jika ada branch_id
        if ($this->columnExists('products', 'branch_id') && 
            auth()->user()->role_id != 1 && 
            $product->branch_id && 
            $product->branch_id != auth()->user()->branch_id) {
            return redirect()->route('products.index')->with('error', 'Anda tidak memiliki akses untuk mengedit produk ini.');
        }

        // Cek apakah produk memiliki transaksi (untuk warning)
        $transactionCheck = $this->checkProductTransactions($id);

        $categories = DB::table('product_categories')->orderBy('name')->get();
        $uoms = DB::table('uoms')->orderBy('name')->get();
        
        // Extract HPP from notes untuk ditampilkan di form
        $hpp = $this->extractHppFromNotes($product->notes);

        return view('products.edit', [
            'title' => 'Edit Produk',
            'product' => $product,
            'categories' => $categories,
            'uoms' => $uoms,
            'transactionCheck' => $transactionCheck,
            'hpp' => $hpp
        ]);
    }

    /**
     * Update produk
     * METHOD BARU - ditambahkan 
     */
    public function update(Request $request, $id)
    {
        $product = DB::table('products')->where('id', $id)->first();

        if (!$product) {
            return redirect()->route('products.index')->with('error', 'Produk tidak ditemukan.');
        }

        // Cek authorization jika ada branch_id
        if ($this->columnExists('products', 'branch_id') && 
            auth()->user()->role_id != 1 && 
            $product->branch_id && 
            $product->branch_id != auth()->user()->branch_id) {
            return redirect()->route('products.index')->with('error', 'Anda tidak memiliki akses untuk mengedit produk ini.');
        }

        // Validasi input yang dapat diedit
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:160'],
            'category_id'     => ['required', 'exists:product_categories,id'],
            'uom_id'          => ['required', 'integer', 'exists:uoms,id'],
            'hpp'             => ['nullable', 'numeric', 'min:0'], // HPP tetap bisa diedit
            'material'        => ['nullable', 'string', 'max:30'],
            'series'          => ['nullable', 'string', 'max:60'],
            'pattern_code'    => ['nullable', 'string', 'max:60'],
            'finish'          => ['nullable', 'string', 'max:40'],
            'length_cm'       => ['nullable', 'integer'],
            'width_mm'        => ['nullable', 'integer'],
            'thickness_mm'    => ['nullable', 'numeric'],
            'barcode'         => ['nullable', 'string', 'max:64', 
                                 Rule::unique('products')->ignore($id)],
            'notes'           => ['nullable', 'string'],
        ], [
            'barcode.unique' => 'Barcode ini sudah digunakan oleh produk lain.',
        ]);

        // Cek apakah ada perubahan UOM jika produk sudah ada transaksi
        $hasTransactions = $this->checkProductTransactions($id);
        if ($hasTransactions['has_transactions'] && $request->uom_id != $product->uom_id) {
            return redirect()->back()->with('error', 
                'Tidak dapat mengubah satuan (UOM) karena produk memiliki riwayat transaksi: ' . 
                implode(', ', $hasTransactions['transaction_types'])
            )->withInput();
        }

        $userId = (int) Auth::id();

        try {
            DB::beginTransaction();

            // Simpan data lama untuk audit log
            $oldData = (array) $product;

            // Logika untuk menggabungkan HPP ke dalam kolom notes (sama seperti di store)
            $notes = $data['notes'] ?? '';
            if (!empty($data['hpp'])) {
                // Hapus entri hpp lama jika ada
                $notes = preg_replace('/hpp\s*:\s*[0-9\.]+\s*/i', '', $notes);
                // Tambahkan hpp baru
                $notes .= (trim($notes) ? ' ' : '') . 'hpp:' . $data['hpp'];
            }

            // Update produk
            $updateData = [
                'name'         => $data['name'],
                'category_id'  => $data['category_id'],
                'uom_id'       => $data['uom_id'],
                'notes'        => $notes,
                'material'     => $data['material'] ?? null,
                'series'       => $data['series'] ?? null,
                'pattern_code' => $data['pattern_code'] ?? null,
                'finish'       => $data['finish'] ?? null,
                'length_cm'    => $data['length_cm'] ?? null,
                'width_mm'     => $data['width_mm'] ?? null,
                'thickness_mm' => $data['thickness_mm'] ?? null,
                'barcode'      => $data['barcode'] ?? null,
            ];

            DB::table('products')->where('id', $id)->update($updateData);

            // Catat di audit log (sesuai dengan pattern yang sudah ada)
            DB::table('audit_logs')->insert([
                'event'    => 'PRODUCT_UPDATED',
                'user_id'  => $userId,
                'ref_type' => 'PRODUCT',
                'ref_id'   => $id,
                'payload'  => json_encode([
                    'old' => $oldData,
                    'new' => $updateData
                ]),
                'created_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('products.show', $id)
                             ->with('success', 'Produk "' . $data['name'] . '" berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui produk: ' . $e->getMessage());
        }
    }

    /**
     * Toggle status aktif/nonaktif produk
     * METHOD BARU - ditambahkan
     */
    public function toggleActive($id)
    {
        $product = DB::table('products')->where('id', $id)->first();

        if (!$product) {
            return redirect()->back()->with('error', 'Produk tidak ditemukan.');
        }

        // Cek authorization jika ada branch_id
        if ($this->columnExists('products', 'branch_id') && 
            auth()->user()->role_id != 1 && 
            $product->branch_id && 
            $product->branch_id != auth()->user()->branch_id) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk mengubah status produk ini.');
        }

        $userId = (int) Auth::id();

        try {
            DB::beginTransaction();

            $newStatus = $product->is_active ? 0 : 1;
            $action = $newStatus ? 'PRODUCT_ACTIVATED' : 'PRODUCT_DEACTIVATED';

            DB::table('products')->where('id', $id)->update([
                'is_active' => $newStatus,
            ]);

            // Catat di audit log
            DB::table('audit_logs')->insert([
                'event'    => $action,
                'user_id'  => $userId,
                'ref_type' => 'PRODUCT',
                'ref_id'   => $id,
                'payload'  => json_encode([
                    'old_status' => $product->is_active,
                    'new_status' => $newStatus
                ]),
                'created_at' => now(),
            ]);

            DB::commit();
            
            $message = $newStatus ? 'Produk berhasil diaktifkan.' : 'Produk berhasil dinonaktifkan.';
            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mengubah status produk: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan form untuk membuat produk baru.
     * METHOD LAMA - tidak diubah
     */
    public function create()
    {
        // Ambil data master untuk mengisi dropdown di form
        $categories = DB::table('product_categories')->orderBy('name')->get();
        $uoms = DB::table('uoms')->orderBy('name')->get();

        return view('products.create', [
            'title' => 'Tambah Produk Baru',
            'categories' => $categories,
            'uoms'       => $uoms,
        ]);
    }

    /**
     * Menyimpan produk baru ke database.
     * Termasuk logika untuk membuat kategori baru dan menyimpan HPP di notes.
     * METHOD LAMA - tidak diubah
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

    // ========== HELPER METHODS BARU ==========

    /**
     * Cek apakah produk memiliki transaksi
     */
    private function checkProductTransactions($productId)
    {
        $transactionTypes = [];
        $hasTransactions = false;

        // Cek di pos_sale_lines
        if (DB::table('pos_sale_lines')->where('product_id', $productId)->exists()) {
            $transactionTypes[] = 'Penjualan POS';
            $hasTransactions = true;
        }

        // Cek di project_items (jika tabel ada)  
        if (DB::getSchemaBuilder()->hasTable('project_items')) {
            if (DB::table('project_items')->where('product_id', $productId)->exists()) {
                $transactionTypes[] = 'Project Installation';
                $hasTransactions = true;
            }
        }

        // Cek di stock_moves
        if (DB::table('stock_moves')->where('product_id', $productId)->exists()) {
            $transactionTypes[] = 'Pergerakan Stok';
            $hasTransactions = true;
        }

        // Cek di stock_transfers (jika ada tabel stock_transfer_lines)
        if (DB::getSchemaBuilder()->hasTable('stock_transfer_lines')) {
            if (DB::table('stock_transfer_lines')->where('product_id', $productId)->exists()) {
                $transactionTypes[] = 'Transfer Antar Cabang';
                $hasTransactions = true;
            }
        }

        return [
            'has_transactions' => $hasTransactions,
            'transaction_types' => $transactionTypes
        ];
    }

    /**
     * Mendapatkan informasi stok produk
     */
    private function getProductStockInfo($productId)
    {
        $query = DB::table('stock_quants as sq')
            ->leftJoin('stock_locations as sl', 'sq.location_id', '=', 'sl.id')
            ->select(
                'sq.qty',
                'sl.name as location_name',
                'sl.code as location_code'
            )
            ->where('sq.product_id', $productId)
            ->where('sq.qty', '>', 0);

        // Tambahkan join branch jika ada
        if (DB::getSchemaBuilder()->hasColumn('stock_locations', 'branch_id')) {
            $query->leftJoin('branches as b', 'sl.branch_id', '=', 'b.id')
                  ->addSelect('b.name as branch_name');
        }

        $stockInfo = $query->get();
        $totalStock = $stockInfo->sum('qty');

        return [
            'locations' => $stockInfo,
            'total_stock' => $totalStock
        ];
    }

    /**
     * Extract HPP dari kolom notes
     */
    private function extractHppFromNotes($notes)
    {
        if (empty($notes)) {
            return null;
        }

        // Cari pattern hpp:angka
        if (preg_match('/hpp\s*:\s*([0-9\.]+)/i', $notes, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    /**
     * Utility method untuk cek apakah kolom ada di tabel
     */
    private function columnExists($table, $column)
    {
        try {
            return DB::getSchemaBuilder()->hasColumn($table, $column);
        } catch (\Exception $e) {
            return false;
        }
    }
}
