<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockReportController extends Controller
{
    /**
     * Menampilkan halaman laporan stok menyeluruh dengan filter.
     */
public function index(Request $request)
{
    // 1. Ambil data master untuk dropdown filter
    $branches = DB::table('branches')->where('is_active', 1)->orderBy('name')->get();

    // 2. Query dasar untuk mengambil data stok
    // Query ini persis seperti yang Anda deskripsikan di diagram
    $query = DB::table('stock_quants as q')
        ->join('products as p', 'p.id', '=', 'q.product_id')
        ->join('stock_locations as l', 'l.id', '=', 'q.location_id')
        ->join('branches as b', 'b.id', '=', 'l.branch_id')
        ->select(
            'p.sku as product_sku',
            'p.name as product_name',
            'q.qty',
            'l.name as location_name',
            'b.name as branch_name'
        )
        ->where('q.qty', '>', 0) // Hanya tampilkan yang stoknya ada
        ->where('p.is_active', 1); // ⭐ TAMBAHAN: Hanya tampilkan produk aktif

    // 3. Terapkan filter jika pengguna memilih cabang
    if ($request->filled('branch_id')) {
        $query->where('b.id', $request->input('branch_id'));
    }

    // 4. Urutkan berdasarkan kuantitas dari terkecil ke terbesar
    $query->orderBy('q.qty', 'asc');
    
    // 5. Ambil hasil query dengan paginasi
    $stockData = $query->paginate(50)->withQueryString();

    // 6. Kirim data ke view
    return view('reports.stock.index', [
        'stockData'      => $stockData,
        'branches'       => $branches,
        'selectedBranch' => $request->input('branch_id'),
    ]);
}

}
