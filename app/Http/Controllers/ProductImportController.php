<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
     * Parse stock value dengan berbagai kemungkinan format
     */
    private function parseStockValue($value) 
    {
        // Null atau undefined
        if (!isset($value) || is_null($value)) {
            return 0;
        }
        
        // String kosong atau hanya whitespace
        if (is_string($value) && trim($value) === '') {
            return 0;
        }
        
        // Sudah berupa angka
        if (is_numeric($value)) {
            return max(0, (int) $value);
        }
        
        // String yang mungkin berisi angka dengan format tidak standar
        if (is_string($value)) {
            // Hapus semua karakter non-digit kecuali titik dan koma
            $cleaned = preg_replace('/[^\d.,]/', '', trim($value));
            
            // Ganti koma dengan titik (untuk format Indonesia) 
            $cleaned = str_replace(',', '.', $cleaned);
            
            if (is_numeric($cleaned)) {
                return max(0, (int) $cleaned);
            }
        }
        
        return 0;
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

        // Debug: Log branch yang dipilih
        Log::info("Import started for branch_id: $branchId");

        $rows = Excel::toArray([], $file)[0];
        $header = array_shift($rows);
        $dataRows = $rows;
        
        // Debug: Log header Excel
        Log::info("=== EXCEL HEADERS ===");
        Log::info("Headers: " . json_encode($header));
        Log::info("Header count: " . count($header));
        
        // Debug: Log jumlah baris data
        Log::info("Total rows to process: " . count($dataRows));
        
        // --- TAHAP VALIDASI AWAL ---
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
            
            // Debug: Sample parsing untuk 3 baris pertama
            if ($index < 3) {
                $stockRaw = $row[5] ?? 'MISSING';
                $stockParsed = $this->parseStockValue($stockRaw);
                
                Log::info("=== DEBUG ROW " . ($index + 2) . " ===");
                Log::info("SKU: $sku");
                Log::info("Stock raw: " . var_export($stockRaw, true));
                Log::info("Stock parsed: $stockParsed");
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
            // Cari atau buat lokasi STORE untuk cabang
            $location = DB::table('stock_locations')
                ->where('branch_id', $branchId)
                ->where('code', 'STORE')
                ->first();
            
            if (!$location) {
                Log::info("Creating STORE location for branch_id: $branchId");
                
                // Cek apakah tabel stock_locations punya timestamps
                try {
                    $locationId = DB::table('stock_locations')->insertGetId([
                        'branch_id' => $branchId,
                        'code' => 'STORE',
                        'name' => 'Store Location',
                        'is_active' => 1,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                } catch (\Exception $e) {
                    // Jika error timestamps, coba tanpa timestamps
                    $locationId = DB::table('stock_locations')->insertGetId([
                        'branch_id' => $branchId,
                        'code' => 'STORE',
                        'name' => 'Store Location',
                        'is_active' => 1
                    ]);
                }
            } else {
                $locationId = $location->id;
            }

            if (!$locationId) {
                throw new \Exception("Gagal mendapatkan atau membuat lokasi STORE untuk cabang ID: $branchId");
            }

            Log::info("Using location_id: $locationId for branch_id: $branchId");

            $totalProcessed = 0;
            $totalStockUpdated = 0;
            $newCategories = 0;
            $newUoms = 0;
            
            $categoryCache = [];
            $uomCache = [];

            foreach ($dataRows as $index => $row) {
                $rowNumber = $index + 2;
                
                $sku = trim($row[0]);
                $stokAwal = $this->parseStockValue($row[5] ?? null);
                
                // Cari produk berdasarkan SKU
                $existingProduct = DB::table('products')->where('sku', $sku)->first();
                $productId = null;
                $uomId = null;

                if ($existingProduct) {
                    // --- PRODUK SUDAH ADA ---
                    $productId = $existingProduct->id;
                    $uomId = $existingProduct->uom_id;

                } else {
                    // --- PRODUK BARU ---
                    $namaProduk   = trim($row[1] ?? $sku);
                    $kategoriKode = strtoupper(trim($row[2] ?? 'UNCATEGORIZED'));
                    $satuanKode   = strtoupper(trim($row[3] ?? 'PCS'));
                    $hpp          = (float) ($row[4] ?? 0);
                    $catatanAwal  = trim($row[10] ?? '');
                    
                    // Cari atau buat Kategori (TANPA TIMESTAMPS)
                    if (!isset($categoryCache[$kategoriKode])) {
                        $category = DB::table('product_categories')->where('code', $kategoriKode)->first();
                        if (!$category) {
                            $categoryCache[$kategoriKode] = DB::table('product_categories')->insertGetId([
                                'code' => $kategoriKode, 
                                'name' => $kategoriKode
                            ]);
                            $newCategories++;
                        } else {
                            $categoryCache[$kategoriKode] = $category->id;
                        }
                    }
                    $categoryId = $categoryCache[$kategoriKode];
                    
                    // Cari atau buat Satuan (UOM) - TANPA TIMESTAMPS
                    if (!isset($uomCache[$satuanKode])) {
                        $uom = DB::table('uoms')->where('code', $satuanKode)->first();
                        if (!$uom) {
                            $uomCache[$satuanKode] = DB::table('uoms')->insertGetId([
                                'code' => $satuanKode, 
                                'name' => $satuanKode
                            ]);
                            $newUoms++;
                        } else {
                            $uomCache[$satuanKode] = $uom->id;
                        }
                    }
                    $uomId = $uomCache[$satuanKode];

                    $notes = trim(preg_replace('/hpp\s*:\s*[0-9\.]+/i', '', $catatanAwal) . ' hpp:' . $hpp);

                    // Buat produk baru di database (CEK TIMESTAMPS)
                    try {
                        $productId = DB::table('products')->insertGetId([
                            'sku' => $sku, 
                            'name' => $namaProduk, 
                            'category_id' => $categoryId, 
                            'uom_id' => $uomId, 
                            'notes' => $notes,
                            'barcode' => trim($row[6] ?? null), 
                            'length_cm' => empty($row[7]) ? null : (int)$row[7],
                            'width_mm' => empty($row[8]) ? null : (int)$row[8], 
                            'thickness_mm' => empty($row[9]) ? null : (float)$row[9],
                            'is_active' => 1,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    } catch (\Exception $e) {
                        // Jika error timestamps di products, coba tanpa timestamps
                        $productId = DB::table('products')->insertGetId([
                            'sku' => $sku, 
                            'name' => $namaProduk, 
                            'category_id' => $categoryId, 
                            'uom_id' => $uomId, 
                            'notes' => $notes,
                            'barcode' => trim($row[6] ?? null), 
                            'length_cm' => empty($row[7]) ? null : (int)$row[7],
                            'width_mm' => empty($row[8]) ? null : (int)$row[8], 
                            'thickness_mm' => empty($row[9]) ? null : (float)$row[9],
                            'is_active' => 1
                        ]);
                    }
                }

                // --- PROSES STOK UNTUK PRODUK BARU MAUPUN LAMA ---
                if ($stokAwal > 0) {
                    Log::info("Processing stock for SKU: $sku, product_id: $productId, location_id: $locationId, qty: $stokAwal");
                    
                    // Cek apakah sudah ada kuantitas di lokasi ini
                    $quant = DB::table('stock_quants')
                        ->where('product_id', $productId)
                        ->where('location_id', $locationId)
                        ->first();
                    
                    if ($quant) {
                        // Jika sudah ada, UPDATE (tambahkan) kuantitasnya
                        $oldQty = $quant->qty;
                        DB::table('stock_quants')->where('id', $quant->id)->increment('qty', $stokAwal);
                        Log::info("Updated existing quant ID: {$quant->id}, old qty: $oldQty, added: $stokAwal");
                    } else {
                        // Jika belum ada, INSERT kuantitas baru (TANPA TIMESTAMPS)
                        DB::table('stock_quants')->insert([
                            'product_id' => $productId, 
                            'location_id' => $locationId, 
                            'qty' => $stokAwal
                        ]);
                        Log::info("Created new stock quant for product_id: $productId, qty: $stokAwal");
                    }

                    // Catat pergerakan stok
                    try {
                        DB::table('stock_moves')->insert([
                            'product_id' => $productId, 
                            'uom_id' => $uomId, 
                            'qty' => $stokAwal, 
                            'from_location_id' => null,
                            'to_location_id' => $locationId, 
                            'ref_type' => 'ADJUST', 
                            'ref_id' => $productId,
                            'state' => 'DONE', 
                            'created_by' => Auth::id(),
                            'created_at' => now()
                        ]);
                    } catch (\Exception $e) {
                        // Jika tabel stock_moves tidak ada timestamps
                        DB::table('stock_moves')->insert([
                            'product_id' => $productId, 
                            'uom_id' => $uomId, 
                            'qty' => $stokAwal, 
                            'from_location_id' => null,
                            'to_location_id' => $locationId, 
                            'ref_type' => 'ADJUST', 
                            'ref_id' => $productId,
                            'state' => 'DONE', 
                            'created_by' => Auth::id()
                        ]);
                    }
                    
                    $totalStockUpdated++;
                }
                
                $totalProcessed++;
            }

            DB::commit();
            
            Log::info("Import completed successfully. Processed: $totalProcessed, Stock updated: $totalStockUpdated");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Import failed: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            return redirect()->back()->with('import_error', 'Terjadi kesalahan teknis saat impor: ' . $e->getMessage());
        }

        // Pesan sukses yang lebih bersih dan informatif
        $branchName = DB::table('branches')->where('id', $branchId)->value('name');
        $successMessage = "✅ Impor berhasil untuk cabang {$branchName}📦 {$totalProcessed} produk diproses | 📊 {$totalStockUpdated} stok diperbarui";

        // Tambahkan info master data baru yang dibuat
        if ($newCategories > 0 || $newUoms > 0) {
            $successMessage .= "<br><small class='text-muted'>Dibuat otomatis: ";
            if ($newCategories > 0) $successMessage .= "{$newCategories} kategori baru";
            if ($newCategories > 0 && $newUoms > 0) $successMessage .= ", ";
            if ($newUoms > 0) $successMessage .= "{$newUoms} satuan baru";
            $successMessage .= ".</small>";
        }

        return redirect()->back()->with('success', $successMessage);
    }
}
