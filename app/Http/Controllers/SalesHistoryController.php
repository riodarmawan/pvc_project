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
public function invoice(Request $request, $id)
{
    // [LOGIC PENGOLAHAN DATA SAMA - TIDAK BERUBAH]
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

    $saleDate = date('d/m/Y H:i', strtotime($sale->sale_datetime));
    $todayDate = date('d/m/Y');

// ✅ SESUDAH - Dengan kontrol query parameter
$itemCount = count($groupedLines);

// ✅ AMBIL FONT SIZE DARI QUERY PARAMETER (default = 0)
$fontAdjustment = (int) ($request->query('font', 0));

// ✅ BASE FONT SIZES
$titleFont = 14 + $fontAdjustment;
$headerFont = 12 + $fontAdjustment;
$labelFont = 10 + $fontAdjustment;
$dataFont = 9 + $fontAdjustment;
$tableFont = 9 + $fontAdjustment;
$smallFont = 8 + $fontAdjustment;

// ✅ DYNAMIC SCALING BERDASARKAN JUMLAH ITEM (dengan adjustment)
if ($itemCount > 15) {
    $titleFont = 12 + $fontAdjustment;
    $headerFont = 10 + $fontAdjustment;
    $labelFont = 9 + $fontAdjustment;
    $dataFont = 8 + $fontAdjustment;
    $tableFont = 8 + $fontAdjustment;
    $smallFont = 7 + $fontAdjustment;
}

if ($itemCount > 25) {
    $titleFont = 11 + $fontAdjustment;
    $headerFont = 9 + $fontAdjustment;
    $labelFont = 8 + $fontAdjustment;
    $dataFont = 7 + $fontAdjustment;
    $tableFont = 7 + $fontAdjustment;
    $smallFont = 6 + $fontAdjustment;
}

// ✅ VALIDASI: Minimal font size 6px
$titleFont = max(6, $titleFont);
$headerFont = max(6, $headerFont);
$labelFont = max(6, $labelFont);
$dataFont = max(6, $dataFont);
$tableFont = max(6, $tableFont);
$smallFont = max(6, $smallFont);


    $html = '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8">';
    $html .= '<title>Invoice + Surat Jalan #'.$id.'</title>';
    
    $html .= '<style>
        @page {
            size: 21cm 14cm;
            margin: 5mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Courier New", "Consolas", monospace;
            font-size: '.$dataFont.'px;
            font-weight: bold;
            line-height: 1.0;
            color: #000;
            letter-spacing: 0.1px;
        }
        
        .page-container {
            width: 20cm;
            height: 13cm;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        
        .new-page {
            page-break-before: always !important;
        }
        
        @media print {
            @page { 
                size: 21cm 14cm;
                margin: 5mm;
            }
            
            body { 
                margin: 0; 
                padding: 0;
                -webkit-print-color-adjust: exact;
                font-weight: bold !important;
            }
            
            .page-container {
                width: 20cm;
                height: 13cm;
                padding: 0;
            }
        }
    </style></head><body>';

    // ========================================
    // ✅ HALAMAN 1: SURAT JALAN
    // ========================================
    
    $html .= '<div class="page-container">';
    
    $html .= '<div style="font-size: '.$titleFont.'px; font-weight: bold; text-align: center; margin-bottom: 1mm;">SURAT JALAN</div>';
    $html .= '<div style="text-align: center; font-weight: bold; font-size: '.$dataFont.'px; margin-bottom: 1mm;">No: SJ-'.str_pad($id, 5, '0', STR_PAD_LEFT).'</div>';
    
    $html .= '<div style="font-size: '.$headerFont.'px; font-weight: bold; text-align: center; margin-bottom: 2mm;">';
    $html .= '<div>'.strtoupper(e($sale->branch_name ?? 'MAJALENGKA')).'</div>';
    $html .= '<div style="font-size: '.$smallFont.'px;">Sedia: WPC Dinding, Atap UPVC, Kaca Bevel, Hollo, Wall Moulding PVC, Lantai Vinyl, Lantai SPC, dll</div>';
    
    if (!empty($sale->branch_address)) {
        $html .= '<div style="font-size: '.$smallFont.'px;">'.e($sale->branch_address).'</div>';
    }
    
    $html .= '<div style="font-size: '.$smallFont.'px; font-weight: bold;">Telp: 0811 2287 2006</div>';
    $html .= '</div>';

    $html .= '<div style="border-top: 2px solid #000; margin: 2mm 0;"></div>';

    $html .= '<table style="width: 100%; border-collapse: collapse; font-size: '.$labelFont.'px; font-weight: bold; margin-bottom: 1mm;">';
    $html .= '<tr>';
    $html .= '<td style="width: 50%; border: none;">KEPADA: ';
    if ($sale->customer_name) {
        $html .= strtoupper(e($sale->customer_name));
        if ($sale->customer_phone) {
            $html .= ' (Telp: '.e($sale->customer_phone).')';
        }
    } else {
        $html .= 'PELANGGAN UMUM';
    }
    $html .= '</td>';
    $html .= '<td style="width: 50%; text-align: right; border: none;">TANGGAL: '.$todayDate.' | '.date('H:i').'</td>';
    $html .= '</tr></table>';

    // ✅ TABEL SURAT JALAN - WIDTH 100% FIT DENGAN BORDER
    $html .= '<table style="width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid #000; font-size: '.$tableFont.'px; font-weight: bold;">';
    
    $html .= '<thead><tr style="border-bottom: 1px solid #000;">';
    $html .= '<th style="width: 6%; text-align: center; padding: 1mm 0.5mm; border-right: 1px solid #000; background: #f0f0f0;">NO</th>';
    $html .= '<th style="width: 13%; padding: 1mm 0.5mm; border-right: 1px solid #000; background: #f0f0f0;">KODE</th>';
    $html .= '<th style="width: 56%; padding: 1mm 0.5mm; border-right: 1px solid #000; background: #f0f0f0;">NAMA BARANG</th>';
    $html .= '<th style="width: 12%; text-align: center; padding: 1mm 0.5mm; border-right: 1px solid #000; background: #f0f0f0;">QTY</th>';
    $html .= '<th style="width: 13%; text-align: center; padding: 1mm 0.5mm; background: #f0f0f0;">SATUAN</th>';
    $html .= '</tr></thead><tbody>';

    $no = 1;
    foreach ($groupedLines as $item) {
        $html .= '<tr>';
        $html .= '<td style="width: 6%; text-align: center; padding: 0.5mm; border-right: 1px solid #000; border-bottom: 1px dotted #ccc;">'.str_pad($no, 2, '0', STR_PAD_LEFT).'</td>';
        
        // KODE
        $displaySku = $item['sku'];
        if ($item['sku'] === 'SRV-GEN' && !empty($actualServiceNames)) {
            $displaySku = 'LAYANAN';
        }
        $html .= '<td style="width: 13%; padding: 0.5mm; border-right: 1px solid #000; border-bottom: 1px dotted #ccc; overflow: hidden;">'.e($displaySku).'</td>';
        
        // ✅ NAMA BARANG - WIDTH 56% SEKARANG FIT
        $displayName = $item['name'];
        if ($item['sku'] === 'SRV-GEN' && !empty($actualServiceNames)) {
            $displayName = count($actualServiceNames) === 1
                ? $actualServiceNames[0]
                : implode(', ', $actualServiceNames);
        }
        
        $nameText = e($displayName);
        if ($item['qty_sisa'] > 0) {
            $nameText .= ' (Sisa: '.number_format($item['qty_sisa'], 2).' m)';
        }
        $html .= '<td style="width: 56%; padding: 0.5mm; border-right: 1px solid #000; border-bottom: 1px dotted #ccc; overflow: hidden;">'.$nameText.'</td>';
        
        // QTY & SATUAN
        if ($item['is_service']) {
            $html .= '<td style="width: 12%; text-align: center; padding: 0.5mm; border-right: 1px solid #000; border-bottom: 1px dotted #ccc;">1</td>';
            $html .= '<td style="width: 13%; text-align: center; padding: 0.5mm; border-bottom: 1px dotted #ccc;">Layanan</td>';
        } else if ($item['qty_material'] > 0) {
            $html .= '<td style="width: 12%; text-align: center; padding: 0.5mm; border-right: 1px solid #000; border-bottom: 1px dotted #ccc;">'.number_format($item['qty_material'], 0).'</td>';
            $html .= '<td style="width: 13%; text-align: center; padding: 0.5mm; border-bottom: 1px dotted #ccc;">Pcs</td>';
        } else {
            $html .= '<td style="width: 12%; text-align: center; padding: 0.5mm; border-right: 1px solid #000; border-bottom: 1px dotted #ccc;">-</td>';
            $html .= '<td style="width: 13%; text-align: center; padding: 0.5mm; border-bottom: 1px dotted #ccc;">-</td>';
        }
        
        $html .= '</tr>';
        $no++;
    }
    $html .= '</tbody></table>';

    $html .= '<div style="text-align: center; font-weight: bold; font-size: '.$dataFont.'px; margin: 2mm 0; border: 1px solid #000; padding: 1mm;">';
    $html .= 'Total Barang: '.count($groupedLines).' ('.$this->terbilang(count($groupedLines)).') Jenis';
    $html .= '</div>';

    $html .= '<table style="width: 100%; border-collapse: collapse; margin-top: 2mm; font-size: '.$smallFont.'px; font-weight: bold;">';
    $html .= '<tr>';
    $html .= '<td style="width: 40%; border: none;">KETERANGAN: ';
    if (!empty($sale->notes)) {
        $cleanNotes = preg_replace('/\|\s*KEMBALIAN:.*$/i', '', $sale->notes);
        $cleanNotes = trim($cleanNotes);
        if (!empty($cleanNotes)) {
            $html .= e($cleanNotes);
        } else {
            $html .= 'Barang diterima dalam keadaan baik';
        }
    } else {
        $html .= 'Barang diterima dalam keadaan baik';
    }
    $html .= '</td>';
    
    $html .= '<td style="width: 30%; text-align: center; border: none;">';
    $html .= 'YANG MENYERAHKAN<br><br><br>_________________<br>(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)';
    $html .= '</td>';

    $html .= '<td style="width: 30%; text-align: center; border: none;">';
    $html .= 'YANG MENERIMA<br><br><br>_________________<br>(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)';
    $html .= '</td>';
    $html .= '</tr></table>';
    
    $html .= '</div>'; // End page-container

    // ========================================
    // ✅ HALAMAN 2: INVOICE
    // ========================================
    
    $html .= '<div class="page-container new-page">';

    $html .= '<div style="font-size: '.$titleFont.'px; font-weight: bold; text-align: center; margin-bottom: 1mm;">FAKTUR PENJUALAN</div>';
    $html .= '<div style="text-align: center; font-weight: bold; font-size: '.$dataFont.'px; margin-bottom: 1mm;">Invoice #'.str_pad($id, 5, '0', STR_PAD_LEFT).'</div>';
    
    $html .= '<div style="font-size: '.$headerFont.'px; font-weight: bold; text-align: center; margin-bottom: 2mm;">';
    $html .= '<div>'.strtoupper(e($sale->branch_name ?? 'MAJALENGKA')).'</div>';
    $html .= '<div style="font-size: '.$smallFont.'px;">Sedia: WPC Dinding, Atap UPVC, Kaca Bevel, Hollo, Wall Moulding PVC, Lantai Vinyl, Lantai SPC, dll</div>';
    
    if (!empty($sale->branch_address)) {
        $html .= '<div style="font-size: '.$smallFont.'px;">'.e($sale->branch_address).'</div>';
    }
    
    $html .= '<div style="font-size: '.$smallFont.'px; font-weight: bold;">Telp: 0811 2287 2006</div>';
    $html .= '</div>';

    $html .= '<div style="border-top: 2px solid #000; margin: 2mm 0;"></div>';

    $html .= '<table style="width: 100%; border-collapse: collapse; font-size: '.$labelFont.'px; font-weight: bold; margin-bottom: 1mm;">';
    $html .= '<tr>';
    $html .= '<td style="width: 50%; border: none;">TANGGAL: '.$saleDate.'</td>';
    $html .= '<td style="width: 50%; text-align: right; border: none;">PELANGGAN: ';
    if ($sale->customer_name) {
        $html .= e($sale->customer_name);
        if ($sale->customer_phone) {
            $html .= ' ('.e($sale->customer_phone).')';
        }
    } else {
        $html .= 'Pelanggan Umum';
    }
    $html .= '</td>';
    $html .= '</tr></table>';

    // ✅ TABEL INVOICE - WIDTH 100% FIT DENGAN BORDER
    $html .= '<table style="width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid #000; font-size: '.$tableFont.'px; font-weight: bold;">';
    
    $html .= '<thead><tr style="border-bottom: 1px solid #000;">';
    $html .= '<th style="width: 52%; padding: 1mm 0.5mm; border-right: 1px solid #000; background: #f0f0f0;">NAMA PRODUK</th>';
    $html .= '<th style="width: 15%; text-align: center; padding: 1mm 0.5mm; border-right: 1px solid #000; background: #f0f0f0;">QTY</th>';
    $html .= '<th style="width: 16%; text-align: right; padding: 1mm 0.5mm; border-right: 1px solid #000; background: #f0f0f0;">HARGA</th>';
    $html .= '<th style="width: 17%; text-align: right; padding: 1mm 0.5mm; background: #f0f0f0;">SUBTOTAL</th>';
    $html .= '</tr></thead><tbody>';

    foreach ($groupedLines as $item) {
        $html .= '<tr>';
        
        // ✅ NAMA PRODUK - WIDTH 52% FIT
        $displayName = $item['name'];
        $displaySku = $item['sku'];
        
        if ($item['sku'] === 'SRV-GEN' && !empty($actualServiceNames)) {
            $displayName = count($actualServiceNames) === 1
                ? $actualServiceNames[0]
                : implode(', ', $actualServiceNames);
            $displaySku = 'LAYANAN';
        }
        
        $nameText = e($displayName).' ('.e($displaySku).')';
        if ($item['qty_sisa'] > 0) {
            $nameText .= ' [Sisa: '.number_format($item['qty_sisa'], 2).'m]';
        }
        $html .= '<td style="width: 52%; padding: 0.5mm; border-right: 1px solid #000; border-bottom: 1px dotted #ccc; overflow: hidden;">'.$nameText.'</td>';
        
        // QTY
        if ($item['is_service']) {
            $html .= '<td style="width: 15%; text-align: center; padding: 0.5mm; border-right: 1px solid #000; border-bottom: 1px dotted #ccc;">1 Layanan</td>';
        } else if ($item['qty_material'] > 0) {
            $html .= '<td style="width: 15%; text-align: center; padding: 0.5mm; border-right: 1px solid #000; border-bottom: 1px dotted #ccc;">'.number_format($item['qty_material'], 0).'</td>';
        } else {
            $html .= '<td style="width: 15%; text-align: center; padding: 0.5mm; border-right: 1px solid #000; border-bottom: 1px dotted #ccc;">-</td>';
        }
        
        // HARGA & SUBTOTAL
        $html .= '<td style="width: 16%; text-align: right; padding: 0.5mm; border-right: 1px solid #000; border-bottom: 1px dotted #ccc;">'.number_format($item['display_price'], 0, ',', '.').'</td>';
        $html .= '<td style="width: 17%; text-align: right; padding: 0.5mm; border-bottom: 1px dotted #ccc;">'.number_format($item['total_subtotal'], 0, ',', '.').'</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';

    $html .= '<table style="width: 100%; border-collapse: collapse; font-size: '.$labelFont.'px; font-weight: bold; margin-top: 1mm;">';
    $html .= '<tr><td style="width: 50%; border: none;"></td>';
    $html .= '<td style="width: 50%; border: 2px solid #000; padding: 1mm;">';
    
    $html .= '<div>Subtotal: Rp '.number_format($calculatedSubtotal, 0, ',', '.').'</div>';
    
    if ($discount > 0) {
        $html .= '<div>Potongan: Rp '.number_format($discount, 0, ',', '.').'</div>';
    }
    
    $html .= '<div style="font-size: '.$headerFont.'px; border-top: 1px solid #000; padding-top: 1mm;">TOTAL BAYAR: Rp '.number_format($finalTotal, 0, ',', '.').'</div>';
    
    if ($changeAmount > 0) {
        $html .= '<div>Kembalian: Rp '.number_format($changeAmount, 0, ',', '.').'</div>';
    }
    
    $html .= '</td></tr></table>';

    $html .= '<div style="text-align: center; font-weight: bold; font-size: '.$dataFont.'px; margin: 2mm 0; border: 1px solid #000; padding: 1mm;">';
    $html .= 'PEMBAYARAN: Transfer Bank BCA 4181380637 a/n YADI MULYADI';
    $html .= '</div>';

    $html .= '<table style="width: 100%; border-collapse: collapse; margin-top: 1mm; font-size: '.$smallFont.'px; font-weight: bold;">';
    $html .= '<tr>';
    $html .= '<td style="width: 60%; border: none;">';
    
    if (!empty($sale->notes)) {
        $cleanNotes = preg_replace('/\|\s*KEMBALIAN:.*$/i', '', $sale->notes);
        $cleanNotes = trim($cleanNotes);
        
        if (!empty($cleanNotes)) {
            $html .= 'CATATAN: '.e($cleanNotes);
        }
    }
    
    $html .= '</td>';
    
    $html .= '<td style="width: 40%; text-align: center; border: none;">';
    $html .= 'HORMAT KAMI<br><br><br>_________________';
    $html .= '</td>';
    $html .= '</tr></table>';
    
    $html .= '</div>'; // End second page-container

    $html .= '<script>
        window.addEventListener("load", function() {
            setTimeout(function() {
                window.print();
            }, 500);
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
