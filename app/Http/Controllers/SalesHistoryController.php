<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;


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
    $id = (int) $id;

    $allowed = $this->allowedBranchIds();

    $sale = DB::table('pos_sales as s')
        ->leftJoin('customers as c', 'c.id', '=', 's.customer_id')
        ->join('branches as b', 'b.id', '=', 's.branch_id')
        ->selectRaw('s.*, b.name as branch_name, c.name as customer_name, c.phone as customer_phone') // s.* sudah include discount
        ->where('s.id', $id)
        ->first();

    if (!$sale || !in_array((int)$sale->branch_id, $allowed, true)) {
        return response()->json(['ok'=>false,'message'=>'Transaksi tidak ditemukan.'], 404);
    }

    $lines = DB::table('pos_sale_lines as l')
        ->join('products as p', 'p.id', '=', 'l.product_id')
        ->selectRaw('p.sku, p.name, l.qty, l.price, l.subtotal')
        ->where('l.pos_sale_id', $id)->get();

    $pays = DB::table('pos_payments')
        ->select('method','amount','ref_no')
        ->where('pos_sale_id', $id)->get();

return response()->json([
    'ok'   => true,
    'sale' => [
        'id'           => $sale->id,
        'datetime'     => $sale->sale_datetime,
        'branch'       => $sale->branch_name,
        'status'       => $sale->status,
        'total'        => (float)$sale->total,
        'discount'     => (float)($sale->discount ?? 0),
        'change_amount'=> (float)($sale->change_amount ?? 0), // TAMBAH INI
        'notes'        => $sale->notes, // TAMBAH INI
        'customer'     => $sale->customer_name,
        'phone'        => $sale->customer_phone,
    ],
    'lines' => $lines,
    'payments' => $pays,
]);

}


