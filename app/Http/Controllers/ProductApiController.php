<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductApiController extends Controller
{
    /**
     * Markup harga dari HPP (contoh 30%)
     */
    private const PRICE_MARKUP = 1;

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
                'p.notes'
            )
            ->addSelect(DB::raw("(SELECT IFNULL(SUM(sq.qty),0)
                                 FROM stock_quants sq
                                 WHERE sq.product_id = p.id
                                   AND sq.location_id IN ({$locationIdsString})) as stock"));

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
            $price = $this->priceFromNotes($row->notes);
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
     * Helper untuk menghitung harga jual dari HPP di kolom 'notes'.
     */
    private function priceFromNotes($notes): float
    {
        $hpp = 0.0;
        if ($notes && preg_match('/hpp\s*[:=]\s*([\d\.]+)/i', (string)$notes, $m)) {
            $hpp = (float)$m[1];
        }
        $price = $hpp * self::PRICE_MARKUP;
        return round($price ?: 0.0, 2);
    }
}
