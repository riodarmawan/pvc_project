<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Http\Response;

class SalesHistoryController extends Controller
{
    /** Daftar cabang yang boleh diakses user (default + user_branches) */
    private function allowedBranchIds(): array
    {
        $u = Auth::user();
        $ids = [];

        if ($u && $u->default_branch_id) $ids[] = (int)$u->default_branch_id;

        $extra = DB::table('user_branches')
            ->where('user_id', $u->id ?? 0)
            ->pluck('branch_id')->map(fn($v) => (int)$v)->all();

        $ids = array_values(array_unique(array_merge($ids, $extra)));
        if (empty($ids)) {
            // fallback: izinkan semua? lebih aman kosong → tidak tampil apa2
            $ids = [-1];
        }
        return $ids;
    }

    /** LIST halaman riwayat */
/** LIST halaman riwayat */
/** LIST halaman riwayat */
public function index(Request $r)
{
    $allowed = $this->allowedBranchIds();

    // Cabang aktif (default = default_branch user)
    $branchId = (int) ($r->query('branch_id') ?: (Auth::user()->default_branch_id ?? 0));
    if (!in_array($branchId, $allowed, true)) {
        $branchId = $allowed[0];
    }

    // Ambil tanggal dari start_date/end_date ATAU d1/d2
    $d1 = $r->query('start_date') ?? $r->query('d1');
    $d2 = $r->query('end_date')   ?? $r->query('d2');

    // Default: bulan ini s/d hari ini
    $start = $d1 ? Carbon::parse($d1)->startOfDay() : now()->startOfMonth();
    $end   = $d2 ? Carbon::parse($d2)->endOfDay()   : now()->endOfDay();
    if ($start->gt($end)) { [$start, $end] = [$end, $start]; }

    $q = trim((string) $r->query('q', ''));

    // ✅ TAMBAHKAN FILTER UNTUK MENGECUALIKAN SALES PROYEK
    $sales = DB::table('pos_sales as s')
        ->leftJoin('customers as c', 'c.id', '=', 's.customer_id')
        ->join('branches as b', 'b.id', '=', 's.branch_id')
        ->selectRaw("
            s.id, s.sale_datetime, s.total, s.status, s.branch_id,
            s.discount, s.change_amount, s.notes,
            b.name as branch_name,
            c.name as customer_name, c.phone as customer_phone,
            (SELECT IFNULL(SUM(l.qty),0) FROM pos_sale_lines l WHERE l.pos_sale_id = s.id) as items_qty
        ")
        ->whereIn('s.branch_id', $allowed)
        ->where('s.branch_id', $branchId)
        ->whereNull('s.project_id') // ✅ KUNCI: Hanya penjualan biasa, bukan dari proyek
        ->whereBetween('s.sale_datetime', [$start, $end])
        ->when($q !== '', function ($qq) use ($q) {
            $qq->where(function ($w) use ($q) {
                // ✅ IMPROVED: Pencarian berdasarkan ID transaksi
                if (ctype_digit($q)) {
                    $w->orWhere('s.id', (int)$q);
                }
                
                // ✅ IMPROVED: Pencarian berdasarkan nama customer (case insensitive)
                $w->orWhere('c.name', 'like', "%{$q}%");
                
                // ✅ NEW: Pencarian berdasarkan nomor HP customer 
                // Support format: 0811, 08112287, 081122872006, dll
                $cleanPhone = preg_replace('/[^0-9]/', '', $q); // Strip non-numeric
                if (strlen($cleanPhone) >= 3) { // Minimal 3 digit untuk mencari HP
                    $w->orWhere(function($phoneQuery) use ($cleanPhone, $q) {
                        // Cari dengan format asli (dengan spasi, dash, dll)
                        $phoneQuery->where('c.phone', 'like', "%{$q}%")
                                   // Cari dengan nomor bersih (hanya digit)
                                   ->orWhere(DB::raw('REGEXP_REPLACE(c.phone, "[^0-9]", "")'), 'like', "%{$cleanPhone}%");
                    });
                }
                
                // ✅ NEW: Pencarian berdasarkan nama + HP sekaligus
                if (str_contains($q, ' ')) {
                    $parts = explode(' ', $q, 2);
                    if (count($parts) == 2) {
                        $name_part = trim($parts[0]);
                        $phone_part = trim($parts[1]);
                        
                        if (!empty($name_part) && !empty($phone_part)) {
                            $w->orWhere(function($combo) use ($name_part, $phone_part) {
                                $combo->where('c.name', 'like', "%{$name_part}%")
                                      ->where('c.phone', 'like', "%{$phone_part}%");
                            });
                        }
                    }
                }
            });
        })
        ->orderByDesc('s.sale_datetime')
        ->paginate(15)
        ->appends([
            'branch_id'  => $branchId,
            'start_date' => $start->toDateString(),
            'end_date'   => $end->toDateString(),
            'q'          => $q,
        ]);

    $branches = DB::table('branches')
        ->whereIn('id', $allowed)
        ->orderBy('name')
        ->get();

    return view('kasir.history', [
        'branches' => $branches,
        'branchId' => $branchId,
        'start'    => $start->toDateString(),
        'end'      => $end->toDateString(),
        'q'        => $q,
        'sales'    => $sales,
    ]);
}



    /** Detail 1 transaksi (AJAX) */

      protected function ensureUomMeterId(): int
    {
        $u = DB::table('uoms')->where('code', 'M')->first();
        if ($u) return (int) $u->id;

        return (int) DB::table('uoms')->insertGetId([
            'code' => 'M',
            'name' => 'Meter',
        ]);
    }
public function detail($id)
{
    $id = (int)$id;
    $allowed = $this->allowedBranchIds();

    $sale = DB::table('pos_sales as s')
        ->leftJoin('customers as c', 'c.id', '=', 's.customer_id')
        ->join('branches as b', 'b.id', '=', 's.branch_id')
        ->selectRaw('s.*, b.name as branch_name, c.name as customer_name, c.phone as customer_phone, c.address as customer_address') // TAMBAH alamat customer
        ->where('s.id', $id)
        ->first();

    if (!$sale || !in_array((int)$sale->branch_id, $allowed, true)) {
        return response()->json(['ok' => false, 'message' => 'Transaksi tidak ditemukan.'], 404);
    }

    $lines = DB::table('pos_sale_lines as l')
        ->join('products as p', 'p.id', '=', 'l.product_id')
        ->selectRaw('p.sku, p.name, l.qty, l.price, l.subtotal')
        ->where('l.pos_sale_id', $id)->get();

    $pays = DB::table('pos_payments')
        ->select('method','amount','ref_no')
        ->where('pos_sale_id', $id)->get();

    return response()->json([
        'ok' => true,
        'sale' => [
            'id' => $sale->id,
            'datetime' => $sale->sale_datetime,
            'branch' => $sale->branch_name,
            'status' => $sale->status,
            'total' => (float)$sale->total,
            'discount' => (float)$sale->discount ?? 0,
            'change_amount' => (float)$sale->change_amount ?? 0,
            'notes' => $sale->notes, // TAMBAH INI
            'customer' => $sale->customer_name,
            'phone' => $sale->customer_phone,
            'address' => $sale->customer_address, // TAMBAH alamat customer
        ],
        'lines' => $lines,
        'payments' => $pays,
    ]);
}



/** Cetak invoice + surat jalan (html sederhana, siap print) - MULTI-PAGE AUTOMATION FIXED */
public function invoice(Request $request, $id)
{
    $id = (int)$id;
    $allowed = $this->allowedBranchIds();
   
    $sale = DB::table('pos_sales as s')
        ->leftJoin('customers as c', 'c.id', '=', 's.customer_id')
        ->leftJoin('branches as b', 'b.id', '=', 's.branch_id')
        ->selectRaw('s.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address, b.name as branch_name, b.address as branch_address') // TAMBAH alamat customer
        ->where('s.id', $id)
        ->first();
       
    if (!$sale || !in_array((int)$sale->branch_id, $allowed, true)) {
        abort(404);
    }

    $lines = DB::table('pos_sale_lines as l')
        ->join('products as p','p.id','=','l.product_id')
        ->join('uoms as u','u.id','=','l.uom_id')
        ->where('l.pos_sale_id', $id)
        ->select('l.*','p.sku','p.name','u.code as uom')
        ->get();

    $actualServiceNames = [];
    if (!empty($sale->project_id)) {
        $serviceNames = DB::table('project_services')
            ->where('project_id', $sale->project_id)
            ->pluck('name')
            ->toArray();
        if (count($serviceNames) > 0) {
            $actualServiceNames = $serviceNames;
        }
    }

    $meterUom = $this->ensureUomMeterId();
    $groupedLines = $this->groupProductLines($lines, $meterUom);

    $calculatedSubtotal = array_sum(array_column($groupedLines, 'total_subtotal'));
    $discount = (float)($sale->discount ?? 0);
    $finalTotal = $calculatedSubtotal - $discount;
    $changeAmount = $this->extractChangeAmount($sale->notes);
    $itemCount = count($groupedLines);

    // Ambil font dari query dan batasi nilai agar aman
    $fontAdjustment = max(-3, min(20, (int) $request->query('font', 0)));

    if ($itemCount <= 6) {
        return $this->generateSinglePageInvoice($sale, $groupedLines, $actualServiceNames, $calculatedSubtotal, $discount, $finalTotal, $changeAmount, $fontAdjustment);
    } else {
        return $this->generateMultiPageInvoice($sale, $groupedLines, $actualServiceNames, $calculatedSubtotal, $discount, $finalTotal, $changeAmount, $fontAdjustment);
    }
}



/**
 * ✅ GROUP PRODUCT LINES (existing logic)
 */
private function groupProductLines($lines, $meterUom): array 
{
    $groupedLines = [];

    foreach ($lines as $line) {
        $productId = $line->product_id;
       
        if (!isset($groupedLines[$productId])) {
            $groupedLines[$productId] = [
                'sku' => $line->sku,
                'name' => $line->name,
                'is_service' => false,
                'qty_material' => 0,
                'qty_sisa' => 0,
                'price_material' => 0,
                'price_sisa' => 0,
                'subtotal_material' => 0,
                'subtotal_sisa' => 0
            ];
        }

        if ($line->sku === 'SRV-GEN') {
            $groupedLines[$productId]['is_service'] = true;
            $groupedLines[$productId]['price_material'] = $line->price;
            $groupedLines[$productId]['subtotal_material'] += $line->subtotal;
        } else if ($line->uom_id == $meterUom) {
            $groupedLines[$productId]['qty_sisa'] += $line->qty;
            $groupedLines[$productId]['price_sisa'] = $line->price;
            $groupedLines[$productId]['subtotal_sisa'] += $line->subtotal;
        } else {
            $groupedLines[$productId]['qty_material'] += $line->qty;
            $groupedLines[$productId]['price_material'] = $line->price;
            $groupedLines[$productId]['subtotal_material'] += $line->subtotal;
        }
    }

    foreach ($groupedLines as &$item) {
        $item['total_subtotal'] = $item['subtotal_material'] + $item['subtotal_sisa'];
        $item['display_price'] = $item['price_material'] > 0 ? $item['price_material'] : $item['price_sisa'];
    }
    unset($item);

    return $groupedLines;
}

/**
 * ✅ EXTRACT CHANGE AMOUNT FROM NOTES
 */
private function extractChangeAmount($notes): float 
{
    $changeAmount = 0;
    if (!empty($notes) && preg_match('/KEMBALIAN:\\s*Rp\\s*([\\d\\.,]+)/i', $notes, $m)) {
        $changeAmount = (float)str_replace(['.', ','], ['', '.'], $m[1]);
    }
    return $changeAmount;
}
/**
 * Estimasi jumlah item per halaman untuk mode multipage.
 * Kita hitung dari font tabel dan faktor pembesaran di renderer (2×).
 * Jaga minimum 3 baris agar tidak kosong.
 */
private function estimateItemsPerPage(array $groupedLines, array $fonts, string $docType = 'invoice'): int
{
    // base rows saat ukuran normal (sebelum dibesarkan)
    // angka ini cocok dengan layout 21x14cm milikmu
    $baseRows = 15; // aman untuk invoice & surat jalan ukuran normal

    // di renderer kamu pakai $fs = $fonts['table'] * 2; → scale = 2
    $scale = 2;

    // ada margin header/footer yang sedikit berbeda, beri faktor koreksi kecil
    $headerFootCorrection = ($docType === 'invoice') ? 0.9 : 0.85;

    $rows = (int) floor($baseRows / $scale * $headerFootCorrection);

    // safety guard
    $rows = max(3, $rows);

    // kalau item sedikit dari rows, biarkan semua masuk satu halaman
    $total = count($groupedLines);
    if ($total <= $rows) return $total;

    return $rows;
}

/**
 * ✅ GENERATE SINGLE PAGE INVOICE - Untuk order kecil dengan font optimal
 */
private function generateSinglePageInvoice($sale, $groupedLines, $actualServiceNames, $calculatedSubtotal, $discount, $finalTotal, $changeAmount, $fontAdjustment)
{
    $fonts = [
        'title'  => max(8, 16 + $fontAdjustment),
        'header' => max(8, 14 + $fontAdjustment),
        'data'   => max(8, 11 + $fontAdjustment),
        'table'  => max(8, 10 + $fontAdjustment),
        'small'  => max(7, 9 + $fontAdjustment),
    ];

    return $this->renderSinglePageHTML($sale, $groupedLines, $actualServiceNames, $calculatedSubtotal, $discount, $finalTotal, $changeAmount, $fonts);
}


private function generateMultiPageInvoice($sale, $groupedLines, $actualServiceNames, $calculatedSubtotal, $discount, $finalTotal, $changeAmount, $fontAdjustment)
{
    $fonts = [
        'title'  => max(8, 14 + $fontAdjustment),
        'header' => max(8, 12 + $fontAdjustment),
        'data'   => max(8, 10 + $fontAdjustment),
        'table'  => max(8,  9 + $fontAdjustment),
        'small'  => max(7,  8 + $fontAdjustment),
    ];

    // ⬇️ gunakan estimasi per halaman yang mempertimbangkan pembesaran 2×
    $itemsPerPage = $this->estimateItemsPerPage($groupedLines, $fonts, 'invoice');

    $pages = $this->paginateItems($groupedLines, $itemsPerPage);

    // ⬇️ teruskan $itemsPerPage ke renderer multipage
    return $this->renderMultiPageHTML(
        $sale, $pages, $actualServiceNames,
        $calculatedSubtotal, $discount, $finalTotal, $changeAmount,
        $fonts, $itemsPerPage
    );
}



/**
 * ✅ CALCULATE ITEMS PER PAGE
 */
/**
 * ✅ FIXED ITEMS PER PAGE CALCULATION - SMART DISTRIBUTION
 */
private function calculateItemsPerPage($groupedLines): int 
{
    $totalItems = count($groupedLines);
    
    // ✅ SMART DISTRIBUTION based on total items for optimal pagination
    if ($totalItems <= 6) {
        return $totalItems; // Single page - all items
    } elseif ($totalItems <= 12) {
        return 6; // 2 pages @ 6 items each (12 items max)
    } elseif ($totalItems <= 16) {
        return 8; // 2 pages @ 8 items each (16 items max)
    } elseif ($totalItems <= 24) {
        return 8; // 3 pages @ 8 items each (24 items max)
    } elseif ($totalItems <= 30) {
        return 10; // 3 pages @ 10 items each (30 items max)
    } else {
        return 12; // Large orders = 12 items per page maximum
    }
}

/**
 * ✅ PAGINATE ITEMS WITH SMART GROUPING
 */
/**
 * ✅ FIXED PAGINATION WITH EVEN DISTRIBUTION
 */
private function paginateItems($groupedLines, $itemsPerPage): array
{
    $totalItems = count($groupedLines);
    
    if ($totalItems <= $itemsPerPage) {
        return [$groupedLines];
    }
    
    $pages = [];
    $currentPage = [];
    
    foreach ($groupedLines as $key => $item) {
        $currentPage[$key] = $item;
        if (count($currentPage) == $itemsPerPage) {
            $pages[] = $currentPage;
            $currentPage = [];
        }
    }
    
    if (!empty($currentPage)) {
        $pages[] = $currentPage;
    }
    
    return $pages;
}


/**
 * ✅ RENDER SINGLE PAGE HTML
 * Sekarang: INVOICE (hal.1) lalu SURAT JALAN (hal.2)
 */
private function renderSinglePageHTML($sale, $groupedLines, $actualServiceNames, $calculatedSubtotal, $discount, $finalTotal, $changeAmount, $fonts): Response
{
    $saleDate  = date('d/m/Y H:i', strtotime($sale->sale_datetime));
    $todayDate = date('d/m/Y');

    $html  = '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8">';
    $html .= '<title>Invoice + Surat Jalan #'.$sale->id.'</title>';
    $html .= $this->getSinglePageCSS($fonts);
    $html .= '</head><body>';

    // ===== INVOICE dulu =====
    $html .= '<div class="page-container">';
    $html .= $this->renderInvoiceContent(
        $sale,
        $groupedLines,
        $actualServiceNames,
        $calculatedSubtotal,
        $discount,
        $finalTotal,
        $changeAmount,
        $fonts,
        $saleDate,
        /* isFirstPage */ true,
        /* currentPage  */ 1,
        /* totalPages    */ 1
    );
    $html .= '</div>';

    // ===== kemudian SURAT JALAN =====
    $html .= '<div class="page-container">';
    $html .= $this->renderSuratJalanContent(
        $sale,
        $groupedLines,
        $actualServiceNames,
        $fonts,
        $todayDate,
        /* isFirstPage */ true,
        /* currentPage  */ 1,
        /* totalPages    */ 1
    );
    $html .= '</div>';

    $html .= $this->getAutoPrintScript();
    $html .= '</body></html>';

    return response($html);
}


private function renderMultiPageHTML($sale, $pages, $actualServiceNames, $calculatedSubtotal, $discount, $finalTotal, $changeAmount, $fonts, int $itemsPerPage)
{
    $saleDate  = date('d/m/Y H:i', strtotime($sale->sale_datetime));
    $todayDate = date('d/m/Y');

    $html  = '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8">';
    $html .= '<title>Invoice + Surat Jalan #'.$sale->id.' (Multi-Page)</title>';
    $html .= $this->getMultiPageCSS($fonts);
    $html .= '</head><body>';

    $totalPages = count($pages);

    // 1) Semua INVOICE dulu
    $runningSubtotal = 0;
    foreach ($pages as $i => $pageItems) {
        $current = $i + 1;
        $isFirst = ($i === 0);
        $isLast  = ($i === $totalPages - 1);

        $pageSubtotal     = array_sum(array_column($pageItems, 'total_subtotal'));
        $runningSubtotal += $pageSubtotal;

        $html .= '<div class="page-container">';
        $html .= $this->renderInvoiceContent(
            $sale, $pageItems, $actualServiceNames,
            $calculatedSubtotal, $discount, $finalTotal, $changeAmount,
            $fonts, $saleDate,
            $isFirst, $current, $totalPages,
            $runningSubtotal, $isLast,
            $itemsPerPage                 // ⬅️ NEW: teruskan per-page
        );
        $html .= '</div>';
    }

    // 2) Lalu semua SURAT JALAN
    foreach ($pages as $i => $pageItems) {
        $current = $i + 1;
        $isFirst = ($i === 0);

        $html .= '<div class="page-container">';
        $html .= $this->renderSuratJalanContent(
            $sale, $pageItems, $actualServiceNames,
            $fonts, $todayDate,
            $isFirst, $current, $totalPages,
            $itemsPerPage                 // ⬅️ NEW: teruskan per-page
        );
        $html .= '</div>';
    }

    $html .= $this->getAutoPrintScript();
    $html .= '</body></html>';

    return response($html);
}




private function renderSuratJalanContent(
    $sale,
    $pageItems,
    $actualServiceNames,
    $fonts,
    $todayDate,
    $isFirstPage,
    $currentPage,
    $totalPages,
    int $itemsPerPage = 10
): string
{
    $html = '';

    // ===== Header =====
    $html .= '<div class="page-header">';
    $html .= '<div style="font-size: '.$fonts['title'].'px; font-weight: bold; text-align: center; margin-bottom: 2mm;">SURAT JALAN</div>';
    if ($totalPages > 1) {
        $html .= '<div style="text-align:center; font-weight:bold; font-size: '.$fonts['data'].'px; margin-bottom:2mm;">No SJ-'
              . str_pad($sale->id, 5, '0', STR_PAD_LEFT)
              . ' | Halaman '.$currentPage.' dari '.$totalPages.'</div>';
    } else {
        $html .= '<div style="text-align:center; font-weight:bold; font-size: '.$fonts['data'].'px; margin-bottom:2mm;">No SJ-'
              . str_pad($sale->id, 5, '0', STR_PAD_LEFT).'</div>';
    }

    if ($isFirstPage) {
        // Company + Customer (halaman pertama)
        $html .= '<div style="font-size: '.$fonts['header'].'px; font-weight: bold; text-align: center; margin-bottom: 3mm;">';
        $html .= '<div>'.strtoupper(e($sale->branch_name ?? 'MAJALENGKA')).'</div>';
        $html .= '<div style="font-size: '.$fonts['small'].'px;">Sedia WPC Dinding, Atap UPVC, Kaca Bevel, Hollo, Wall Moulding PVC, Lantai Vinyl, Lantai SPC, dll</div>';
        if (!empty($sale->branch_address)) { $html .= '<div style="font-size: '.$fonts['small'].'px;">'.e($sale->branch_address).'</div>'; }
        $html .= '<div style="font-size: '.$fonts['small'].'px; font-weight: bold;">Telp: 0811 2287 2006</div>';
        $html .= '</div>';
        $html .= '<div style="border-top: 2px solid #000; margin: 2mm 0;"></div>';

        // KEPADA/TANGGAL (besar)
        $bigFS = ($fonts['data'] * 1.5);
        $html .= '<table style="width:100%; border-collapse:collapse; font-size: '.$bigFS.'px; font-weight:bold; margin-bottom:4mm;"><tr>';
        $html .= '<td style="width:50%; border:none;">KEPADA: ';
        if ($sale->customer_name) {
            $html .= '<strong>'.strtoupper(e($sale->customer_name)).'</strong><br>';
            if ($sale->customer_phone) $html .= 'Telp: '.e($sale->customer_phone).'<br>';
            if ($sale->customer_address) $html .= 'Alamat: '.e($sale->customer_address);
        } else {
            $html .= '<strong>PELANGGAN UMUM</strong>';
        }
        $html .= '</td>';
        $html .= '<td style="width:50%; text-align:right; border:none;">TANGGAL: '.$todayDate.' '.date('H:i').'</td>';
        $html .= '</tr></table>';
    } else {
        // Header lanjutan (halaman 2+)
        $html .= '<div style="text-align:center; font-size: '.$fonts['data'].'px; font-weight:bold; margin-bottom:3mm;">';
        $html .= '<strong>LANJUTAN SURAT JALAN</strong><br>';
        $customerInfo = $sale->customer_name ? strtoupper(e($sale->customer_name)) : 'PELANGGAN UMUM';
        if ($sale->customer_address) $customerInfo .= ' | Alamat: '.e($sale->customer_address);
        $html .= 'Customer: '.$customerInfo;
        $html .= '</div>';
        $html .= '<div style="border-top: 2px solid #000; margin: 2mm 0;"></div>';
    }
    $html .= '</div>'; // end header

    // ===== Body =====
    $html .= '<div class="page-content">';

    // Tabel barang (pakai itemsPerPage untuk penomoran konsisten)
    $html .= $this->renderSuratJalanTable($pageItems, $actualServiceNames, $fonts, $currentPage, $totalPages, $itemsPerPage);

    // ===== Tanda tangan (hanya single page atau halaman terakhir) =====
    if ($totalPages == 1 || $currentPage == $totalPages) {
        if ($totalPages == 1) {
            $html .= '<div class="mb-3mm" style="text-align:center; font-weight:bold; font-size: '.$fonts['data'].'px; border:1px solid #000; padding:2mm;">';
            $html .= 'Total Barang: '.count($pageItems).' ('.$this->terbilang(count($pageItems)).') Jenis';
            $html .= '</div>';
        }
        $html .= '<table class="no-break" style="width:100%; border-collapse:collapse; margin-top:3mm; font-size: '.$fonts['small'].'px; font-weight:bold;"><tr>';
        $html .= '<td style="width:40%; border:none;">KETERANGAN: ';
        if (!empty($sale->notes)) {
            $cleanNotes = preg_replace("/KEMBALIAN.*/i", "", $sale->notes);
            $cleanNotes = trim($cleanNotes);
            $html .= !empty($cleanNotes) ? e($cleanNotes) : 'Barang diterima dalam keadaan baik';
        } else { $html .= 'Barang diterima dalam keadaan baik'; }
        $html .= '</td>';
        $html .= '<td style="width:30%; text-align:center; border:none;">YANG MENYERAHKAN<br><br><br><br></td>';
        $html .= '<td style="width:30%; text-align:center; border:none;">YANG MENERIMA<br><br><br><br></td>';
        $html .= '</tr></table>';
    }

    $html .= '</div>'; // end page-content

    // ===== Footer multipage =====
    if ($totalPages > 1) {
        $msg = ($currentPage < $totalPages)
            ? ('Halaman '.$currentPage.' dari '.$totalPages.' - Barang diterima dalam keadaan baik')
            : ('Halaman '.$currentPage.' dari '.$totalPages);
        $html .= '<div class="page-footer">'.$msg.'</div>';
    }

    return $html;
}









private function renderSuratJalanTable($pageItems, $actualServiceNames, $fonts, $currentPage, $totalPages): string
{
    $fs        = $fonts['table'] * 2; // 2× font
    $padHead   = '1.5mm';
    $padCell   = '1.2mm';

    $html = '<table style="width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid #000; font-size: '.$fs.'px; font-weight: bold;">';
    $html .= '<thead><tr style="border-bottom: 1px solid #000;">';
    $html .= '<th style="width: 6%;  text-align: center; padding: '.$padHead.'; border-right: 1px solid #000; background: #f0f0f0;">NO</th>';
    $html .= '<th style="width: 13%; padding: '.$padHead.'; border-right: 1px solid #000; background: #f0f0f0;">KODE</th>';
    $html .= '<th style="width: 56%; padding: '.$padHead.'; border-right: 1px solid #000; background: #f0f0f0;">NAMA BARANG</th>';
    $html .= '<th style="width: 12%; text-align: center; padding: '.$padHead.'; border-right: 1px solid #000; background: #f0f0f0;">QTY</th>';
    $html .= '<th style="width: 13%; text-align: center; padding: '.$padHead.'; background: #f0f0f0;">SATUAN</th>';
    $html .= '</tr></thead><tbody>';

    // Penomoran kontinu (tetap)
    $itemsPerPage   = $this->calculateOptimalItemsPerPage(count($pageItems), $totalPages, $currentPage);
    $startingNumber = (($currentPage - 1) * $itemsPerPage) + 1;
    $no = $startingNumber;

    foreach ($pageItems as $item) {
        $html .= '<tr>';
        $html .= '<td style="width: 6%; text-align: center; padding: '.$padCell.'; border-right: 1px solid #000; border-bottom: 1px dotted #ccc;">'.str_pad($no, 2, '0', STR_PAD_LEFT).'</td>';

        $displaySku = $item['sku'];
        if ($item['sku'] === 'SRV-GEN' && !empty($actualServiceNames)) { $displaySku = 'LAYANAN'; }
        $html .= '<td style="width: 13%; padding: '.$padCell.'; border-right: 1px solid #000; border-bottom: 1px dotted #ccc; overflow: hidden;">'.e($displaySku).'</td>';

        $displayName = $item['name'];
        if ($item['sku'] === 'SRV-GEN' && !empty($actualServiceNames)) {
            $displayName = count($actualServiceNames) === 1 ? $actualServiceNames[0] : implode(', ', $actualServiceNames);
        }
        $nameText = e($displayName);
        if ($item['qty_sisa'] > 0) { $nameText .= ' (Sisa: '.number_format($item['qty_sisa'], 2).' m)'; }
        $html .= '<td style="width: 56%; padding: '.$padCell.'; border-right: 1px solid #000; border-bottom: 1px dotted #ccc; overflow: hidden;">'.$nameText.'</td>';

        if ($item['is_service']) {
            $html .= '<td style="width: 12%; text-align: center; padding: '.$padCell.'; border-right: 1px solid #000; border-bottom: 1px dotted #ccc;">1</td>';
            $html .= '<td style="width: 13%; text-align: center; padding: '.$padCell.'; border-bottom: 1px dotted #ccc;">Layanan</td>';
        } elseif ($item['qty_material'] > 0) {
            $html .= '<td style="width: 12%; text-align: center; padding: '.$padCell.'; border-right: 1px solid #000; border-bottom: 1px dotted #ccc;">'.number_format($item['qty_material'], 0).'</td>';
            $html .= '<td style="width: 13%; text-align: center; padding: '.$padCell.'; border-bottom: 1px dotted #ccc;">Pcs</td>';
        } else {
            $html .= '<td style="width: 12%; text-align: center; padding: '.$padCell.'; border-right: 1px solid #000; border-bottom: 1px dotted #ccc;">-</td>';
            $html .= '<td style="width: 13%; text-align: center; padding: '.$padCell.'; border-bottom: 1px dotted #ccc;">-</td>';
        }

        $html .= '</tr>';
        $no++;
    }

    $html .= '</tbody></table>';
    return $html;
}


/**
 * ✅ NEW HELPER FUNCTION - Calculate optimal items per page for numbering
 */
private function calculateOptimalItemsPerPage($currentPageItemCount, $totalPages, $currentPage): int
{
    // ✅ For numbering calculation - estimate average items per page
    if ($totalPages <= 1) {
        return $currentPageItemCount;
    }
    
    // ✅ Return estimated items per page for continuous numbering
    if ($totalPages == 2) {
        return $currentPage == 1 ? 8 : 8; // Assume 8 items per page for 2 pages
    } elseif ($totalPages == 3) {
        return 8; // 8 items per page for 3 pages
    } else {
        return 10; // 10 items per page for 4+ pages
    }
}

private function renderInvoiceContent(
    $sale,
    $pageItems,
    $actualServiceNames,
    $calculatedSubtotal,
    $discount,
    $finalTotal,
    $changeAmount,
    $fonts,
    $saleDate,
    $isFirstPage,
    $currentPage,
    $totalPages,
    $runningSubtotal = null,
    $isLastPage = false,
    int $itemsPerPage = 10
): string
{
    $html = '';

    /* ===== HEADER ===== */
    $html .= '<div class="page-header">';
    $html .= '<div style="font-size: '.$fonts['title'].'px; font-weight: bold; text-align: center; margin-bottom: 2mm;">FAKTUR PENJUALAN</div>';
    if ($totalPages > 1) {
        $html .= '<div style="text-align:center; font-weight:bold; font-size: '.$fonts['data'].'px; margin-bottom:2mm;">Invoice '
              . str_pad($sale->id, 5, "0", STR_PAD_LEFT)
              . ' | Halaman '.$currentPage.' dari '.$totalPages.'</div>';
    } else {
        $html .= '<div style="text-align:center; font-weight:bold; font-size: '.$fonts['data'].'px; margin-bottom:2mm;">Invoice '
              . str_pad($sale->id, 5, "0", STR_PAD_LEFT).'</div>';
    }

    if ($isFirstPage) {
        $html .= '<div style="font-size: '.$fonts['header'].'px; font-weight: bold; text-align: center; margin-bottom: 2mm;">';
        $html .= '<div>'.strtoupper(e($sale->branch_name ?? 'MAJALENGKA')).'</div>';
        $html .= '<div style="font-size: '.$fonts['small'].'px;">Sedia WPC Dinding, Atap UPVC, Kaca Bevel, Hollo, Wall Moulding PVC, Lantai Vinyl, Lantai SPC, dll</div>';
        if (!empty($sale->branch_address)) {
            $html .= '<div style="font-size: '.$fonts['small'].'px;">'.e($sale->branch_address).'</div>';
        }
        $html .= '<div style="font-size: '.$fonts['small'].'px; font-weight: bold;">Telp: 0811 2287 2006</div>';
        $html .= '</div>';
        $html .= '<div style="border-top: 2px solid #000; margin: 2mm 0;"></div>';

        // Info pelanggan — diperbesar (~1.5×)
        $bigFS = ($fonts['data'] * 1.5);
        $html .= '<table style="width:100%; border-collapse:collapse; font-size: '.$bigFS.'px; font-weight:bold; margin-bottom:2mm;"><tr>';
        $html .= '<td style="width:50%; border:none;">PELANGGAN: ';
        if ($sale->customer_name) {
            $html .= '<strong>'.e($sale->customer_name).'</strong><br>';
            if ($sale->customer_phone) { $html .= 'Telp: '.e($sale->customer_phone).'<br>'; }
            if ($sale->customer_address) { $html .= 'Alamat: '.e($sale->customer_address); }
        } else {
            $html .= '<strong>Pelanggan Umum</strong>';
        }
        $html .= '</td>';
        $html .= '<td style="width:50%; text-align:right; border:none;">TANGGAL: '.$saleDate.'</td>';
        $html .= '</tr></table>';
    } else {
        $html .= '<div style="text-align:center; font-size: '.$fonts['data'].'px; font-weight:bold; margin-bottom:2mm;"><strong>LANJUTAN FAKTUR PENJUALAN</strong><br>';
        $customerInfo = $sale->customer_name ? e($sale->customer_name) : 'Pelanggan Umum';
        if ($sale->customer_address) { $customerInfo .= ' | Alamat: '.e($sale->customer_address); }
        $html .= 'Customer: '.$customerInfo.'</div>';
        $html .= '<div style="border-top: 2px solid #000; margin: 2mm 0;"></div>';
    }
    $html .= '</div>'; // end header

    /* ===== BODY ===== */
    $html .= '<div class="page-content">';

    // Tabel item
    $html .= $this->renderInvoiceTable($pageItems, $actualServiceNames, $fonts, $currentPage, $totalPages, $itemsPerPage);

    if ($totalPages == 1 || $isLastPage) {

        /* -- spacer kecil agar tidak “nempel” ke tabel -- */
        $html .= '<div class="mt-1mm"></div>';

        /* === 2A. Kotak subtotal/total — HANYA ini yang benar2 no-break === */
        $html .= '<div class="no-break">';
        $html .= '<table class="mb-2mm" style="width:100%; border-collapse:collapse; font-size: '.$fonts['data'].'px; font-weight:bold;"><tr>';
        $html .= '<td style="width:50%; border:none;"></td>';
        $html .= '<td style="width:50%; border:2px solid #000; padding:1.5mm;">'; /* padding dipadatkan */
        $html .= '<div>Subtotal: Rp '.number_format($calculatedSubtotal, 0, ",", ".").'</div>';
        if ($discount > 0) {
            $html .= '<div>Potongan: Rp '.number_format($discount, 0, ",", ".").'</div>';
        }
        $html .= '<div style="font-size: '.$fonts['header'].'px; border-top:1px solid #000; padding-top:1.5mm;">TOTAL BAYAR: Rp '.number_format($finalTotal, 0, ",", ".").'</div>';
        if ($changeAmount > 0) {
            $html .= '<div>Kembalian: Rp '.number_format($changeAmount, 0, ",", ".").'</div>';
        }
        $html .= '</td></tr></table>';
        $html .= '</div>'; // end no-break (hanya totals)

        /* === 2B. Pembayaran — fleksibel; cenderung tidak terbelah === */
        $html .= '<div class="mb-2mm avoid-break-before keep-together" style="text-align:center; font-weight:bold; font-size: '.$fonts['data'].'px; border:1px solid #000; padding:1.5mm;">';
        $html .= 'PEMBAYARAN: Transfer Bank BCA 4181380637 a/n YADI MULYADI';
        $html .= '</div>';

        /* === 2C. Catatan + tanda tangan — fleksibel === */
        $html .= '<table class="avoid-break-before keep-together" style="width:100%; border-collapse:collapse; font-size: '.$fonts['small'].'px; font-weight:bold;"><tr>';
        $html .= '<td style="width:60%; border:none;">';
        if (!empty($sale->notes)) {
            $cleanNotes = preg_replace("/KEMBALIAN.*/i", "", $sale->notes);
            $cleanNotes = trim($cleanNotes);
            if (!empty($cleanNotes)) { $html .= 'CATATAN: '.e($cleanNotes); }
        }
        $html .= '</td>';
        $html .= '<td style="width:40%; text-align:center; border:none;">HORMAT KAMI<br><br><br></td>';
        $html .= '</tr></table>';

    } else {
        // halaman antara: tidak ada subtotal apapun
        $html .= '<div class="mt-1mm"></div>';
    }

    $html .= '</div>'; // end page-content

    /* ===== FOOTER ===== */
    if ($totalPages > 1 && !$isLastPage) {
        $html .= '<div class="page-footer">Halaman '.$currentPage.' dari '.$totalPages.' - Lanjutan di halaman berikutnya</div>';
    }

    return $html;
}









private function renderInvoiceTable($pageItems, $actualServiceNames, $fonts, $currentPage, $totalPages): string
{
    $fs = $fonts['table'] * 2;      // 2× font
    $padHeader = '1.5mm';           // padding sedikit lebih besar
    $padCell   = '1.2mm';

    $html = '<table style="width:100%; border-collapse:separate; border-spacing:0; border:1px solid #000; font-size: '.$fs.'px; font-weight:bold;">';
    $html .= '<thead><tr style="border-bottom: 1px solid #000;">';
    $html .= '<th style="width: 52%; padding: '.$padHeader.'; border-right:1px solid #000; background:#f0f0f0;">NAMA PRODUK</th>';
    $html .= '<th style="width: 15%; text-align:center; padding: '.$padHeader.'; border-right:1px solid #000; background:#f0f0f0;">QTY</th>';
    $html .= '<th style="width: 16%; text-align:right;  padding: '.$padHeader.'; border-right:1px solid #000; background:#f0f0f0;">HARGA</th>';
    $html .= '<th style="width: 17%; text-align:right;  padding: '.$padHeader.'; background:#f0f0f0;">SUBTOTAL</th>';
    $html .= '</tr></thead><tbody>';

    foreach ($pageItems as $item) {
        $html .= '<tr>';

        $displayName = $item['name'];
        $displaySku  = $item['sku'];
        if ($item['sku'] === 'SRV-GEN' && !empty($actualServiceNames)) {
            $displayName = count($actualServiceNames) === 1 ? $actualServiceNames[0] : implode(', ', $actualServiceNames);
            $displaySku  = 'LAYANAN';
        }
        $nameText = e($displayName).' ('.e($displaySku).')';
        if ($item['qty_sisa'] > 0) { $nameText .= ' [Sisa: '.number_format($item['qty_sisa'], 2).'m]'; }

        $html .= '<td style="width:52%; padding: '.$padCell.'; border-right:1px solid #000; border-bottom:1px dotted #ccc; overflow:hidden;">'.$nameText.'</td>';

        if ($item['is_service']) {
            $html .= '<td style="width:15%; text-align:center; padding: '.$padCell.'; border-right:1px solid #000; border-bottom:1px dotted #ccc;">1 Layanan</td>';
        } elseif ($item['qty_material'] > 0) {
            $html .= '<td style="width:15%; text-align:center; padding: '.$padCell.'; border-right:1px solid #000; border-bottom:1px dotted #ccc;">'.number_format($item['qty_material'], 0).'</td>';
        } else {
            $html .= '<td style="width:15%; text-align:center; padding: '.$padCell.'; border-right:1px solid #000; border-bottom:1px dotted #ccc;">-</td>';
        }

        $html .= '<td style="width:16%; text-align:right; padding: '.$padCell.'; border-right:1px solid #000; border-bottom:1px dotted #ccc;">'.number_format($item['display_price'], 0, ',', '.').'</td>';
        $html .= '<td style="width:17%; text-align:right; padding: '.$padCell.'; border-bottom:1px dotted #ccc;">'.number_format($item['total_subtotal'], 0, ',', '.').'</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';

    return $html;
}


private function getSinglePageCSS($fonts): string
{
    return '
    <style>
      @page { size: 21cm 14cm; margin: 4mm; }

      * { margin: 0; padding: 0; box-sizing: border-box; }

      body {
        font-family: "Courier New", "Consolas", monospace;
        font-size: ' . $fonts['data'] . 'px;
        font-weight: bold;
        line-height: 1.1;
        color: #000;
        -webkit-print-color-adjust: exact;
      }

      .page-container {
        width: 20cm;
        padding: 20mm;                 /* sedikit diperkecil agar ada ruang ekstra */
        page-break-after: always;
        break-after: page;
        overflow: visible;
        position: relative;
      }
      .page-container:last-child { page-break-after: auto; break-after: auto; }

      /* JANGAN beri padding bawah, agar sisa ruang bisa dipakai blok total */
      .page-content { padding-bottom: 0; }

      .page-header { margin-bottom: 2mm; }
      table { width: 100%; border-collapse: separate; border-spacing: 0; }

      .mb-2mm { margin-bottom: 2mm; }
      .mt-1mm { margin-top: 1mm; }

      /* blok yang benar-benar tidak boleh terpotong */
      .no-break { page-break-inside: avoid; break-inside: avoid; }

      /* hindari page break sebelum elemen (lebih lunak ketimbang no-break) */
      .avoid-break-before { page-break-before: auto; break-before: avoid; }

      /* cenderung tidak terbelah */
      .keep-together { page-break-inside: avoid; break-inside: avoid; }

      @media print {
        @page { size: 21cm 14cm; margin: 4mm; }
        body { font-weight: bold !important; line-height: 1.1 !important; font-size: ' . $fonts['data'] . 'px; }
        .page-container { width: 20cm; padding: 1.5mm; page-break-after: always; break-after: page; }
        .page-container:last-child { page-break-after: auto; break-after: auto; }
      }
    </style>';
}




private function getMultiPageCSS($fonts): string
{
    return '
    <style>
      @page { size: 21cm 14cm; margin: 4mm; }

      body {
        font-family: "Courier New", "Consolas", monospace;
        font-size: ' . $fonts['data'] . 'px;
        font-weight: bold;
        line-height: 1.1;
        color: #000;
        -webkit-print-color-adjust: exact;
      }

      .page-container {
        width: 20cm;
        padding: 20mm;
        page-break-after: always;
        break-after: page;
        overflow: visible;
        position: relative;
      }
      .page-container:last-child { page-break-after: auto; break-after: auto; }

      .page-content { padding-bottom: 14mm; }
      .page-header { margin-bottom: 3mm; }
      table { width: 100%; border-collapse: separate; border-spacing: 0; }
      .mb-3mm { margin-bottom: 3mm; }

      .page-footer {
        margin-top: 4mm;
        border-top: 1px solid #000;
        padding-top: 2mm;
        text-align: center;
        font-size: ' . $fonts['small'] . 'px;
      }

      .no-break { page-break-inside: avoid; break-inside: avoid; }
    </style>';
}




/**
 * ✅ GET AUTO-PRINT SCRIPT
 */
private function getAutoPrintScript(): string
{
    return '<script>
        window.addEventListener("load", function() {
            setTimeout(function() {
                window.print();
            }, 500);
        });
    </script>';
}










/**
 * Helper function untuk konversi angka ke terbilang
 */
private function terbilang($angka) {
    $angka = (int) $angka;
    $bilangan = array('', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas');
    
    if ($angka < 12) {
        return $bilangan[$angka];
    } elseif ($angka < 20) {
        return $bilangan[$angka - 10] . ' Belas';
    } elseif ($angka < 100) {
        return $bilangan[intval($angka / 10)] . ' Puluh ' . $bilangan[$angka % 10];
    } elseif ($angka < 200) {
        return 'Seratus ' . $this->terbilang($angka - 100);
    } elseif ($angka < 1000) {
        return $bilangan[intval($angka / 100)] . ' Ratus ' . $this->terbilang($angka % 100);
    }
    return (string) $angka;
}




}