/** Cetak invoice + surat jalan (html sederhana, siap print) */
public function invoice($id)
{
    $id = (int)$id;
    $allowed = $this->allowedBranchIds();
   
    $sale = DB::table('pos_sales as s')
        ->leftJoin('customers as c','c.id','=','s.customer_id')
        ->leftJoin('branches as b','b.id','=','s.branch_id')
        ->selectRaw('s.*, c.name as customer_name, c.phone as customer_phone, b.name as branch_name, b.address as branch_address')
        ->where('s.id', $id)->first();
       
    if (!$sale || !in_array((int)$sale->branch_id, $allowed, true)) {
        abort(404);
    }

    // Ambil semua lines dengan informasi UOM
    $lines = DB::table('pos_sale_lines as l')
        ->join('products as p','p.id','=','l.product_id')
        ->join('uoms as u','u.id','=','l.uom_id')
        ->where('l.pos_sale_id', $id)
        ->select('l.*','p.sku','p.name','u.code as uom')
        ->get();

    // AMBIL NAMA SERVICE ASLI DARI PROJECT_SERVICES
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

    // GABUNGKAN MATERIAL DAN LEFTOVER DENGAN PERLAKUAN KHUSUS SERVICE
    $meterUom = $this->ensureUomMeterId();
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

    $calculatedSubtotal = array_sum(array_column($groupedLines, 'total_subtotal'));
    $discount = (float)($sale->discount ?? 0);
    $finalTotal = $calculatedSubtotal - $discount;

    $changeAmount = 0;
    if (!empty($sale->notes) && preg_match('/KEMBALIAN:\s*Rp\s*([\d\.,]+)/i', $sale->notes, $m)) {
        $changeAmount = (float)str_replace(['.', ','], ['', '.'], $m[1]);
    }

    // Format tanggal yang lebih rapi
    $saleDate = date('d/m/Y H:i', strtotime($sale->sale_datetime));
    $todayDate = date('d/m/Y');

    // ✅ FONT SCALING PROFESSIONAL UNTUK DOT MATRIX 21x14cm
    $itemCount = count($groupedLines);
   
    // ✅ BOLD FONTS UNTUK DOT MATRIX - JELAS & TEBAL
$titleFont = 16;     // +4px total - TITLE SANGAT MENONJOL
$headerFont = 14;    // +4px total - HEADER COMPANY SANGAT JELAS  
$labelFont = 12;     // +3px total - LABEL MUDAH DIBACA
$dataFont = 11;      // +3px total - DATA CONTENT OPTIMAL
$tableFont = 11;     // +3px total - TABLE SANGAT READABLE
$smallFont = 10;     // +3px total - SMALL INFO JELAS


   
    // Dynamic scaling berdasarkan items
if ($itemCount > 20) {
    $titleFont = 15;     // +4px dari original 11
    $headerFont = 13;    // +4px dari original 9
    $labelFont = 11;     // +3px dari original 8  
    $dataFont = 10;      // +3px dari original 7
    $tableFont = 10;     // +3px dari original 7
    $smallFont = 9;      // +3px dari original 6
    $paddingSize = 1;
}



    $html = '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8">';
    $html .= '<title>Invoice + Surat Jalan #'.$id.'</title>';
    
    // ✅ CSS PROFESSIONAL ELEGANT UNTUK DOT MATRIX 21x14CM
    $html .= '<style>
        @page {
            size: 21cm 14cm;
            margin: 0;
        }
        
        body {
            font-family: "Courier New", "Consolas", monospace;
            font-size: '.$dataFont.'px;
            line-height: 1.1;
            margin: 0;
            padding: 0;
            color: #000;
            letter-spacing: 0.3px;
            font-weight: 500; /* ✅ SLIGHTLY BOLD BASE */
        }
        
        .page-container {
            width: 20.5cm;
            height: 13.5cm;
            margin: 0;
            padding: 3mm;
            box-sizing: border-box;
            overflow: hidden;
        }
        
        .new-page {
            page-break-before: always !important;
            break-before: page !important;
        }
        
        /* ✅ ELEGANT TYPOGRAPHY HIERARCHY */
        .doc-title {
            font-size: '.$titleFont.'px;
            font-weight: bold;
            text-align: center;
            letter-spacing: 1px;
            margin: 2mm 0;
        }
        
        .company-header {
            font-size: '.$headerFont.'px;
            font-weight: bold;
            text-align: center;
            line-height: 1.2;
            margin-bottom: 3mm;
        }
        
        .section-label {
            font-size: '.$labelFont.'px;
            font-weight: bold;
            margin: 2mm 0 1mm 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .data-text {
            font-size: '.$dataFont.'px;
            font-weight: normal;
            line-height: 1.2;
        }
        
        .emphasis {
            font-weight: bold;
        }
        
        .small-text {
            font-size: '.$smallFont.'px;
            font-weight: normal;
        }
        
        /* ✅ ELEGANT TABLE STYLING - NO HEAVY BORDERS */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1mm 0;
        }
        
        .main-table {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }
        
        .main-table th {
            font-size: '.$tableFont.'px;
            font-weight: bold;
            padding: 2mm 1mm;
            text-align: left;
            border-bottom: 1px solid #000;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .main-table td {
            font-size: '.$tableFont.'px;
            padding: 1.5mm 1mm;
            vertical-align: top;
            border-bottom: 1px dotted #999;
            line-height: 1.1;
        }
        
        .no-border {
            border: none !important;
        }
        
        .no-border td, .no-border th {
            border: none !important;
        }
        
        /* ✅ PROFESSIONAL ALIGNMENT */
        .right { text-align: right; }
        .center { text-align: center; }
        .left { text-align: left; }
        
        /* ✅ ELEGANT SEPARATORS */
        .section-separator {
            height: 1px;
            background: #000;
            margin: 3mm 0;
        }
        
        .dotted-separator {
            border-top: 1px dotted #666;
            margin: 2mm 0;
        }
        
        /* ✅ PROFESSIONAL INFO BLOCKS */
        .info-block {
            margin: 2mm 0;
            padding: 1mm 0;
        }
        
        .total-block {
            border: 2px solid #000;
            padding: 2mm;
            margin: 2mm 0;
            background: none;
        }
        
        .payment-info {
            border: 1px solid #000;
            padding: 2mm;
            text-align: center;
            margin: 2mm 0;
        }
        
        /* ✅ SIGNATURE AREAS - ELEGANT & SPACIOUS */
        .signature-area {
            margin-top: 4mm;
            height: 20mm;
        }
        
        .signature-box {
            height: 15mm;
            margin: 3mm 0;
            border-bottom: 1px solid #000;
            position: relative;
        }
        
        .signature-label {
            font-size: '.$smallFont.'px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 1mm;
        }
        
        .signature-name {
            font-size: '.$smallFont.'px;
            text-align: center;
            margin-top: 1mm;
        }
        
        /* ✅ PRINT OPTIMIZATIONS */
        @media print {
            @page { 
                size: 21cm 14cm;
                margin: 0; 
            }
            
            body { 
                margin: 0; 
                padding: 0;
                -webkit-print-color-adjust: exact;
                font-weight: 500; /* ✅ ENSURE BOLD PRINTING */
            }
            
            .page-container {
                width: 21cm;
                height: 14cm;
                padding: 2mm;
            }
            
            .emphasis, .section-label, .doc-title, .company-header {
                font-weight: bold !important;
            }
            
            .signature-box {
                height: 12mm;
                page-break-inside: avoid;
            }
        }
    </style></head><body>';

    // ========================================
    // ✅ HALAMAN 1: SURAT JALAN ELEGANT
    // ========================================
    
    $html .= '<div class="page-container">';
    
    // ✅ DOCUMENT TITLE ELEGANT
    $html .= '<div class="doc-title">SURAT JALAN</div>';
    $html .= '<div class="center emphasis data-text">No: SJ-'.str_pad($id, 5, '0', STR_PAD_LEFT).'</div>';
    
    // ✅ COMPANY HEADER PROFESSIONAL
    $html .= '<div class="company-header">';
    $html .= '<div>'.strtoupper(e($sale->branch_name ?? 'MAJALENGKA')).'</div>';
    $html .= '<div class="small-text">Sedia: WPC Dinding, Atap UPVC, Kaca Bevel, Hollo</div>';
    $html .= '<div class="small-text">Wall Moulding PVC, Lantai Vinyl, Lantai SPC, dll</div>';
    
    if (!empty($sale->branch_address)) {
        $html .= '<div class="small-text">'.e($sale->branch_address).'</div>';
    }
    
    $html .= '<div class="small-text emphasis">Telp: 0811 2287 2006</div>';
    $html .= '</div>';

    // ✅ SECTION SEPARATOR
    $html .= '<div class="section-separator"></div>';

    // ✅ CUSTOMER & DATE INFO ELEGANT
    $html .= '<table class="no-border info-block">';
    $html .= '<tr>';
    $html .= '<td class="no-border" style="width: 60%; vertical-align: top;">';
    $html .= '<div class="section-label">Kepada Yth:</div>';
    if ($sale->customer_name) {
        $html .= '<div class="data-text emphasis">'.strtoupper(e($sale->customer_name)).'</div>';
        if ($sale->customer_phone) {
            $html .= '<div class="small-text">Telp: '.e($sale->customer_phone).'</div>';
        }
    } else {
        $html .= '<div class="data-text emphasis">PELANGGAN UMUM</div>';
    }
    $html .= '</td>';
    $html .= '<td class="no-border right" style="width: 40%; vertical-align: top;">';
    $html .= '<div class="section-label">Tanggal:</div>';
    $html .= '<div class="data-text emphasis">'.$todayDate.'</div>';
    $html .= '<div class="small-text">Jam: '.date('H:i').'</div>';
    $html .= '</td>';
    $html .= '</tr></table>';

    // ✅ DOTTED SEPARATOR
    $html .= '<div class="dotted-separator"></div>';

    // ✅ ITEMS TABLE ELEGANT - NO HEAVY BORDERS
    $html .= '<table class="main-table">';
    $html .= '<thead><tr>';
    $html .= '<th style="width:8%" class="center">No</th>';
    $html .= '<th style="width:20%">Kode</th>';
    $html .= '<th style="width:47%">Nama Barang</th>';
    $html .= '<th style="width:12%" class="center">Qty</th>';
    $html .= '<th style="width:13%" class="center">Satuan</th>';
    $html .= '</tr></thead><tbody>';

    $no = 1;
    foreach ($groupedLines as $item) {
        $html .= '<tr>';
        $html .= '<td class="center emphasis">'.str_pad($no, 2, '0', STR_PAD_LEFT).'</td>';
        
        // KODE/SKU
        $displaySku = $item['sku'];
        if ($item['sku'] === 'SRV-GEN' && !empty($actualServiceNames)) {
            $displaySku = 'LAYANAN';
        }
        $html .= '<td class="emphasis">'.e($displaySku).'</td>';
        
        // NAMA PRODUK
        $displayName = $item['name'];
        if ($item['sku'] === 'SRV-GEN' && !empty($actualServiceNames)) {
            $displayName = count($actualServiceNames) === 1
                ? $actualServiceNames[0]
                : implode(', ', $actualServiceNames);
        }
        
        $html .= '<td>';
        $html .= '<div class="emphasis">'.e($displayName).'</div>';
        
        // INFO SISA JIKA ADA
        if ($item['qty_sisa'] > 0) {
            $html .= '<div class="small-text">Sisa material: '.number_format($item['qty_sisa'], 2).' meter</div>';
        }
        $html .= '</td>';
        
        // QUANTITY
        if ($item['is_service']) {
            $html .= '<td class="center">1</td>';
            $html .= '<td class="center">Layanan</td>';
        } else if ($item['qty_material'] > 0) {
            $html .= '<td class="center emphasis">'.number_format($item['qty_material'], 0).'</td>';
            $html .= '<td class="center">Pcs</td>';
        } else {
            $html .= '<td class="center">-</td>';
            $html .= '<td class="center">-</td>';
        }
        
        $html .= '</tr>';
        $no++;
    }
    $html .= '</tbody></table>';

    // ✅ SUMMARY ELEGANT
    $html .= '<div class="total-block">';
    $html .= '<div class="center emphasis">Total Barang: '.count($groupedLines).' ('.$this->terbilang(count($groupedLines)).') Jenis</div>';
    $html .= '</div>';

    // ✅ NOTES & SIGNATURES ELEGANT
    $html .= '<table class="no-border signature-area">';
    $html .= '<tr>';
    $html .= '<td class="no-border" style="width: 50%; vertical-align: top;">';
    $html .= '<div class="section-label">Keterangan:</div>';
    if (!empty($sale->notes)) {
        $cleanNotes = preg_replace('/\|\s*KEMBALIAN:.*$/i', '', $sale->notes);
        $cleanNotes = trim($cleanNotes);
        if (!empty($cleanNotes)) {
            $html .= '<div class="small-text">'.nl2br(e($cleanNotes)).'</div>';
        }
    } else {
        $html .= '<div class="small-text">Barang diterima dalam keadaan baik</div>';
    }
    $html .= '</td>';
    
    // SIGNATURES
    $html .= '<td class="no-border" style="width: 25%; vertical-align: top;">';
    $html .= '<div class="signature-label">Yang Menyerahkan</div>';
    $html .= '<div class="signature-box"></div>';
    $html .= '<div class="signature-name">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</div>';
    $html .= '</td>';

    $html .= '<td class="no-border" style="width: 25%; vertical-align: top;">';
    $html .= '<div class="signature-label">Yang Menerima</div>';
    $html .= '<div class="signature-box"></div>';
    $html .= '<div class="signature-name">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</div>';
    $html .= '</td>';
    $html .= '</tr></table>';
    
    $html .= '</div>'; // End page-container

    // ========================================
    // ✅ HALAMAN 2: INVOICE ELEGANT
    // ========================================
    
    $html .= '<div class="page-container new-page">';

    // ✅ DOCUMENT TITLE ELEGANT
    $html .= '<div class="doc-title">FAKTUR PENJUALAN</div>';
    $html .= '<div class="center emphasis data-text">Invoice #'.str_pad($id, 5, '0', STR_PAD_LEFT).'</div>';
    
    // ✅ COMPANY HEADER PROFESSIONAL
    $html .= '<div class="company-header">';
    $html .= '<div>'.strtoupper(e($sale->branch_name ?? 'MAJALENGKA')).'</div>';
    $html .= '<div class="small-text">Sedia: WPC Dinding, Atap UPVC, Kaca Bevel, Hollo</div>';
    $html .= '<div class="small-text">Wall Moulding PVC, Lantai Vinyl, Lantai SPC, dll</div>';
    
    if (!empty($sale->branch_address)) {
        $html .= '<div class="small-text">'.e($sale->branch_address).'</div>';
    }
    
    $html .= '<div class="small-text emphasis">Telp: 0811 2287 2006</div>';
    $html .= '</div>';

    // ✅ SECTION SEPARATOR
    $html .= '<div class="section-separator"></div>';

    // ✅ INVOICE INFO ELEGANT
    $html .= '<table class="no-border info-block">';
    $html .= '<tr>';
    $html .= '<td class="no-border" style="width: 50%; vertical-align: top;">';
    $html .= '<div class="section-label">Tanggal Transaksi:</div>';
    $html .= '<div class="data-text emphasis">'.$saleDate.'</div>';
    $html .= '</td>';
    $html .= '<td class="no-border right" style="width: 50%; vertical-align: top;">';
    
    // CUSTOMER INFO
    $html .= '<div class="section-label">Pelanggan:</div>';
    if ($sale->customer_name) {
        $html .= '<div class="data-text emphasis">'.e($sale->customer_name).'</div>';
        if ($sale->customer_phone) {
            $html .= '<div class="small-text">Telp: '.e($sale->customer_phone).'</div>';
        }
    } else {
        $html .= '<div class="data-text emphasis">Pelanggan Umum</div>';
    }
    
    $html .= '</td>';
    $html .= '</tr></table>';

    // ✅ DOTTED SEPARATOR
    $html .= '<div class="dotted-separator"></div>';

    // ✅ PRODUCTS TABLE ELEGANT
    $html .= '<table class="main-table">';
    $html .= '<thead><tr>';
    $html .= '<th style="width:40%">Nama Produk</th>';
    $html .= '<th class="center" style="width:12%">Qty</th>';
    $html .= '<th class="right" style="width:24%">Harga Satuan</th>';
    $html .= '<th class="right" style="width:24%">Subtotal</th>';
    $html .= '</tr></thead><tbody>';

    foreach ($groupedLines as $item) {
        $html .= '<tr>';
        
        // NAMA PRODUK
        $displayName = $item['name'];
        $displaySku = $item['sku'];
        
        if ($item['sku'] === 'SRV-GEN' && !empty($actualServiceNames)) {
            $displayName = count($actualServiceNames) === 1
                ? $actualServiceNames[0]
                : implode(', ', $actualServiceNames);
            $displaySku = 'LAYANAN';
        }
        
        $html .= '<td>';
        $html .= '<div class="emphasis">'.e($displayName).'</div>';
        $html .= '<div class="small-text">('.e($displaySku).')</div>';
        
        // INFO SISA JIKA ADA
        if ($item['qty_sisa'] > 0) {
            $html .= '<div class="small-text">Sisa: '.number_format($item['qty_sisa'], 2).' meter</div>';
        }
        
        $html .= '</td>';
        
        // QUANTITY
        if ($item['is_service']) {
            $html .= '<td class="center">1 Layanan</td>';
        } else if ($item['qty_material'] > 0) {
            $html .= '<td class="center emphasis">'.number_format($item['qty_material'], 0).'</td>';
        } else {
            $html .= '<td class="center">-</td>';
        }
        
        $html .= '<td class="right">Rp '.number_format($item['display_price'], 0, ',', '.').'</td>';
        $html .= '<td class="right emphasis">Rp '.number_format($item['total_subtotal'], 0, ',', '.').'</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';

    // ✅ TOTAL CALCULATION ELEGANT
    $html .= '<table class="no-border" style="margin-top: 3mm;">';
    $html .= '<tr><td class="no-border" style="width: 60%;"></td>';
    $html .= '<td class="no-border" style="width: 40%;">';
    
    $html .= '<div class="total-block">';
    $html .= '<table class="no-border">';
    $html .= '<tr><td class="no-border">Subtotal:</td>';
    $html .= '<td class="no-border right emphasis">Rp '.number_format($calculatedSubtotal, 0, ',', '.').'</td></tr>';
    
    if ($discount > 0) {
        $html .= '<tr><td class="no-border">Potongan:</td>';
        $html .= '<td class="no-border right">Rp '.number_format($discount, 0, ',', '.').'</td></tr>';
    }
    
    $html .= '<tr style="border-top: 1px solid #000;"><td class="no-border emphasis section-label">TOTAL BAYAR:</td>';
    $html .= '<td class="no-border right emphasis section-label">Rp '.number_format($finalTotal, 0, ',', '.').'</td></tr>';
    
    if ($changeAmount > 0) {
        $html .= '<tr><td class="no-border">Kembalian:</td>';
        $html .= '<td class="no-border right">Rp '.number_format($changeAmount, 0, ',', '.').'</td></tr>';
    }
    
    $html .= '</table>';
    $html .= '</div>';
    $html .= '</td></tr></table>';

    // ✅ PAYMENT INFO ELEGANT
    $html .= '<div class="payment-info">';
    $html .= '<div class="section-label">Informasi Pembayaran</div>';
    $html .= '<div class="data-text">Transfer Bank BCA: <span class="emphasis">4181380637</span></div>';
    $html .= '<div class="small-text">Atas Nama: YADI MULYADI</div>';
    $html .= '</div>';

    // ✅ NOTES & SIGNATURE ELEGANT
    $html .= '<table class="no-border signature-area">';
    $html .= '<tr>';
    $html .= '<td class="no-border" style="width: 60%; vertical-align: top;">';
    
    // CATATAN JIKA ADA
    if (!empty($sale->notes)) {
        $cleanNotes = preg_replace('/\|\s*KEMBALIAN:.*$/i', '', $sale->notes);
        $cleanNotes = trim($cleanNotes);
        
        if (!empty($cleanNotes)) {
            $html .= '<div class="section-label">Catatan:</div>';
            $html .= '<div class="small-text">'.nl2br(e($cleanNotes)).'</div>';
        }
    }
    
    $html .= '</td>';
    
    // SIGNATURE AREA
    $html .= '<td class="no-border" style="width: 40%; vertical-align: top;">';
    $html .= '<div class="signature-label">Hormat Kami,</div>';
    $html .= '<div class="signature-box"></div>';
    $html .= '<div class="signature-name emphasis">Tanda Tangan & Stempel</div>';
    $html .= '</td>';
    $html .= '</tr></table>';
    
    $html .= '</div>'; // End second page-container

    // ✅ PRINT SCRIPT
    $html .= '<script>
        window.addEventListener("load", function() {
            setTimeout(function() {
                window.print();
            }, 800);
        });
    </script>';

    $html .= '</body></html>';

    return response($html);
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
