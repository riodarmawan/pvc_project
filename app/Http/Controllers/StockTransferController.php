<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockTransferController extends Controller
{
    private const TBL_QUANTS = 'stock_quants';

    /**
     * Halaman form transfer - DENGAN SUPPORT DISPLAY STOK DI DROPDOWN
     */
    public function create()
    {
        $branches = DB::table('branches')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();

        // Tidak load products di sini - akan diload via AJAX berdasarkan cabang
        return view('stock.transfer.create', [
            'branches' => $branches,
        ]);
    }
/**
 * Tampilkan history transfer dengan filter rentang waktu dan pagination
 */
public function index(Request $request)
{
    // Default rentang waktu (hari ini)
    $dateFrom = $request->input('date_from', now()->format('Y-m-d'));
    $dateTo = $request->input('date_to', now()->format('Y-m-d'));
    
    // Validasi format tanggal
    try {
        $dateFromParsed = Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay();
        $dateToParsed = Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay();
    } catch (\Exception $e) {
        $dateFromParsed = now()->startOfDay();
        $dateToParsed = now()->endOfDay();
        $dateFrom = $dateFromParsed->format('Y-m-d');
        $dateTo = $dateToParsed->format('Y-m-d');
    }
    
    // Query transfer dengan join cabang dan pagination
    $transfers = DB::table('stock_transfers as t')
        ->leftJoin('branches as bf', 'bf.id', '=', 't.branch_from_id')
        ->leftJoin('branches as bt', 'bt.id', '=', 't.branch_to_id')
        ->whereBetween('t.created_at', [$dateFromParsed, $dateToParsed])
        ->select(
            't.id',
            't.status',
            't.created_at',
            't.shipped_at',
            't.received_at',
            't.notes',
            'bf.name as branch_from_name',
            'bt.name as branch_to_name',
            DB::raw('(SELECT COUNT(*) FROM stock_transfer_lines WHERE transfer_id = t.id) as total_items'),
            DB::raw('(SELECT SUM(qty) FROM stock_transfer_lines WHERE transfer_id = t.id) as total_qty')
        )
        ->orderBy('t.created_at', 'desc')
        ->paginate(15)
        ->withQueryString(); // Mempertahankan query parameter saat pagination
    
    return view('stock.transfer.index', [
        'transfers' => $transfers,
        'dateFrom' => $dateFrom,
        'dateTo' => $dateTo,
        'totalTransfers' => $transfers->total()
    ]);
}
/**
 * AJAX: Mendapatkan produk dengan stok berdasarkan cabang untuk dropdown.
 */
public function getProductsWithStock(Request $request)
{
    try {
        $branchId = (int) $request->input('branch_id');
        
        if (!$branchId) {
            return response()->json(['error' => 'Branch ID harus diisi'], 400);
        }
        
        // Ambil semua lokasi untuk cabang ini
        $locations = DB::table('stock_locations')
            ->where('branch_id', $branchId)
            ->pluck('id')
            ->toArray();
        
        if (empty($locations)) {
            // Auto-create default location
            $locationId = DB::table('stock_locations')->insertGetId([
                'branch_id' => $branchId,
                'code' => 'STORE',
                'name' => 'Gudang Utama',
                'type' => 'STORE',
                'created_at' => now()
            ]);
            $locations = [$locationId];
        }
        
        // Ambil semua produk aktif dengan total stok per cabang
        $products = DB::table('products as p')
            ->join('uoms as u', 'u.id', '=', 'p.uom_id')
            ->leftJoin('stock_quants as sq', function($join) use ($locations) {
                $join->on('sq.product_id', '=', 'p.id')
                     ->whereIn('sq.location_id', $locations);
            })
            ->where('p.is_active', 1)
            ->select(
                'p.id',
                'p.sku', 
                'p.name',
                'u.code as uom',
                DB::raw('COALESCE(SUM(sq.qty), 0) as total_stock')
            )
            ->groupBy('p.id', 'p.sku', 'p.name', 'u.code')
            ->orderBy('p.sku')
            ->get();
        
        // Format data untuk dropdown dengan stok di ujung
        $formattedProducts = $products->map(function($product) {
            $stockDisplay = number_format($product->total_stock, 0);
            $displayText = "[{$product->sku}] {$product->name} ({$product->uom}) ({$stockDisplay})";
            
            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'uom' => $product->uom,
                'stock' => (float) $product->total_stock,
                'display_text' => $displayText
            ];
        });
        
        return response()->json([
            'products' => $formattedProducts,
            'branch_id' => $branchId
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Get Products With Stock Error: ' . $e->getMessage());
        return response()->json(['error' => 'Gagal mengambil data produk'], 500);
    }
}

    /**
     * Proses transfer stok antar cabang multi-item (langsung selesai).
     */
    public function store(Request $request)
    {
        // Normalisasi angka qty (dukung koma)
        $items = (array) $request->input('items', []);
        foreach ($items as $i => $row) {
            if (isset($row['qty'])) {
                $items[$i]['qty'] = $this->floatFromInput($row['qty']);
            }
        }
        $request->merge(['items' => $items]);

        // Validasi dasar
        $data = $request->validate([
            'branch_from_id'     => ['required', 'integer', 'exists:branches,id'],
            'branch_to_id'       => ['required', 'integer', 'different:branch_from_id', 'exists:branches,id'],
            'notes'              => ['nullable', 'string', 'max:255'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty'        => ['required', 'numeric', 'gt:0'],
        ], [
            'branch_to_id.different' => 'Cabang tujuan tidak boleh sama dengan cabang asal.',
        ]);

        $userId   = (int) (Auth::id() ?? 0);
        $fromId   = (int) $data['branch_from_id'];
        $toId     = (int) $data['branch_to_id'];
        $notes    = $data['notes'] ?? null;
        $items    = $data['items'];

        // Penentuan lokasi tujuan
        $locToId = $this->ensurePrimaryLocationId($toId);

        // Ambil data master cabang (untuk audit)
        $branchFrom = DB::table('branches')->select('id','name')->where('id',$fromId)->first();
        $branchTo   = DB::table('branches')->select('id','name')->where('id',$toId)->first();

        // Transaksikan seluruh operasi
        $result = DB::transaction(function () use ($items, $userId, $fromId, $toId, $locToId, $notes, $branchFrom, $branchTo) {

            // Buat header transfer (langsung closed)
            $transferId = DB::table('stock_transfers')->insertGetId([
                'branch_from_id' => $fromId,
                'branch_to_id'   => $toId,
                'status'         => 'CLOSED',   // langsung selesai
                'requested_by'   => $userId ?: 0,
                'approved_by'    => null,
                'shipped_at'     => now(),
                'received_at'    => now(),
                'notes'          => $notes,
                'created_at'     => now(),
            ]);

            // Dapatkan daftar lokasi di cabang asal, diprioritaskan
            $sourceLocations = DB::table('stock_locations')
                ->where('branch_id', $fromId)
                ->orderByRaw("FIELD(type, 'STORE', 'AVAILABLE')")
                ->get();

            foreach ($items as $row) {
                $pid = (int) $row['product_id'];
                $qty = round((float) $row['qty'], 2);
                if ($qty <= 0) {
                    continue;
                }

                // Master produk (ambil uom untuk stock_moves/lines)
                $prod = DB::table('products')->select('id','sku','name','uom_id')->where('id',$pid)->first();
                if (!$prod) {
                    throw new \RuntimeException("Produk $pid tidak ditemukan.");
                }

                // ===== LOGIKA PENCARIAN STOK ASAL =====
                $fromQuant = null;
                $locFromId = null;

                // Cari di setiap lokasi prioritas di cabang asal
                foreach ($sourceLocations as $location) {
                    $potentialQuant = DB::table(self::TBL_QUANTS)
                        ->where('product_id', $pid)
                        ->where('location_id', $location->id)
                        ->lockForUpdate()
                        ->first();

                    // Jika stok ditemukan dan mencukupi, gunakan lokasi ini!
                    if ($potentialQuant && (float) $potentialQuant->qty >= $qty) {
                        $fromQuant = $potentialQuant;
                        $locFromId = $location->id;
                        break; // Hentikan pencarian karena sudah ketemu
                    }
                }

                // Jika setelah dicari di semua lokasi tetap tidak ada yang cukup
                if (!$fromQuant) {
                    // Hitung total stok di cabang asal untuk pesan error yang lebih informatif
                    $totalStockInBranch = DB::table(self::TBL_QUANTS)
                        ->where('product_id', $pid)
                        ->whereIn('location_id', $sourceLocations->pluck('id')->all())
                        ->sum('qty');

                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items' => ["Stok cabang asal untuk produk [ID:$pid] tidak mencukupi. Diminta: " . number_format($qty,2) . ", Total tersedia di cabang: " . number_format($totalStockInBranch, 2)],
                    ]);
                }
                
                $fromOld = (float) $fromQuant->qty;
                $fromNew = round($fromOld - $qty, 2);
                $this->setQuantDirect($pid, $locFromId, $fromNew, $fromQuant);

                // Stock move OUT (asal)
                DB::table('stock_moves')->insert([
                    'product_id'       => $pid,
                    'uom_id'           => (int) $prod->uom_id,
                    'qty'              => $qty,
                    'from_location_id' => $locFromId, // Dinamis sesuai lokasi stok ditemukan
                    'to_location_id'   => null,
                    'ref_type'         => 'TRANSFER',
                    'ref_id'           => $transferId,
                    'state'            => 'DONE',
                    'created_by'       => $userId ?: 0,
                    'created_at'       => now(),
                ]);

                // ===== Tambah stok di cabang TUJUAN =====
                $toQuant = DB::table(self::TBL_QUANTS)
                    ->where('product_id', $pid)
                    ->where('location_id', $locToId)
                    ->lockForUpdate()
                    ->first();

                $toOld = $toQuant ? (float) $toQuant->qty : 0.0;
                $toNew = round($toOld + $qty, 2);
                $this->setQuantDirect($pid, $locToId, $toNew, $toQuant);

                // Stock move IN (tujuan)
                DB::table('stock_moves')->insert([
                    'product_id'       => $pid,
                    'uom_id'           => (int) $prod->uom_id,
                    'qty'              => $qty,
                    'from_location_id' => null,
                    'to_location_id'   => $locToId,
                    'ref_type'         => 'TRANSFER',
                    'ref_id'           => $transferId,
                    'state'            => 'DONE',
                    'created_by'       => $userId ?: 0,
                    'created_at'       => now(),
                ]);

                // Detail transfer
                DB::table('stock_transfer_lines')->insert([
                    'transfer_id' => $transferId,
                    'product_id'  => $pid,
                    'uom_id'      => (int) $prod->uom_id,
                    'qty'         => $qty,
                ]);

                // Audit per item (ringkas)
                DB::table('audit_logs')->insert([
                    'event'    => 'STOCK_TRANSFER_ITEM',
                    'user_id'  => $userId ?: null,
                    'ref_type' => 'TRANSFER',
                    'ref_id'   => $transferId,
                    'payload'  => json_encode([
                        'product_id'   => $pid,
                        'product_sku'  => $prod->sku,
                        'product_name' => $prod->name,
                        'from_branch'  => ['id'=>$branchFrom->id, 'name'=>$branchFrom->name],
                        'to_branch'    => ['id'=>$branchTo->id,   'name'=>$branchTo->name],
                        'from_loc_id'  => $locFromId,
                        'to_loc_id'    => $locToId,
                        'qty'          => $qty,
                        'from_old'     => $fromOld,
                        'from_new'     => $fromNew,
                        'to_old'       => $toOld,
                        'to_new'       => $toNew,
                    ], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                ]);
            }

            // Audit header
            DB::table('audit_logs')->insert([
                'event'    => 'STOCK_TRANSFER_HEADER',
                'user_id'  => $userId ?: null,
                'ref_type' => 'TRANSFER',
                'ref_id'   => $transferId,
                'payload'  => json_encode([
                    'transfer_id'  => $transferId,
                    'from_branch'  => ['id'=>$branchFrom->id, 'name'=>$branchFrom->name],
                    'to_branch'    => ['id'=>$branchTo->id,   'name'=>$branchTo->name],
                    'notes'        => $notes,
                    'total_items'  => count($items),
                    'items'        => $items
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
            ]);
            
            return $transferId;
        });

        // Redirect ke surat jalan
// Di fungsi store(), ubah redirect menjadi:
return redirect()->route('stock.transfer.index')
    ->with('success', "Transfer stok berhasil diproses (ID: {$result}) untuk " . count($items) . " item. Surat jalan dapat dicetak dari history.");

    }

    /**
     * Print surat jalan optimized untuk Epson LX-310.
     */
    /**
 * Print surat jalan optimized untuk Epson LX-310 - FIXED untuk OWNER LOGIN
 */
public function printDeliveryNote($id)
{
    $id = (int) $id;
    
    // Ambil data transfer TANPA join ke users (karena owner login)
    $transfer = DB::table('stock_transfers as t')
        ->leftJoin('branches as bf', 'bf.id', '=', 't.branch_from_id')
        ->leftJoin('branches as bt', 'bt.id', '=', 't.branch_to_id')
        ->where('t.id', $id)
        ->select(
            't.*',
            'bf.name as branch_from_name', 'bf.address as branch_from_address',
            'bt.name as branch_to_name', 'bt.address as branch_to_address',
            // ✅ SET NAMA OWNER LANGSUNG tanpa join ke users
            DB::raw('"OWNER/SISTEM" as created_by_name')
        )
        ->first();
    
    if (!$transfer) {
        abort(404, 'Transfer tidak ditemukan');
    }
    
    // Ambil detail items
    $items = DB::table('stock_transfer_lines as tl')
        ->join('products as p', 'p.id', '=', 'tl.product_id')
        ->join('uoms as u', 'u.id', '=', 'tl.uom_id')
        ->where('tl.transfer_id', $id)
        ->select('tl.*', 'p.sku', 'p.name', 'u.code as uom')
        ->orderBy('p.sku')
        ->get();
    
    // Generate nomor surat jalan berbasis ID transfer
    $deliveryNumber = 'SJ-' . date('ymd') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT);
    
    // Format tanggal
    $transferDate = date('d/m/Y H:i', strtotime($transfer->created_at));
    $printDate = date('d/m/Y H:i');
    
    // Dynamic scaling untuk dot matrix berdasarkan jumlah items
    $itemCount = count($items);
    $baseFont = ($itemCount > 15) ? 9 : 10;
    $tableFont = ($itemCount > 15) ? 8 : 9;
    $smallFont = ($itemCount > 15) ? 7 : 8;
    $mediumFont = ($itemCount > 15) ? 10 : 11;
    $largeFont = ($itemCount > 15) ? 11 : 12;
    $paddingSize = ($itemCount > 15) ? 1 : 2;
    $marginSize = ($itemCount > 25) ? '0.1in' : (($itemCount > 15) ? '0.15in' : '0.2in');
    
    // Generate HTML optimized untuk Epson LX-310
    $html = '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8">';
    $html .= '<title>Surat Jalan ' . $deliveryNumber . '</title>';
    $html .= '<style>
        @page { 
            size: 10in 14in; 
            margin: '.$marginSize.'; 
        }
        body { 
            font-family: "Courier New", "Draft", monospace; 
            font-size: '.$baseFont.'px; 
            line-height: 1.1; 
            margin: 0; 
            padding: 0; 
            color: #000;
            letter-spacing: 0.5px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: '.$tableFont.'px; 
            font-family: "Courier New", monospace; 
        }
        th, td { 
            padding: '.$paddingSize.'px 3px; 
            text-align: left; 
            border-bottom: 1px solid #000; 
            vertical-align: top;
            word-break: break-word;
        }
        th { 
            font-weight: bold; 
            border-bottom: 2px solid #000; 
            text-transform: uppercase;
            background-color: #f0f0f0;
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
            margin: '.($paddingSize * 2).'px 0; 
        }
        .dotted-line { 
            border-top: 1px dotted #000; 
            margin: '.$paddingSize.'px 0; 
        }
        .signature-box {
            border: 2px solid #000;
            padding: '.($paddingSize * 3).'px;
            margin-top: '.($paddingSize * 4).'px;
        }
        
        .dot-matrix-text {
            font-family: "Courier New", monospace;
            letter-spacing: 0.5px;
            font-weight: normal;
        }
        
        .dot-matrix-bold {
            font-family: "Courier New", monospace;
            font-weight: bold;
            letter-spacing: 0.3px;
        }
        
        @media print { 
            body { 
                margin: 0; 
                -webkit-print-color-adjust: exact;
            }
            .no-print { display: none; }
        }
    </style></head><body>';
    
    // HEADER SURAT JALAN
    $html .= '<table style="border: none; margin-bottom: '.($paddingSize * 2).'px;">';
    $html .= '<tr><td style="border: none; padding: '.$paddingSize.'px;" class="header-company dot-matrix-bold">';
    $html .= '<div class="large">SURAT JALAN TRANSFER STOK</div>';
    $html .= '<div class="medium">'.strtoupper(e($transfer->branch_from_name ?? 'PERUSAHAAN')).'</div>';
    $html .= '<div class="small dot-matrix-text">Transfer Barang Antar Cabang</div>';
    $html .= '</td></tr></table>';
    
    // GARIS PEMISAH
    $html .= '<div class="line-separator"></div>';
    
    // INFO TRANSFER
    $html .= '<table style="border: none; margin-bottom: '.($paddingSize * 2).'px;">';
    $html .= '<tr>';
    $html .= '<td style="border: none; width: 50%; padding: 1px;" class="dot-matrix-text">';
    $html .= '<div class="medium dot-matrix-bold">No. Transfer: ' . $deliveryNumber . '</div>';
    $html .= '<div>Tanggal: ' . $transferDate . '</div>';
    $html .= '<div>Status: <span class="bold">'.strtoupper($transfer->status ?? 'CLOSED').'</span></div>';
    $html .= '</td>';
    $html .= '<td style="border: none; text-align: right; padding: 1px;" class="dot-matrix-text">';
    $html .= '<div class="bold">Petugas Transfer:</div>';
    $html .= '<div>'.e($transfer->created_by_name).'</div>';
    $html .= '</td>';
    $html .= '</tr></table>';
    
    // INFO CABANG
    $html .= '<table style="border: 1px solid #000; margin: '.($paddingSize * 2).'px 0;" class="dot-matrix-text">';
    $html .= '<tr>';
    $html .= '<td style="border-right: 1px solid #000; width: 50%; padding: '.($paddingSize * 2).'px;" class="dot-matrix-bold">';
    $html .= '<div class="medium">CABANG ASAL</div>';
    $html .= '<div class="dot-matrix-text">'.e($transfer->branch_from_name ?? '').'</div>';
    if (!empty($transfer->branch_from_address)) {
        $html .= '<div class="small dot-matrix-text">'.e($transfer->branch_from_address).'</div>';
    }
    $html .= '</td>';
    $html .= '<td style="width: 50%; padding: '.($paddingSize * 2).'px;" class="dot-matrix-bold">';
    $html .= '<div class="medium">CABANG TUJUAN</div>';
    $html .= '<div class="dot-matrix-text">'.e($transfer->branch_to_name ?? '').'</div>';
    if (!empty($transfer->branch_to_address)) {
        $html .= '<div class="small dot-matrix-text">'.e($transfer->branch_to_address).'</div>';
    }
    $html .= '</td>';
    $html .= '</tr></table>';
    
    // GARIS PEMISAH
    $html .= '<div class="dotted-line"></div>';
    
    // TABEL BARANG
    $html .= '<table class="dot-matrix-text">';
    $html .= '<thead><tr>';
    $html .= '<th style="width:8%" class="center">NO</th>';
    $html .= '<th style="width:20%">SKU</th>';
    $html .= '<th style="width:45%">NAMA BARANG</th>';
    $html .= '<th style="width:12%" class="center">QTY</th>';
    $html .= '<th style="width:15%" class="center">SATUAN</th>';
    $html .= '</tr></thead><tbody>';
    
    $no = 1;
    $totalQty = 0;
    
    foreach ($items as $item) {
        $html .= '<tr>';
        $html .= '<td class="center">'.str_pad($no, 2, '0', STR_PAD_LEFT).'</td>';
        $html .= '<td class="dot-matrix-bold">'.e($item->sku).'</td>';
        $html .= '<td>'.e($item->name).'</td>';
        $html .= '<td class="center bold">'.number_format($item->qty, ($item->qty == (int)$item->qty ? 0 : 2)).'</td>';
        $html .= '<td class="center">'.strtoupper(e($item->uom)).'</td>';
        $html .= '</tr>';
        
        $totalQty += $item->qty;
        $no++;
    }
    
    $html .= '</tbody></table>';
    
    // RINGKASAN
    $html .= '<table style="border: 2px solid #000; margin-top: '.($paddingSize * 3).'px;" class="dot-matrix-bold">';
    $html .= '<tr><td style="border: none; text-align: center; padding: '.($paddingSize * 2).'px;">';
    $html .= '<div class="medium">RINGKASAN TRANSFER</div>';
    $html .= '<div class="dot-matrix-text" style="margin: '.$paddingSize.'px 0;">Total Item: <span class="bold">'.count($items).' jenis barang</span></div>';
    $html .= '<div class="dot-matrix-text">Total Quantity: <span class="bold">'.number_format($totalQty, 2).'</span></div>';
    $html .= '</td></tr></table>';
    
    // CATATAN
    if (!empty($transfer->notes)) {
        $html .= '<table style="border: 1px solid #000; margin-top: '.($paddingSize * 2).'px;" class="dot-matrix-text">';
        $html .= '<tr><td style="border: none; padding: '.($paddingSize * 2).'px;">';
        $html .= '<div class="bold">CATATAN:</div>';
        $html .= '<div class="small">'.nl2br(e($transfer->notes)).'</div>';
        $html .= '</td></tr></table>';
    }
    
    // SIGNATURE BOXES
    $html .= '<table style="border: none; margin-top: '.($paddingSize * 4).'px;" class="dot-matrix-text">';
    $html .= '<tr>';
    $html .= '<td style="border: 2px solid #000; width: 33%; padding: '.($paddingSize * 3).'px; text-align: center;">';
    $html .= '<div class="bold">PENGIRIM</div>';
    $html .= '<div style="margin: '.($paddingSize * 8).'px 0 '.$paddingSize.'px 0;">________________</div>';
    $html .= '<div class="small">Tanda Tangan & Nama</div>';
    $html .= '</td>';
    $html .= '<td style="border: none; width: 34%;"></td>';
    $html .= '<td style="border: 2px solid #000; width: 33%; padding: '.($paddingSize * 3).'px; text-align: center;">';
    $html .= '<div class="bold">PENERIMA</div>';
    $html .= '<div style="margin: '.($paddingSize * 8).'px 0 '.$paddingSize.'px 0;">________________</div>';
    $html .= '<div class="small">Tanda Tangan & Nama</div>';
    $html .= '</td>';
    $html .= '</tr></table>';
    
    // STATUS PENERIMAAN
    $html .= '<table style="border: 2px solid #000; margin-top: '.($paddingSize * 3).'px;" class="dot-matrix-text">';
    $html .= '<tr><td style="border: none; padding: '.($paddingSize * 2).'px;">';
    $html .= '<div class="bold center">STATUS PENERIMAAN BARANG</div>';
    $html .= '<div style="margin: '.$paddingSize.'px 0;">';
    $html .= '[ &nbsp; ] Diterima Lengkap dan Sesuai<br>';
    $html .= '[ &nbsp; ] Ada Kekurangan / Selisih<br>';
    $html .= '[ &nbsp; ] Ada Kerusakan / Cacat';
    $html .= '</div>';
    $html .= '<div class="small">Catatan Penerima: ________________________________</div>';
    $html .= '</td></tr></table>';
    
    // FOOTER
    $html .= '<div style="margin-top: '.($paddingSize * 4).'px; border-top: 1px solid #000; padding-top: '.$paddingSize.'px;" class="small dot-matrix-text center">';
    $html .= 'Dokumen ini dicetak pada: '.$printDate.' | Ref: '.$deliveryNumber;
    $html .= '</div>';
    
    // AUTO PRINT SCRIPT
    $html .= '<script>window.print();</script>';
    $html .= '</body></html>';
    
    return response($html);
}


    /**
     * Dapatkan lokasi utama untuk cabang dengan prioritas STORE first.
     */
    private function ensurePrimaryLocationId(int $branchId): int
    {
        $loc = DB::table('stock_locations')
            ->where('branch_id', $branchId)
            ->where(function ($q) {
                $q->whereIn('type', ['STORE', 'AVAILABLE'])
                  ->orWhereIn('code', ['STORE', 'AVAILABLE']);
            })
            ->orderByRaw("FIELD(type, 'STORE', 'AVAILABLE')") 
            ->first();

        if ($loc) {
            return (int) $loc->id;
        }

        // Buat default STORE jika belum ada lokasi apa pun
        return (int) DB::table('stock_locations')->insertGetId([
            'branch_id' => $branchId,
            'code'      => 'STORE',
            'name'      => 'Gudang Utama',
            'type'      => 'STORE',
            'created_at' => now(),
        ]);
    }

    /**
     * Set qty langsung pada baris quant (update / insert).
     * Tidak menghapus baris saat qty = 0 (biarkan sebagai jejak).
     */
    private function setQuantDirect(int $productId, int $locationId, float $newQty, $existingQuant = null): void
    {
        if ($existingQuant) {
            DB::table(self::TBL_QUANTS)
                ->where('id', $existingQuant->id)
                ->update(['qty' => $newQty]);
            return;
        }

        DB::table(self::TBL_QUANTS)->insert([
            'product_id'  => $productId,
            'location_id' => $locationId,
            'qty'         => $newQty,
        ]);
    }

    /** Normalisasi angka dari input (mendukung koma). */
    private function floatFromInput($value): float
    {
        if ($value === null) return 0.0;
        $s = trim((string) $value);
        $s = str_replace([' ', ','], ['', '.'], $s); // "12,5" -> "12.5"
        return (float) $s;
    }
}
