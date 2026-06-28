<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Branch;
use Maatwebsite\Excel\Facades\Excel;

class ProductImportController extends Controller
{
    /**
     * Menampilkan halaman form untuk impor produk.
     */
    public function showForm()
    {
        $branches = Branch::where('is_active', 1)->orderBy('name')->get();
        return view('products.import', [
            'title' => 'Impor Produk dari Excel',
            'branches' => $branches
        ]);
    }

    /**
     * Memproses file Excel yang diunggah dengan logika pembuatan master data otomatis.
     */
    public function processImport(Request $request)
    {
        $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'file'      => ['required', 'file', 'mimes:xlsx,xls'],
        ]);

        $branchId = $request->input('branch_id');
        $file     = $request->file('file');

        $rows = Excel::toArray([], $file)[0];
        $header = array_shift($rows);
        $dataRows = $rows;
        
        // --- TAHAP VALIDASI AWAL ---
        // Hanya validasi SKU kosong atau duplikat di dalam file
        $failures = [];
        $uniqueSkus = [];
        foreach ($dataRows as $index => $row) {
            $rowNumber = $index + 2;

            $sku = trim($row[0] ?? '');
            if (empty($sku)) {
                $failures[] = "Baris {$rowNumber}: Kolom 'sku' tidak boleh kosong.";
                continue;
            }
            if (in_array($sku, $uniqueSkus)) {
                 $failures[] = "Baris {$rowNumber}: SKU '{$sku}' duplikat di dalam file Excel.";
            } else {
                 $uniqueSkus[] = $sku;
            }

            // Produk BARU wajib punya HPP & harga jual (>0) agar COGS/laba akurat.
            if (!DB::table('products')->where('sku', $sku)->exists()) {
                if ((float) ($row[4] ?? 0) <= 0) {
                    $failures[] = "Baris {$rowNumber}: HPP wajib > 0 untuk produk baru '{$sku}'.";
                }
                if ((float) ($row[11] ?? 0) <= 0) {
                    $failures[] = "Baris {$rowNumber}: Harga jual wajib > 0 untuk produk baru '{$sku}'.";
                }
            }
        }

        if (!empty($failures)) {
            $errorString = "<strong>Impor dibatalkan. Ditemukan kesalahan pada file:</strong><br><ul class='list-disc list-inside mt-2'>";
            foreach ($failures as $fail) {
                $errorString .= "<li>" . e($fail) . "</li>";
            }
            $errorString .= "</ul>";
            return redirect()->back()->with('import_error', $errorString);
        }
        
        // --- TAHAP EKSEKUSI ---
        DB::beginTransaction();
        try {
            $locationId = DB::table('stock_locations')->where('branch_id', $branchId)->where('code', 'STORE')->value('id');
            if (!$locationId) {
                throw new \Exception("Lokasi 'STORE' untuk cabang yang dipilih tidak ditemukan.");
            }

            $totalProcessed = 0;
            
            $categoryCache = [];
            $uomCache = [];

            foreach ($dataRows as $row) {
                $sku          = trim($row[0]);
                $stokAwal     = (int) ($row[5] ?? 0);
                
                // Cari produk berdasarkan SKU
                $existingProduct = DB::table('products')->where('sku', $sku)->first();
                $productId = null;
                $uomId = null;

                // Parse data dari Excel
                $namaProduk   = trim($row[1] ?? $sku);
                $kategoriKode = strtoupper(trim($row[2] ?? 'UNCATEGORIZED'));
                $satuanKode   = strtoupper(trim($row[3] ?? 'PCS'));
                $hpp          = (float) ($row[4] ?? 0);
                $catatanAwal  = trim($row[10] ?? '');
                $barcode      = trim($row[6] ?? null);
                $lengthCm     = empty($row[7]) ? null : (int)$row[7];
                $widthMm      = empty($row[8]) ? null : (int)$row[8];
                $thicknessMm  = empty($row[9]) ? null : (float)$row[9];
                $sellingPrice = !empty($row[11]) ? (float) $row[11] : null;

                // Cari atau buat Kategori
                if (!isset($categoryCache[$kategoriKode])) {
                    $category = DB::table('product_categories')->where('code', $kategoriKode)->first();
                    $categoryCache[$kategoriKode] = $category ? $category->id : DB::table('product_categories')->insertGetId(['code' => $kategoriKode, 'name' => $kategoriKode]);
                }
                $categoryId = $categoryCache[$kategoriKode];
                
                // Cari atau buat Satuan (UOM)
                if (!isset($uomCache[$satuanKode])) {
                    $uom = DB::table('uoms')->where('code', $satuanKode)->first();
                    $uomCache[$satuanKode] = $uom ? $uom->id : DB::table('uoms')->insertGetId(['code' => $satuanKode, 'name' => $satuanKode]);
                }
                $uomId = $uomCache[$satuanKode];

                if ($existingProduct) {
                    // --- PRODUK SUDAH ADA → UPDATE data ---
                    $productId = $existingProduct->id;
                    $uomId = $existingProduct->uom_id;

                    DB::table('products')->where('id', $productId)->update([
                        'name'          => $namaProduk,
                        'category_id'   => $categoryId,
                        'uom_id'        => $uomId,
                        'hpp'           => $hpp > 0 ? $hpp : $existingProduct->hpp,
                        'selling_price' => $sellingPrice ?? $existingProduct->selling_price,
                        'barcode'       => $barcode ?: $existingProduct->barcode,
                        'length_cm'     => $lengthCm ?? $existingProduct->length_cm,
                        'width_mm'      => $widthMm ?? $existingProduct->width_mm,
                        'thickness_mm'  => $thicknessMm ?? $existingProduct->thickness_mm,
                        'notes'         => trim(preg_replace('/hpp\s*:\s*[0-9\.]+/i', '', $catatanAwal)) ?: $existingProduct->notes,
                    ]);

                } else {
                    // --- PRODUK BARU → INSERT ---
                    $notes = trim(preg_replace('/hpp\s*:\s*[0-9\.]+/i', '', $catatanAwal));

                    $productId = DB::table('products')->insertGetId([
                        'sku' => $sku, 'name' => $namaProduk, 'category_id' => $categoryId, 'uom_id' => $uomId,
                        'hpp' => $hpp, 'selling_price' => $sellingPrice, 'notes' => $notes,
                        'barcode' => $barcode, 'length_cm' => $lengthCm,
                        'width_mm' => $widthMm, 'thickness_mm' => $thicknessMm,
                        'is_active' => 1,
                    ]);
                }

                // --- PROSES STOK UNTUK PRODUK BARU MAUPUN LAMA ---
                if ($stokAwal > 0) {
                    // Cek apakah sudah ada kuantitas di lokasi ini
                    $quant = DB::table('stock_quants')
                        ->where('product_id', $productId)
                        ->where('location_id', $locationId)
                        ->first();
                    
                    if ($quant) {
                        // Jika sudah ada, UPDATE (tambahkan) kuantitasnya
                        DB::table('stock_quants')->where('id', $quant->id)->increment('qty', $stokAwal);
                    } else {
                        // Jika belum ada, INSERT kuantitas baru
                        DB::table('stock_quants')->insert(['product_id' => $productId, 'location_id' => $locationId, 'qty' => $stokAwal]);
                    }

                    // Selalu catat pergerakan stok sebagai bukti impor/penyesuaian
                    DB::table('stock_moves')->insert([
                        'product_id' => $productId, 'uom_id' => $uomId, 'qty' => $stokAwal, 'from_location_id' => null,
                        'to_location_id' => $locationId, 'ref_type' => 'ADJUST', 'ref_id' => $productId,
                        'state' => 'DONE', 'created_by' => Auth::id(), 'created_at' => now(),
                    ]);
                }
                $totalProcessed++;
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('import_error', 'Terjadi kesalahan teknis saat impor: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', "Impor berhasil! Sejumlah {$totalProcessed} baris produk telah diproses.");
    }
}