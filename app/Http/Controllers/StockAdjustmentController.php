<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    private const TBL_QUANTS = 'stock_quants';

    /**
     * Menampilkan form penyesuaian stok.
     */
    public function create()
    {
        $user = Auth::user();
        $branchId = (int) ($user->default_branch_id ?? 0);

        $products = DB::table('products')->where('is_active', 1)->select('id', 'sku', 'name', 'hpp')->orderBy('sku')->limit(1000)->get();
        $activeBranch = $branchId ? DB::table('branches')->where('id', $branchId)->first() : null;
        $branches = !$activeBranch ? DB::table('branches')->where('is_active', 1)->orderBy('name')->get() : collect();

        return view('stock.adjust.create', [
            'products'       => $products,
            'active_branch'  => $activeBranch,
            'branches'       => $branches,
        ]);
    }

    /**
     * Simpan penyesuaian stok dengan jurnal akuntansi dan stock_moves.
     *
     * Alur:
     * 1. Baca stok lama
     * 2. Hitung delta (new_qty - old_qty)
     * 3. Update stock_quants
     * 4. Buat stock_moves record
     * 5. Buat jurnal: DR/CR Persediaan vs HPP
     * 6. Catat audit log
     */
    public function store(Request $request)
    {
        $request->merge(['new_qty' => $this->floatFromInput($request->input('new_qty'))]);

        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'new_qty'    => ['required', 'numeric', 'min:0'],
            'reason'     => ['nullable', 'string', 'max:255'],
        ]);

        $user = Auth::user();
        $userId = (int) ($user?->id ?? 0);
        $branchId = (int) ($user?->default_branch_id ?? 0);

        // Jika user tidak punya default_branch, ambil dari request atau cabang pertama
        if ($branchId <= 0) {
            $branchId = (int) $request->input('branch_id');
        }
        if ($branchId <= 0) {
            $firstBranch = DB::table('branches')->where('is_active', 1)->orderBy('id')->first();
            $branchId = $firstBranch ? (int) $firstBranch->id : 0;
        }
        if ($branchId <= 0) {
            return redirect()->back()->with('error', 'Tidak ada cabang aktif.');
        }

        $productId = (int) $data['product_id'];
        $newQty    = (float) $data['new_qty'];
        $reason    = trim($data['reason'] ?? 'Penyesuaian stok');
        $locationId = $this->ensureStoreLocationId($branchId);

        // Ambil HPP produk
        $product = DB::table('products')->where('id', $productId)->first();
        $hpp = 0.0;
        if ($product) {
            if (!empty($product->hpp) && (float) $product->hpp > 0) {
                $hpp = (float) $product->hpp;
            } elseif ($product->notes && preg_match('/hpp\s*:\s*([0-9\.]+)/i', $product->notes, $m)) {
                $hpp = (float) $m[1];
            }
        }

        // Hitung delta dan nilai
        $oldQty = (float) (DB::table(self::TBL_QUANTS)
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->value('qty') ?? 0.0);

        $delta     = $newQty - $oldQty;
        $deltaVal  = round(abs($delta) * $hpp, 2);

        DB::beginTransaction();

        try {
            // 1. Update stock_quants
            DB::table(self::TBL_QUANTS)->updateOrInsert(
                ['product_id' => $productId, 'location_id' => $locationId],
                ['qty' => $newQty]
            );

            // 2. Buat stock_moves record (jika ada perubahan)
            if (abs($delta) > 0.001) {
                $direction = $delta > 0 ? 'IN' : 'OUT';

                DB::table('stock_moves')->insert([
                    'product_id'       => $productId,
                    'uom_id'           => $product->uom_id ?? 1,
                    'qty'              => abs($delta),
                    'from_location_id' => $direction === 'OUT' ? $locationId : null,
                    'to_location_id'   => $direction === 'IN' ? $locationId : null,
                    'ref_type'         => 'ADJUST',
                    'ref_id'           => 0,
                    'state'            => 'DONE',
                    'created_by'       => $userId,
                    'created_at'       => now(),
                ]);

                // 3. Buat jurnal akuntansi
                // Stock naik:  DR Persediaan (1300) CR Modal (3100)
                // Stock turun: DR HPP (5100) CR Persediaan (1300)
                $persediaanId = \App\Services\AccountingService::accountId('1300');
                $hppId        = \App\Services\AccountingService::accountId('5100');
                $modalId      = \App\Services\AccountingService::accountId('3100');

                if ($delta > 0) {
                    // Stock naik → aset naik, modal naik (selisih opname)
                    \App\Services\AccountingService::createEntry([
                        'date'        => now()->format('Y-m-d'),
                        'description' => "Penyesuaian stok naik: {$reason}",
                        'source_type' => 'STOCK_ADJUSTMENT',
                        'source_id'   => null,
                        'branch_id'   => $branchId,
                        'lines'       => [
                            ['account_id' => $persediaanId, 'debit' => $deltaVal, 'credit' => 0, 'memo' => "Stok +{$delta} item"],
                            ['account_id' => $modalId,      'debit' => 0, 'credit' => $deltaVal, 'memo' => 'Selisih opname'],
                        ],
                    ]);
                } else {
                    // Stock turun → aset turun, beban naik
                    \App\Services\AccountingService::createEntry([
                        'date'        => now()->format('Y-m-d'),
                        'description' => "Penyesuaian stok turun: {$reason}",
                        'source_type' => 'STOCK_ADJUSTMENT',
                        'source_id'   => null,
                        'branch_id'   => $branchId,
                        'lines'       => [
                            ['account_id' => $hppId,        'debit' => $deltaVal, 'credit' => 0, 'memo' => 'Koreksi HPP'],
                            ['account_id' => $persediaanId, 'debit' => 0, 'credit' => $deltaVal, 'memo' => "Stok {$delta} item"],
                        ],
                    ]);
                }
            }

            // 4. Audit log
            DB::table('audit_logs')->insert([
                'event'    => 'STOCK_ADJUST',
                'user_id'  => $userId,
                'ref_type' => 'PRODUCT',
                'ref_id'   => $productId,
                'payload'  => json_encode([
                    'location_id' => $locationId,
                    'branch_id'   => $branchId,
                    'reason'      => $reason,
                    'old_qty'     => $oldQty,
                    'new_qty'     => $newQty,
                    'delta'       => $delta,
                    'hpp'         => $hpp,
                    'delta_value' => $deltaVal,
                ]),
                'created_at' => now(),
            ]);

            DB::commit();

            $label = $delta > 0 ? "naik +{$delta}" : ($delta < 0 ? "turun {$delta}" : 'tidak berubah');
            return redirect()->back()->with('success', "Stok {$label} menjadi {$newQty}. Nilai: Rp " . number_format($deltaVal, 0, ',', '.'));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    /**
     * Memastikan lokasi STORE untuk cabang ada dan mengembalikannya.
     */
    private function ensureStoreLocationId(int $branchId): int
    {
        $loc = DB::table('stock_locations')
            ->where('branch_id', $branchId)
            ->where(function ($q) {
                $q->where('type', 'STORE')->orWhere('code', 'STORE');
            })->first();

        if ($loc) {
            return (int) $loc->id;
        }
        
        return (int) DB::table('stock_locations')->insertGetId([
            'branch_id' => $branchId,
            'code'      => 'STORE',
            'name'      => 'Gudang Utama',
            'type'      => 'STORE',
        ]);
    }

    /**
     * Normalisasi angka dari input.
     */
    private function floatFromInput($value): float
    {
        if ($value === null || trim((string) $value) === '') {
            return 0.0;
        }
        $s = str_replace([' ', ','], ['', '.'], (string) $value);
        return (float) $s;
    }
}
