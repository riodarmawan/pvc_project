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
                'p.hpp',
                DB::raw('q.qty * COALESCE(p.hpp, 0) as nilai'),
                'l.name as location_name',
                'b.name as branch_name'
            )
            ->where('q.qty', '>', 0); // Hanya tampilkan yang stoknya ada

        // 3. Terapkan filter jika pengguna memilih cabang
        if ($request->filled('branch_id')) {
            $query->where('b.id', $request->input('branch_id'));
        }

        // 4. Urutkan berdasarkan kuantitas dari terkecil ke terbesar
        $query->orderBy('q.qty', 'asc');
        
        // 5. Nilai aset persediaan atas SELURUH data terfilter (bukan cuma halaman ini).
        //    Dinilai pakai HPP (harga modal) — lazimnya nilai persediaan dicatat sebesar
        //    biaya perolehan, bukan harga jual. Produk tanpa HPP dihitung 0 dan dihitung
        //    terpisah supaya ketahuan kalau master datanya belum lengkap.
        //    select() (bukan selectRaw) supaya kolom detail diganti, bukan ditambah —
        //    kolom non-agregat akan ditolak MySQL saat ONLY_FULL_GROUP_BY aktif.
        $ringkasan = (clone $query)
            ->select(DB::raw('COALESCE(SUM(q.qty * COALESCE(p.hpp, 0)), 0) as total_nilai,
                              COALESCE(SUM(q.qty), 0) as total_qty,
                              SUM(CASE WHEN COALESCE(p.hpp, 0) <= 0 THEN 1 ELSE 0 END) as tanpa_hpp'))
            ->reorder()
            ->first();

        // 6. Ambil hasil query dengan paginasi
        $stockData = $query->paginate(50)->withQueryString();

        // 7. Kirim data ke view
        return view('reports.stock.index', [
            'stockData'      => $stockData,
            'ringkasan'      => $ringkasan,
            'branches'       => $branches,
            'selectedBranch' => $request->input('branch_id'),
        ]);
    }
}
