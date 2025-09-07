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

        $products = DB::table('products')->where('is_active', 1)->select('id', 'sku', 'name')->orderBy('sku')->limit(1000)->get();
        $activeBranch = $branchId ? DB::table('branches')->where('id', $branchId)->first() : null;
        $branches = !$activeBranch ? DB::table('branches')->where('is_active', 1)->orderBy('name')->get() : collect();

        return view('stock.adjust.create', [
            'products'       => $products,
            'active_branch'  => $activeBranch,
            'branches'       => $branches,
        ]);
    }

    /**
     * [FINAL] Langsung update kuantitas di lokasi STORE dan mencatat ke audit log.
     */
    public function store(Request $request)
    {
        // Normalisasi input
        $request->merge(['new_qty' => $this->floatFromInput($request->input('new_qty'))]);

        // Validasi, kembalikan 'reason'
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'new_qty'    => ['required', 'numeric', 'min:0'],
            'reason'     => ['nullable', 'string', 'max:255'],
        ]);

        $user = Auth::user();
        $userId = (int) ($user?->id ?? 0);
        $branchId = (int) ($user?->default_branch_id ?? 0);

        if ($branchId <= 0) {
            $request->validate(['branch_id' => ['required', 'integer', 'exists:branches,id']]);
            $branchId = (int) $request->input('branch_id');
        }

        // Cari atau buat lokasi STORE
        $locationId = $this->ensureStoreLocationId($branchId);

        // Ambil data lama SEBELUM diubah, untuk keperluan audit
        $oldQty = DB::table(self::TBL_QUANTS)
            ->where('product_id', (int) $data['product_id'])
            ->where('location_id', $locationId)
            ->value('qty') ?? 0.0;

        // Langsung update atau insert ke tabel stock_quants
        DB::table(self::TBL_QUANTS)->updateOrInsert(
            [
                'product_id'  => (int) $data['product_id'],
                'location_id' => $locationId
            ],
            [
                'qty' => (float) $data['new_qty']
            ]
        );

        // [BARU] Catat aktivitas ke audit_logs
        DB::table('audit_logs')->insert([
            'event'    => 'STOCK_ADJUST_DIRECT', // Event khusus untuk membedakan
            'user_id'  => $userId,
            'ref_type' => 'PRODUCT',
            'ref_id'   => (int) $data['product_id'],
            'payload'  => json_encode([
                'location_id' => $locationId,
                'branch_id'   => $branchId,
                'reason'      => $data['reason'] ?? 'Penyesuaian Langsung',
                'old_qty'     => (float) $oldQty,
                'new_qty'     => (float) $data['new_qty'],
            ]),
            'created_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Stok di Gudang Utama berhasil diatur ulang menjadi ' . $data['new_qty']);
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
