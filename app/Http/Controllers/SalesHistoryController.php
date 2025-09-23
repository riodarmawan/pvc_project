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

    // ✅ FONT SCALING UNTUK UKURAN 21x14cm (LEBIH KECIL)
    $itemCount = count($groupedLines);
   
    // Font sizes optimal untuk 21x14cm continuous form
    $baseFont = 8;       // ✅ LEBIH KECIL UNTUK 14CM HEIGHT
    $tableFont = 7;      
    $smallFont = 6;      
    $mediumFont = 9;     
    $largeFont = 10;     
    $paddingSize = 1;    // ✅ MINIMAL PADDING
   
    // Scaling berdasarkan jumlah items (LEBIH AGRESIF)
    if ($itemCount > 15) {
        $baseFont = 7;       
        $tableFont = 6;      
        $smallFont = 5;      
        $mediumFont = 8;     
        $largeFont = 9;      
        $paddingSize = 0;
    }
   
    if ($itemCount > 25) {
        $baseFont = 6;       
        $tableFont = 5;      
        $smallFont = 4;      
        $mediumFont = 7;     
        $largeFont = 8;      
        $paddingSize = 0;
    }

    $html = '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8">';
    $html .= '<title>Invoice + Surat Jalan #'.$id.'</title>';
    
    // ✅ CSS UNTUK UKURAN 21CM x 14CM CONTINUOUS FORM
    $html .= '<style>
        @page {
            size: 21cm 14cm; /* ✅ UKURAN 21x14 CM SESUAI PERMINTAAN */
            margin: 0;
        }
        
        body {
            font-family: "Courier New", monospace;
            font-size: '.$baseFont.'px;
            line-height: 0.9; /* ✅ LINE HEIGHT LEBIH RAPAT */
            margin: 0;
            padding: 0;
            color: #000;
            letter-spacing: 0.3px; /* ✅ LETTER SPACING LEBIH RAPAT */
        }
        
        .page-container {
            width: 20.5cm; /* ✅ LEBAR SESUAI KERTAS 21CM */
            height: 13.5cm; /* ✅ TINGGI SESUAI KERTAS 14CM */
            margin: 0;
            padding: 2mm; /* ✅ MINIMAL PADDING */
            box-sizing: border-box;
            overflow: hidden;
        }
        
        .new-page {
            page-break-before: always !important;
            break-before: page !important;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: '.$tableFont.'px;
            page-break-inside: avoid;
            margin-bottom: 1mm; /* ✅ MINIMAL MARGIN */
        }
        
        th, td {
            padding: '.$paddingSize.'px 1px; /* ✅ PADDING MINIMAL */
            text-align: left;
            border-bottom: 1px solid #000;
            vertical-align: top;
            word-break: break-word;
            line-height: 1.0; /* ✅ LINE HEIGHT RAPAT */
        }
        
        th {
            font-weight: bold;
            border-bottom: 1px solid #000; /* ✅ BORDER TIPIS */
            text-transform: uppercase;
            font-size: '.$smallFont.'px; /* ✅ HEADER KECIL */
        }
        
        .right { text-align: right; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .large { font-size: '.$largeFont.'px; font-weight: bold; }
        .medium { font-size: '.$mediumFont.'px; font-weight: bold; }
        .small { font-size: '.$smallFont.'px; }
        .tiny { font-size: '.($smallFont-1).'px; } /* ✅ EXTRA SMALL */
        .header-company { text-align: center; }
        
        .line-separator {
            border-top: 1px solid #000; /* ✅ BORDER TIPIS */
            margin: 1mm 0; /* ✅ MARGIN MINIMAL */
        }
        
        .dotted-line {
            border-top: 1px dotted #000;
            margin: 0.5mm 0; /* ✅ MARGIN MINIMAL */
        }
        
        .dot-matrix-text {
            font-family: "Courier New", monospace;
            letter-spacing: 0.3px;
            font-weight: normal;
        }
        
        .dot-matrix-bold {
            font-family: "Courier New", monospace;
            font-weight: bold;
            letter-spacing: 0.2px;
        }
        
        /* ✅ SIGNATURE AREA - DISESUAIKAN UNTUK 14CM HEIGHT */
        .signature-section {
            min-height: 15mm; /* ✅ LEBIH KECIL DARI SEBELUMNYA */
        }
        
        .signature-box {
            height: 12mm; /* ✅ TINGGI SIGNATURE LEBIH KECIL */
            margin: 5mm 0 2mm 0; /* ✅ MARGIN LEBIH KECIL */
            border-bottom: 1px solid #000;
            position: relative;
        }
        
        .signature-name {
            margin-top: 1mm;
            font-size: '.$smallFont.'px;
        }
        
        /* ✅ COMPACT STYLES UNTUK 21x14 */
        .compact-header {
            margin-bottom: 1mm;
        }
        
        .compact-table {
            margin: 1mm 0;
        }
        
        .compact-row {
            height: auto;
            min-height: 3mm;
        }
        
        @media print {
            @page { 
                size: 21cm 14cm; /* ✅ UKURAN PRINT 21x14 CM */
                margin: 0; 
            }
            
            body { 
                margin: 0; 
                padding: 0;
                -webkit-print-color-adjust: exact;
            }
            
            .page-container {
                width: 21cm;
                height: 14cm;
                padding: 1mm; /* ✅ MINIMAL PADDING PRINT */
                page-break-after: avoid;
            }
            
            .new-page {
                page-break-before: always !important;
            }
            
            table, .signature-section, .total-section {
                page-break-inside: avoid !important;
            }
            
            .signature-box {
                height: 10mm; /* ✅ LEBIH KECIL SAAT PRINT */
                margin: 4mm 0 2mm 0;
            }
        }
    </style></head><body>';

    // ========================================
    // ✅ HALAMAN 1: SURAT JALAN (COMPACT UNTUK 21x14)
    // ========================================
    
    $html .= '<div class="page-container">';
    
    // HEADER SURAT JALAN (COMPACT)
    $html .= '<table style="border: none;" class="compact-header">';
    $html .= '<tr><td style="border: none; padding: 1mm;" class="header-company dot-matrix-bold">';
    $html .= '<div class="medium">SJ-'.str_pad($id, 5, '0', STR_PAD_LEFT).' - '.strtoupper(e($sale->branch_name ?? 'TOKO')).'</div>'; // ✅ GABUNG TITLE
    $html .= '<div class="tiny dot-matrix-text">WPC DINDING, ATAP UPVC, KACA BEVEL, HOLLO, VINYL, SPC | Telp: 0811 2287 2006</div>'; // ✅ SATU BARIS
    $html .= '</td></tr></table>';

    // INFO SURAT JALAN (COMPACT)
    $html .= '<table style="border: none;" class="compact-table">';
    $html .= '<tr>';
    $html .= '<td style="border: none; width: 60%; padding: 1px;" class="dot-matrix-text">';
    $html .= '<div class="small bold">Kepada: ';
    if ($sale->customer_name) {
        $html .= strtoupper(e(substr($sale->customer_name, 0, 25))); // ✅ TRIM NAMA
        if ($sale->customer_phone) {
            $html .= ' | HP: '.e(substr($sale->customer_phone, 0, 13)); // ✅ TRIM HP
        }
    } else {
        $html .= 'PELANGGAN UMUM';
    }
    $html .= '</div>';
    $html .= '</td>';
    $html .= '<td style="border: none; text-align: right; padding: 1px;" class="dot-matrix-text">';
    $html .= '<div class="small bold">'.$todayDate.' | '.date('H:i').'</div>'; // ✅ SATU BARIS
    $html .= '</td>';
    $html .= '</tr></table>';

    // GARIS PEMISAH TIPIS
    $html .= '<div class="dotted-line"></div>';

    // ✅ TABEL BARANG SURAT JALAN (ULTRA COMPACT)
    $html .= '<table class="dot-matrix-text compact-table">';
    $html .= '<thead><tr>';
    $html .= '<th style="width:5%" class="center">NO</th>';
    $html .= '<th style="width:15%">KODE</th>';
    $html .= '<th style="width:55%">NAMA BARANG</th>';
    $html .= '<th style="width:12%" class="center">QTY</th>';
    $html .= '<th style="width:13%" class="center">SATUAN</th>';
    $html .= '</tr></thead><tbody>';

    $no = 1;
    foreach ($groupedLines as $item) {
        $html .= '<tr class="compact-row">';
        $html .= '<td class="center tiny">'.str_pad($no, 2, '0', STR_PAD_LEFT).'</td>';
        
        // KODE/SKU (COMPACT)
        $displaySku = $item['sku'];
        if ($item['sku'] === 'SRV-GEN' && !empty($actualServiceNames)) {
            $displaySku = 'LAYANAN';
        }
        $html .= '<td class="small">'.e(substr($displaySku, 0, 12)).'</td>'; // ✅ TRIM SKU
        
        // NAMA PRODUK (ULTRA COMPACT)
        $displayName = $item['name'];
        if ($item['sku'] === 'SRV-GEN' && !empty($actualServiceNames)) {
            $displayName = count($actualServiceNames) === 1
                ? $actualServiceNames[0]
                : implode(', ', $actualServiceNames);
        }
        
        $html .= '<td>';
        $html .= '<div class="small">'.e(substr($displayName, 0, 35)).'</div>'; // ✅ TRIM NAMA
        
        // INFO SISA JIKA ADA (INLINE)
        if ($item['qty_sisa'] > 0) {
            $html .= '<div class="tiny" style="color: #666;">+Sisa: '.number_format($item['qty_sisa'], 1).'m</div>';
        }
        $html .= '</td>';
        
        // QUANTITY (COMPACT)
        if ($item['is_service']) {
            $html .= '<td class="center tiny">1</td>';
            $html .= '<td class="center tiny">LAYANAN</td>';
        } else if ($item['qty_material'] > 0) {
            $html .= '<td class="center tiny">'.number_format($item['qty_material'], 0).'</td>';
            $html .= '<td class="center tiny">PCS</td>';
        } else {
            $html .= '<td class="center tiny">-</td>';
            $html .= '<td class="center tiny">-</td>';
        }
        
        $html .= '</tr>';
        $no++;
    }
    $html .= '</tbody></table>';

    // RINGKASAN SURAT JALAN (INLINE)
    $html .= '<table style="border: 1px solid #000; margin: 1mm 0;" class="dot-matrix-text compact-table">';
    $html .= '<tr><td style="border: none; padding: 1mm;" class="center">';
    $html .= '<div class="small bold">TOTAL: '.count($groupedLines).' ('.$this->terbilang(count($groupedLines)).') Jenis</div>'; // ✅ SINGKAT
    $html .= '</td></tr></table>';

    // ✅ SIGNATURE COMPACT UNTUK 21x14
    $html .= '<table style="border: none; margin-top: 1mm;" class="dot-matrix-text">';
    $html .= '<tr>';
    $html .= '<td style="border: none; width: 40%; padding: 1px; vertical-align: top;">';
    $html .= '<div class="tiny bold">Keterangan:</div>';
    if (!empty($sale->notes)) {
        $cleanNotes = preg_replace('/\|\s*KEMBALIAN:.*$/i', '', $sale->notes);
        $cleanNotes = trim($cleanNotes);
        if (!empty($cleanNotes)) {
            $html .= '<div class="tiny">'.e(substr($cleanNotes, 0, 40)).'</div>'; // ✅ TRIM NOTES
        }
    } else {
        $html .= '<div class="tiny">Barang diterima baik</div>';
    }
    $html .= '</td>';
    
    // SIGNATURES (COMPACT)
    $html .= '<td style="border: none; width: 30%; text-align: center; padding: 1px;" class="signature-section">';
    $html .= '<div class="tiny bold">PENYERAH</div>';
    $html .= '<div class="signature-box"></div>';
    $html .= '<div class="signature-name tiny">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</div>';
    $html .= '</td>';

    $html .= '<td style="border: none; width: 30%; text-align: center; padding: 1px;" class="signature-section">';
    $html .= '<div class="tiny bold">PENERIMA</div>';
    $html .= '<div class="signature-box"></div>';
    $html .= '<div class="signature-name tiny">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</div>';
    $html .= '</td>';
    $html .= '</tr></table>';
    
    $html .= '</div>'; // End page-container

    // ========================================
    // ✅ HALAMAN 2: INVOICE (COMPACT UNTUK 21x14)
    // ========================================
    
    $html .= '<div class="page-container new-page">';

    // HEADER INVOICE (COMPACT)
    $html .= '<table style="border: none;" class="compact-header">';
    $html .= '<tr><td style="border: none; padding: 1mm;" class="header-company dot-matrix-bold">';
    $html .= '<div class="medium">INVOICE #'.str_pad($id, 5, '0', STR_PAD_LEFT).' - '.strtoupper(e($sale->branch_name ?? 'TOKO')).'</div>'; // ✅ GABUNG
    $html .= '<div class="tiny dot-matrix-text">WPC DINDING, ATAP UPVC, KACA BEVEL, HOLLO, VINYL, SPC | Telp: 0811 2287 2006</div>';
    $html .= '</td></tr></table>';

    // INFO INVOICE (COMPACT)
    $html .= '<table style="border: none;" class="compact-table">';
    $html .= '<tr>';
    $html .= '<td style="border: none; width: 50%; padding: 1px;" class="dot-matrix-text">';
    $html .= '<div class="small bold">Tanggal: '.date('d/m/Y H:i', strtotime($sale->sale_datetime)).'</div>';
    $html .= '</td>';
    $html .= '<td style="border: none; text-align: right; padding: 1px;" class="dot-matrix-text">';
    
    // INFO PELANGGAN (COMPACT)
    if ($sale->customer_name) {
        $html .= '<div class="small bold">'.e(substr($sale->customer_name, 0, 20)).'</div>'; // ✅ TRIM NAMA
        if ($sale->customer_phone) {
            $html .= '<div class="tiny">'.e(substr($sale->customer_phone, 0, 13)).'</div>'; // ✅ TRIM HP
        }
    } else {
        $html .= '<div class="small bold">UMUM</div>';
    }
    
    $html .= '</td>';
    $html .= '</tr></table>';

    // GARIS PEMISAH TIPIS
    $html .= '<div class="dotted-line"></div>';

    // ✅ TABEL PRODUK INVOICE (ULTRA COMPACT)
    $html .= '<table class="dot-matrix-text compact-table">';
    $html .= '<thead><tr>';
    $html .= '<th style="width:40%">PRODUK</th>';
    $html .= '<th class="center" style="width:12%">QTY</th>';
    $html .= '<th class="right" style="width:24%">HARGA</th>';
    $html .= '<th class="right" style="width:24%">SUBTOTAL</th>';
    $html .= '</tr></thead><tbody>';

    foreach ($groupedLines as $item) {
        $html .= '<tr class="compact-row">';
        
        // NAMA PRODUK (ULTRA COMPACT)
        $displayName = $item['name'];
        $displaySku = $item['sku'];
        
        if ($item['sku'] === 'SRV-GEN' && !empty($actualServiceNames)) {
            $displayName = count($actualServiceNames) === 1
                ? $actualServiceNames[0]
                : implode(', ', $actualServiceNames);
            $displaySku = 'LAYANAN';
        }
        
        $html .= '<td>';
        $html .= '<div class="small">'.e(substr($displayName, 0, 25)).'</div>'; // ✅ TRIM NAMA
        $html .= '<div class="tiny">('.e(substr($displaySku, 0, 8)).')</div>'; // ✅ TRIM SKU
        
        // INFO SISA JIKA ADA (INLINE)
        if ($item['qty_sisa'] > 0) {
            $html .= '<div class="tiny" style="color: #666;">+'.number_format($item['qty_sisa'], 1).'m</div>';
        }
        
        $html .= '</td>';
        
        // QUANTITY (COMPACT)
        if ($item['is_service']) {
            $html .= '<td class="center tiny">1 SRV</td>';
        } else if ($item['qty_material'] > 0) {
            $html .= '<td class="center tiny">'.number_format($item['qty_material'], 0).'</td>';
        } else {
            $html .= '<td class="center tiny">-</td>';
        }
        
        $html .= '<td class="right tiny">'.number_format($item['display_price'], 0, ',', '.').'</td>';
        $html .= '<td class="right small bold">'.number_format($item['total_subtotal'], 0, ',', '.').'</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';

    // ✅ RINGKASAN TOTAL INVOICE (COMPACT)
    $html .= '<table style="border: none; margin: 1mm 0;" class="total-section compact-table">';
    $html .= '<tr><td style="border: none; width: 40%;"></td>';
    $html .= '<td style="border: none; text-align: right; padding: 1px;">';
    
    $html .= '<table style="border: 1px solid #000;" class="dot-matrix-text">';
    $html .= '<tr><td style="border-bottom: 1px solid #000; padding: 1px;" class="tiny">Subtotal:</td>';
    $html .= '<td class="right small bold" style="border-bottom: 1px solid #000; padding: 1px;">Rp '.number_format($calculatedSubtotal, 0, ',', '.').'</td></tr>';
    
    if ($discount > 0) {
        $html .= '<tr><td style="border-bottom: 1px solid #000; padding: 1px;" class="tiny">Diskon:</td>';
        $html .= '<td class="right tiny" style="border-bottom: 1px solid #000; padding: 1px;">-Rp '.number_format($discount, 0, ',', '.').'</td></tr>';
    }
    
    $html .= '<tr><td class="small bold" style="border-bottom: none; padding: 1px;">TOTAL:</td>';
    $html .= '<td class="right small bold" style="border-bottom: none; padding: 1px;">Rp '.number_format($finalTotal, 0, ',', '.').'</td></tr>';
    
    if ($changeAmount > 0) {
        $html .= '<tr><td style="border-bottom: none; padding: 1px;" class="tiny">Kembalian:</td>';
        $html .= '<td class="right tiny" style="border-bottom: none; padding: 1px;">Rp '.number_format($changeAmount, 0, ',', '.').'</td></tr>';
    }
    
    $html .= '</table>';
    $html .= '</td></tr></table>';

    // INFO PEMBAYARAN (COMPACT)
    $html .= '<table style="border: 1px solid #000; margin: 1mm 0;" class="dot-matrix-text compact-table">';
    $html .= '<tr><td style="border: none; padding: 1mm; text-align: center;">';
    $html .= '<div class="small bold">TRANSFER BCA: 4181380637 (YADI MULYADI)</div>'; // ✅ SATU BARIS
    $html .= '</td></tr></table>';

    // ✅ FOOTER INVOICE (SIGNATURE COMPACT)
    $html .= '<table style="border: none; margin-top: 1mm;" class="dot-matrix-text signature-section">';
    $html .= '<tr>';
    $html .= '<td style="border: none; width: 50%; padding: 1px; vertical-align: top;">';
    
    // CATATAN JIKA ADA (COMPACT)
    if (!empty($sale->notes)) {
        $cleanNotes = preg_replace('/\|\s*KEMBALIAN:.*$/i', '', $sale->notes);
        $cleanNotes = trim($cleanNotes);
        
        if (!empty($cleanNotes)) {
            $html .= '<div class="tiny bold">Catatan:</div>';
            $html .= '<div class="tiny">'.e(substr($cleanNotes, 0, 35)).'</div>'; // ✅ TRIM NOTES
        }
    }
    
    $html .= '</td>';
    
    // SIGNATURE AREA (COMPACT)
    $html .= '<td style="border: none; width: 50%; text-align: center; padding: 1px; vertical-align: top;">';
    $html .= '<div class="tiny">Hormat kami,</div>';
    $html .= '<div class="signature-box"></div>';
    $html .= '<div class="signature-name tiny bold">Tanda Tangan & Stempel</div>';
    $html .= '</td>';
    $html .= '</tr></table>';
    
    $html .= '</div>'; // End second page-container

    // ✅ PRINT SCRIPT
    $html .= '<script>
        window.addEventListener("load", function() {
            setTimeout(function() {
                window.print();
            }, 500); // ✅ LEBIH CEPAT
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
