<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductApiController extends Controller
{

    /**
     * [API] Mengembalikan SEMUA data produk dalam format JSON tanpa paginasi.
     * Data diambil berdasarkan 'branch_id' dari query parameter.
     */
    public function getProductsByBranch(Request $request)
    {
        $branchId = (int) $request->query('branch_id');

        if ($branchId === 0) {
            $user = Auth::user();
            $branchId = (int) ($user->default_branch_id ?? 0);
        }

        if ($branchId === 0) {
            return response()->json(['error' => 'Branch ID harus dipilih atau ditentukan.'], 400);
        }
        
        $selectedBranch = DB::table('branches')->where('id', $branchId)->first();
        if (!$selectedBranch) {
            return response()->json(['error' => 'Cabang yang dipilih tidak ditemukan.'], 404);
        }

        $branchLocationIds = DB::table('stock_locations')
                                ->where('branch_id', $branchId)
                                ->pluck('id')
                                ->all();
        
        $locationIdsString = !empty($branchLocationIds) ? implode(',', $branchLocationIds) : '-1';

        $query = DB::table('products as p')
            ->leftJoin('product_categories as c', 'p.category_id', '=', 'c.id')
            ->select(
                'p.id',
                'p.name',
                'c.name as category_name',
                'p.hpp',
                'p.selling_price',
                'p.notes'
            )
            ->addSelect(DB::raw("(SELECT IFNULL(SUM(sq.qty),0)
                                 FROM stock_quants sq
                                 WHERE sq.product_id = p.id
                                   AND sq.location_id IN ({$locationIdsString})) as stock"))
            ->where('p.is_active', 1);

        // Filter tetap berlaku
        $q = trim((string) $request->get('q', ''));
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('p.sku', 'like', "%{$q}%")
                  ->orWhere('p.name', 'like', "%{$q}%");
            });
        }

        $catId = $request->get('cat_id');
        if (!empty($catId)) {
            $query->where('p.category_id', (int)$catId);
        }

        $query->orderBy('p.name');

        // ================== PERUBAHAN UTAMA DI SINI ==================
        // Mengganti paginate() dengan get() untuk mengambil semua data.
        $products = $query->get();
        // =============================================================

        // Transformasi dilakukan pada koleksi data yang didapat
        $transformedProducts = $products->map(function ($row) {
            $price = $this->resolveSellingPrice($row->selling_price, $row->hpp);
            return [
                'name'          => $row->name,
                'category_name' => $row->category_name,
                'stock'         => (int) floor((float) ($row->stock ?? 0)),
                'price'         => $price,
            ];
        });

        // Kembalikan koleksi yang sudah di-transformasi
        return response()->json($transformedProducts);
    }

    /**
     * Helper untuk menentukan harga jual dari selling_price (fallback ke hpp).
     */
    private function resolveSellingPrice($sellingPrice, $hpp): float
    {
        if (!empty($sellingPrice) && (float) $sellingPrice > 0) {
            return round((float) $sellingPrice, 2);
        }
        return round((float) ($hpp ?? 0), 2);
    }
}
