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

    // ✅ DYNAMIC SCALING UNTUK CONTINUOUS FORM
    $itemCount = count($groupedLines);
   
    // Font sizes optimal untuk continuous form LX-310
    $baseFont = 10;      
    $tableFont = 9;      
    $smallFont = 8;      
    $mediumFont = 11;    
    $largeFont = 12;     
    $paddingSize = 2;    
    $marginSize = '3mm'; 
   
    // Scaling berdasarkan jumlah items
    if ($itemCount > 20) {
        $baseFont = 9;       
        $tableFont = 8;      
        $smallFont = 7;      
        $mediumFont = 10;    
        $largeFont = 11;     
        $paddingSize = 1;
        $marginSize = '2mm';
    }
   
    if ($itemCount > 35) {
        $baseFont = 8;       
        $tableFont = 7;      
        $smallFont = 6;      
        $mediumFont = 9;     
        $largeFont = 10;     
        $paddingSize = 1;
        $marginSize = '1mm';
    }

    // ✅ HTML OPTIMIZED UNTUK EPSON LX-310 CONTINUOUS FORM
    $html = '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8">';
    $html .= '<title>Invoice + Surat Jalan #'.$id.'</title>';
    
    // ✅ CSS DIPERBAHARUI UNTUK CONTINUOUS FORM
