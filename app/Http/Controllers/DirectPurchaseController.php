<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DirectPurchaseController extends Controller
{
    private const TBL_QUANTS = 'stock_quants';

    public function create()
    {
        $suppliers = DB::table('suppliers')->orderBy('name')->get();
        $branches = DB::table('branches')->where('is_active', 1)->orderBy('name')->get();
        $products = DB::table('products')->where('is_active', 1)->select('id', 'sku', 'name')->orderBy('name')->limit(1000)->get();

        return view('purchase.direct.create', [
            'suppliers' => $suppliers,
            'branches'  => $branches,
            'products'  => $products,
        ]);
    }

    /**
     * [DIPAKSA] Memproses pembelian dengan mengambil harga dari notes jika riwayat tidak ada.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id'        => ['required', 'integer', 'exists:suppliers,id'],
            'branch_id'          => ['required', 'integer', 'exists:branches,id'],
            'invoice_no'         => ['required', 'string', 'max:60'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty'        => ['required', 'numeric', 'gt:0'],
        ]);

        $userId      = (int) Auth::id();
        $branchId    = (int) $data['branch_id'];
        $items       = (array) $data['items'];
        $totalAmount = 0;

        $resultPoId = DB::transaction(function () use ($data, $userId, $branchId, $items, &$totalAmount) {
            $locationId = $this->ensurePrimaryLocationId($branchId);
            $processedItems = [];

            // Pra-proses item untuk MENCARI harga (termasuk dari 'notes')
            foreach ($items as $index => $item) {
                $pid = (int) $item['product_id'];
                $qty = (float) $item['qty'];
                $price = null;

                // 1. Prioritas utama: Cari harga terakhir dari riwayat pembelian
                $lastPriceRecord = DB::table('purchase_order_lines as pol')
                    ->join('purchase_orders as po', 'po.id', '=', 'pol.po_id')
                    ->where('pol.product_id', $pid)
                    ->whereIn('po.status', ['APPROVED', 'CLOSED'])
                    ->orderBy('pol.id', 'desc')
                    ->select('pol.price')
                    ->first();
                
                if ($lastPriceRecord) {
                    $price = (float) $lastPriceRecord->price;
                } else {
                    // 2. Fallback: Jika tidak ada riwayat, coba parse dari kolom 'notes'
                    $product = DB::table('products')->where('id', $pid)->select('name', 'notes')->first();
                    $notes = $product->notes ?? '';
                    
                    // Regex untuk mencari 'hpp:100000' atau 'hpp: 100000'
                    if (preg_match('/hpp\s*:\s*([0-9\.]+)/i', $notes, $matches)) {
                        $price = (float) $matches[1];
                    }
                }
                
                // 3. Jika setelah semua cara tetap tidak ada harga, baru gagalkan transaksi
                if ($price === null) {
                    $productName = DB::table('products')->where('id', $pid)->value('name');
                    throw ValidationException::withMessages([
                        "items.{$index}.product_id" => "Harga beli untuk produk '{$productName}' tidak ditemukan di riwayat atau di catatan HPP.",
                    ]);
                }
                
                $item['price'] = $price;
                $processedItems[] = $item;
                $totalAmount += $qty * $price;
            }

            // Proses selanjutnya (membuat PO, GRN, stock moves) sama seperti sebelumnya...
            $poId = DB::table('purchase_orders')->insertGetId([
                'branch_id' => $branchId, 'supplier_id' => (int) $data['supplier_id'], 'status' => 'CLOSED',
                'total' => $totalAmount, 'requested_by' => $userId, 'approved_by' => $userId,
                'approved_at' => now(), 'created_at' => now(),
            ]);
            
            $grnId = DB::table('goods_receipts')->insertGetId([
                'po_id' => $poId, 'branch_id' => $branchId, 'received_by' => $userId,
                'received_at' => now(), 'status' => 'DONE',
            ]);

            foreach ($processedItems as $item) {
                $pid = (int) $item['product_id']; $qty = (float) $item['qty']; $price = (float) $item['price'];
                $prod = DB::table('products')->select('uom_id')->where('id', $pid)->first();
                DB::table('purchase_order_lines')->insert(['po_id' => $poId, 'product_id' => $pid, 'uom_id' => $prod->uom_id, 'qty_ordered' => $qty, 'price' => $price]);
                DB::table('goods_receipt_lines')->insert(['grn_id' => $grnId, 'product_id' => $pid, 'uom_id' => $prod->uom_id, 'qty_received' => $qty]);
                $quant = DB::table(self::TBL_QUANTS)->where('product_id', $pid)->where('location_id', $locationId)->first();
                if ($quant) { DB::table(self::TBL_QUANTS)->where('id', $quant->id)->increment('qty', $qty); } 
                else { DB::table(self::TBL_QUANTS)->insert(['product_id' => $pid, 'location_id' => $locationId, 'qty' => $qty]); }
                DB::table('stock_moves')->insert(['product_id' => $pid, 'uom_id' => $prod->uom_id, 'qty' => $qty, 'from_location_id' => null, 'to_location_id' => $locationId, 'ref_type' => 'GRN', 'ref_id' => $grnId, 'state' => 'DONE', 'created_by' => $userId, 'created_at' => now()]);
            }
            
            DB::table('audit_logs')->insert(['event' => 'PURCHASE_DIRECT_CREATED', 'user_id' => $userId, 'ref_type' => 'PO', 'ref_id' => $poId, 'payload' => json_encode($data), 'created_at' => now()]);

            return $poId;
        });

        return redirect()->back()->with('success', "Pembelian (PO ID: {$resultPoId}) berhasil dicatat.");
    }
    
    private function ensurePrimaryLocationId(int $branchId): int
    {
        $loc = DB::table('stock_locations')
            ->where('branch_id', $branchId)
            ->whereIn('type', ['STORE', 'AVAILABLE'])
            ->orderByRaw("FIELD(type, 'STORE', 'AVAILABLE')")
            ->first();

        if ($loc) {
            return (int) $loc->id;
        }

        return (int) DB::table('stock_locations')->insertGetId([
            'branch_id' => $branchId,
            'code'      => 'AVAILABLE',
            'name'      => 'Available Stock',
            'type'      => 'AVAILABLE',
        ]);
    }
}