$html .= '<style>
        @page {
            size: 9.5in auto; /* ✅ AUTO HEIGHT FOR CONTINUOUS */
            margin: 0;
        }
        
        body {
            font-family: "Courier New", monospace;
            font-size: '.$baseFont.'px;
            line-height: 1.0;
            margin: 0;
            padding: 2mm;
            width: 9.1in;
            color: #000;
            letter-spacing: 0.5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: '.$tableFont.'px;
        }
        
        th, td {
            padding: '.$paddingSize.'px 2px;
            text-align: left;
            border-bottom: 1px solid #000;
            vertical-align: top;
            word-break: break-word;
        }
        
        th {
            font-weight: bold;
            border-bottom: 2px solid #000;
            text-transform: uppercase;
        }
        
        .right { text-align: right; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .large { font-size: '.$largeFont.'px; font-weight: bold; }
        .medium { font-size: '.$mediumFont.'px; font-weight: bold; }
        .small { font-size: '.$smallFont.'px; }
        .header-company { text-align: center; }
        
        .line-separator {
            border-top: 2px solid #000;
            margin: '.$paddingSize.'px 0;
        }
        
        .dotted-line {
            border-top: 1px dotted #000;
            margin: '.$paddingSize.'px 0;
        }
        
        /* ✅ SIMPLE DOCUMENT SEPARATOR */
        .document-separator {
            height: 10mm;
            margin: 3mm 0;
            text-align: center;
            border-bottom: 1px dotted #999;
            position: relative;
        }
        
        .document-separator::after {
            content: "• • • • • • • • • • • • • • • • • • • • • • • • •";
            position: absolute;
            bottom: 2mm;
            left: 50%;
            transform: translateX(-50%);
            font-size: 8px;
            color: #999;
        }
        
        /* ✅ FORCE NO PAGE BREAKS */
        * {
            page-break-before: avoid !important;
            page-break-after: avoid !important;
            page-break-inside: avoid !important;
            break-before: avoid !important;
            break-after: avoid !important;
            break-inside: avoid !important;
        }
        
        @media print {
            @page { 
                size: 9.5in auto; 
                margin: 0; 
            }
            
            body { 
                margin: 0; 
                padding: 1mm;
                -webkit-print-color-adjust: exact;
            }
            
            .document-separator {
                height: 8mm;
                page-break-before: avoid !important;
                page-break-after: avoid !important;
            }
        }
    </style></head><body>';

    // ========================================
    // ✅ DOKUMEN 1: SURAT JALAN
    // ========================================
   
    // HEADER SURAT JALAN
    $html .= '<table style="border: none; margin-bottom: '.($paddingSize * 2).'px;">';
    $html .= '<tr><td style="border: none; padding: '.$paddingSize.'px;" class="header-company dot-matrix-bold">';
    $html .= '<div class="large">SURAT JALAN NO: SJ-'.str_pad($id, 5, '0', STR_PAD_LEFT).'</div>';
    $html .= '<div class="medium">'.strtoupper(e($sale->branch_name ?? 'TOKO')).'</div>';
    $html .= '<div class="small dot-matrix-text" style="margin: 1px 0;">SEDIA: WPC DINDING, ATAP UPVC, KACA BEVEL, HOLLO</div>';
    $html .= '<div class="small dot-matrix-text">WALL MOULDING PVC, LANTAI VINYL, LANTAI SPC, DLL</div>';
   
    if (!empty($sale->branch_address)) {
        $html .= '<div class="small dot-matrix-text" style="margin: 1px 0;">'.e($sale->branch_address).'</div>';
    }
   
    $html .= '<div class="small dot-matrix-text">Telp: 0811 2287 2006</div>';
    $html .= '</td></tr></table>';

    // GARIS PEMISAH
    $html .= '<div class="line-separator"></div>';

    // INFO SURAT JALAN
    $html .= '<table style="border: none; margin-bottom: '.($paddingSize * 2).'px;">';
    $html .= '<tr>';
    $html .= '<td style="border: none; width: 50%; padding: 1px;" class="dot-matrix-text">';
    $html .= '<div class="bold">Kepada Yth:</div>';
    if ($sale->customer_name) {
        $html .= '<div class="medium">'.strtoupper(e($sale->customer_name)).'</div>';
        if ($sale->customer_phone) {
            $html .= '<div class="small">HP: '.e($sale->customer_phone).'</div>';
        }
    } else {
        $html .= '<div class="medium">PELANGGAN UMUM</div>';
    }
    $html .= '</td>';
    $html .= '<td style="border: none; text-align: right; padding: 1px;" class="dot-matrix-text">';
    $html .= '<div class="bold">Tanggal: '.$todayDate.'</div>';
    $html .= '<div class="small">Jam: '.date('H:i').'</div>';
    $html .= '</td>';
    $html .= '</tr></table>';

    // GARIS PEMISAH
    $html .= '<div class="dotted-line"></div>';

    // ✅ TABEL BARANG SURAT JALAN (TANPA HARGA)
    $html .= '<table class="dot-matrix-text">';
    $html .= '<thead><tr>';
    $html .= '<th style="width:8%" class="center">NO</th>';
    $html .= '<th style="width:20%">KODE</th>';
    $html .= '<th style="width:52%">NAMA BARANG</th>';
    $html .= '<th style="width:12%" class="center">QTY</th>';
    $html .= '<th style="width:8%" class="center">SATUAN</th>';
    $html .= '</tr></thead><tbody>';

    $no = 1;
    foreach ($groupedLines as $item) {
        $html .= '<tr>';
        $html .= '<td class="center">'.str_pad($no, 2, '0', STR_PAD_LEFT).'</td>';
       
        // KODE/SKU
        $displaySku = $item['sku'];
        if ($item['sku'] === 'SRV-GEN' && !empty($actualServiceNames)) {
            $displaySku = 'LAYANAN';
        }
        $html .= '<td class="bold">'.e($displaySku).'</td>';
       
        // NAMA PRODUK
        $displayName = $item['name'];
        if ($item['sku'] === 'SRV-GEN' && !empty($actualServiceNames)) {
            $displayName = count($actualServiceNames) === 1
                ? $actualServiceNames[0]
                : implode(', ', $actualServiceNames);
        }
       
        $html .= '<td>';
        $html .= '<div class="bold">'.e($displayName).'</div>';
       
        // INFO SISA JIKA ADA
        if ($item['qty_sisa'] > 0) {
            $html .= '<div class="small" style="color: #666;">+ Sisa: '.number_format($item['qty_sisa'], 2).'m</div>';
        }
        $html .= '</td>';
       
        // QUANTITY
        if ($item['is_service']) {
            $html .= '<td class="center">1</td>';
            $html .= '<td class="center">LAYANAN</td>';
        } else if ($item['qty_material'] > 0) {
            $html .= '<td class="center">'.number_format($item['qty_material'], 0).'</td>';
            $html .= '<td class="center">PCS</td>';
        } else {
            $html .= '<td class="center">-</td>';
            $html .= '<td class="center">-</td>';
        }
       
        $html .= '</tr>';
        $no++;
    }
    $html .= '</tbody></table>';

    // RINGKASAN SURAT JALAN
    $html .= '<table style="border: 1px solid #000; margin-top: '.($paddingSize * 3).'px;" class="dot-matrix-text">';
    $html .= '<tr><td style="border: none; padding: '.($paddingSize * 2).'px;">';
    $html .= '<div class="bold center">BANYAKNYA</div>';
    $html .= '<div class="center">'.count($groupedLines).' ('. $this->terbilang(count($groupedLines)) .') Jenis Barang</div>';
    $html .= '</td></tr></table>';

    // KETERANGAN DAN TANDA TANGAN SURAT JALAN
    $html .= '<table style="border: none; margin-top: '.($paddingSize * 4).'px;" class="dot-matrix-text">';
    $html .= '<tr>';
    $html .= '<td style="border: none; width: 50%; padding: 1px; vertical-align: top;">';
    $html .= '<div class="bold">KETERANGAN:</div>';
    if (!empty($sale->notes)) {
        $cleanNotes = preg_replace('/\|\s*KEMBALIAN:.*$/i', '', $sale->notes);
        $cleanNotes = trim($cleanNotes);
        if (!empty($cleanNotes)) {
            $html .= '<div class="small">'.nl2br(e($cleanNotes)).'</div>';
        }
    } else {
        $html .= '<div class="small">Barang diterima dalam keadaan baik</div>';
    }
    $html .= '</td>';
    $html .= '<td style="border: none; width: 25%; text-align: center; padding: 1px;">';
    $html .= '<div class="bold">YANG MENYERAHKAN</div>';
    $html .= '<div style="margin: '.($paddingSize * 8).'px 0 '.$paddingSize.'px 0;">________________</div>';
    $html .= '<div class="small">( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</div>';
    $html .= '</td>';
    $html .= '<td style="border: none; width: 25%; text-align: center; padding: 1px;">';
    $html .= '<div class="bold">YANG MENERIMA</div>';
    $html .= '<div style="margin: '.($paddingSize * 8).'px 0 '.$paddingSize.'px 0;">________________</div>';
    $html .= '<div class="small">( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</div>';
    $html .= '</td>';
    $html .= '</tr></table>';

    // ========================================
    // ✅ SEPARATOR - GANTI PAGE BREAK
    // ========================================
   
    $html .= '<div class="page-separator">
        <div style="text-align: center; margin-top: 15mm; font-size: 10px; color: #999; letter-spacing: 2px;">
            ═══════════════════════════════════════════════════════════════
        </div>
    </div>';

    // ========================================
    // ✅ DOKUMEN 2: INVOICE
    // ========================================

    // HEADER INVOICE
    $html .= '<table style="border: none; margin-bottom: '.($paddingSize * 2).'px;">';
    $html .= '<tr><td style="border: none; padding: '.$paddingSize.'px;" class="header-company dot-matrix-bold">';
    $html .= '<div class="large">'.strtoupper(e($sale->branch_name ?? 'TOKO')).'</div>';
    $html .= '<div class="small dot-matrix-text" style="margin: 1px 0;">SEDIA: WPC DINDING, ATAP UPVC, KACA BEVEL, HOLLO</div>';
    $html .= '<div class="small dot-matrix-text">WALL MOULDING PVC, LANTAI VINYL, LANTAI SPC, DLL</div>';
   
    if (!empty($sale->branch_address)) {
        $html .= '<div class="small dot-matrix-text" style="margin: 1px 0;">'.e($sale->branch_address).'</div>';
    }
   
    $html .= '<div class="small dot-matrix-text">Telp: 0811 2287 2006</div>';
    $html .= '</td></tr></table>';

    // GARIS PEMISAH
    $html .= '<div class="line-separator"></div>';

    // INFO INVOICE
    $html .= '<table style="border: none; margin-bottom: '.($paddingSize * 2).'px;">';
    $html .= '<tr>';
    $html .= '<td style="border: none; width: 60%; padding: 1px;" class="dot-matrix-bold">';
    $html .= '<div class="medium">INVOICE #'.str_pad($id, 5, '0', STR_PAD_LEFT).'</div>';
    $html .= '<div class="dot-matrix-text">Tanggal: '.$saleDate.'</div>';
    $html .= '</td>';
    $html .= '<td style="border: none; text-align: right; padding: 1px;" class="dot-matrix-text">';
   
    // INFO PELANGGAN
    if ($sale->customer_name) {
        $html .= '<div class="bold">Pelanggan:</div>';
        $html .= '<div>'.e($sale->customer_name).'</div>';
        if ($sale->customer_phone) {
            $html .= '<div class="small">HP: '.e($sale->customer_phone).'</div>';
        }
    } else {
        $html .= '<div class="bold">Pelanggan:</div>';
        $html .= '<div>Umum</div>';
    }
   
    $html .= '</td>';
    $html .= '</tr></table>';

    // GARIS PEMISAH
    $html .= '<div class="dotted-line"></div>';

    // ✅ TABEL PRODUK INVOICE (DENGAN HARGA)
    $html .= '<table class="dot-matrix-text">';
    $html .= '<thead><tr>';
    $html .= '<th style="width:50%">NAMA PRODUK</th>';
    $html .= '<th class="center" style="width:15%">QTY</th>';
    $html .= '<th class="right" style="width:17%">HARGA</th>';
    $html .= '<th class="right" style="width:18%">SUBTOTAL</th>';
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
        $html .= '<div class="bold">'.e($displayName).'</div>';
        $html .= '<div class="small">('.e($displaySku).')</div>';
       
        // INFO SISA JIKA ADA
        if ($item['qty_sisa'] > 0) {
            $html .= '<div class="small" style="color: #666;">+ Sisa: '.number_format($item['qty_sisa'], 2).'m</div>';
        }
       
        $html .= '</td>';
       
        // QUANTITY
        if ($item['is_service']) {
            $html .= '<td class="center">1 LAYANAN</td>';
        } else if ($item['qty_material'] > 0) {
            $html .= '<td class="center">'.number_format($item['qty_material'], 0).'</td>';
        } else {
            $html .= '<td class="center">-</td>';
        }
       
        $html .= '<td class="right">'.number_format($item['display_price'], 0, ',', '.').'</td>';
        $html .= '<td class="right bold">'.number_format($item['total_subtotal'], 0, ',', '.').'</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';

    // ✅ RINGKASAN TOTAL INVOICE
    $html .= '<table style="border: none; margin-top: '.($paddingSize * 3).'px;">';
    $html .= '<tr><td style="border: none; width: 55%;"></td>';
    $html .= '<td style="border: none; text-align: right; padding: 1px;">';
   
    $html .= '<table style="border: 1px solid #000;" class="dot-matrix-text">';
    $html .= '<tr><td style="border-bottom: 1px solid #000; padding: '.$paddingSize.'px;">Subtotal:</td>';
    $html .= '<td class="right bold" style="border-bottom: 1px solid #000; padding: '.$paddingSize.'px;">Rp '.number_format($calculatedSubtotal, 0, ',', '.').'</td></tr>';
   
    if ($discount > 0) {
        $html .= '<tr><td style="border-bottom: 1px solid #000; padding: '.$paddingSize.'px;">Diskon:</td>';
        $html .= '<td class="right" style="border-bottom: 1px solid #000; padding: '.$paddingSize.'px;">-Rp '.number_format($discount, 0, ',', '.').'</td></tr>';
    }
   
    $html .= '<tr><td class="bold medium" style="border-bottom: none; padding: '.$paddingSize.'px;">TOTAL:</td>';
    $html .= '<td class="right bold medium" style="border-bottom: none; padding: '.$paddingSize.'px;">Rp '.number_format($finalTotal, 0, ',', '.').'</td></tr>';
   
    if ($changeAmount > 0) {
        $html .= '<tr><td style="border-bottom: none; padding: '.$paddingSize.'px;">Kembalian:</td>';
        $html .= '<td class="right" style="border-bottom: none; padding: '.$paddingSize.'px;">Rp '.number_format($changeAmount, 0, ',', '.').'</td></tr>';
    }
   
    $html .= '</table>';
    $html .= '</td></tr></table>';

    // GARIS PEMISAH
    $html .= '<div class="line-separator"></div>';

    // ✅ INFO PEMBAYARAN INVOICE
    $html .= '<table style="border: 2px solid #000; margin: '.($paddingSize * 3).'px 0;" class="dot-matrix-bold">';
    $html .= '<tr><td style="border: none; padding: '.($paddingSize * 2).'px; text-align: center;">';
    $html .= '<div class="medium">INFORMASI PEMBAYARAN</div>';
    $html .= '<div class="dot-matrix-text" style="margin: '.$paddingSize.'px 0;">Transfer ke Rekening BCA:</div>';
    $html .= '<div class="large">4181380637</div>';
    $html .= '<div class="small dot-matrix-text">A/N YADI MULYADI</div>';
    $html .= '</td></tr></table>';

    // ✅ FOOTER INVOICE
    $html .= '<table style="border: none; margin-top: '.($paddingSize * 4).'px;" class="dot-matrix-text">';
    $html .= '<tr>';
    $html .= '<td style="border: none; width: 50%; padding: 1px;">';
   
    // CATATAN JIKA ADA
    if (!empty($sale->notes)) {
        $cleanNotes = preg_replace('/\|\s*KEMBALIAN:.*$/i', '', $sale->notes);
        $cleanNotes = trim($cleanNotes);
       
        if (!empty($cleanNotes)) {
            $html .= '<div class="bold">Catatan:</div>';
            $html .= '<div class="small">'.nl2br(e($cleanNotes)).'</div>';
        }
    }
   
    $html .= '</td>';
    $html .= '<td style="border: none; text-align: center; padding: 1px;">';
    $html .= '<div>Hormat kami,</div>';
    $html .= '<div style="margin: '.($paddingSize * 6).'px 0 '.$paddingSize.'px 0;">_________________</div>';
    $html .= '<div class="small">Tanda Tangan & Stempel</div>';
    $html .= '</td>';
    $html .= '</tr></table>';

    // ✅ JAVASCRIPT OPTIMIZED UNTUK CONTINUOUS FORM
    $html .= '<script>
        // ✅ WAIT FOR COMPLETE LOAD
        document.addEventListener("DOMContentLoaded", function() {
            // ✅ SMALL DELAY FOR RENDERING
            setTimeout(function() {
                window.print();
            }, 500);
        });

        // ✅ PREVENT BROWSER PAGE BREAK HANDLING
        window.addEventListener("beforeprint", function(event) {
            document.body.style.margin = "0";
            document.body.style.padding = "2mm";
            
            // ✅ FORCE CONTINUOUS LAYOUT
            const tables = document.querySelectorAll("table");
            tables.forEach(function(table) {
                table.style.pageBreakInside = "avoid";
            });
        });
        
        // ✅ CLEANUP AFTER PRINT
        window.addEventListener("afterprint", function(event) {
            // Optional: redirect or close
            // window.close();
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
