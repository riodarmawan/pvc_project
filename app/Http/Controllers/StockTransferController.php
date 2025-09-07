<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    private const TBL_QUANTS = 'stock_quants';

    /**
     * Halaman form transfer.
     * - Owner: pilih cabang asal/tujuan.
     * - Kasir/pegawai yang punya default_branch_id tetap bisa memilih cabang lain (independen).
     */
    public function create()
    {
        $branches = DB::table('branches')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();

        $products = DB::table('products')
            ->select('id', 'sku', 'name', 'uom_id')
            ->where('is_active', 1)
            ->orderBy('sku')
            ->limit(1500)
            ->get();

        return view('stock.transfer.create', [
            'branches' => $branches,
            'products' => $products,
        ]);
    }

    /**
     * Proses transfer stok antar cabang (langsung selesai).
     *
     * Request payload (contoh):
     * branch_from_id: 1
     * branch_to_id: 2
     * notes: "Mutasi antar cabang"
     * items: [
     *   ['product_id' => 45, 'qty' => '5'],
     *   ['product_id' => 56, 'qty' => '12.5'],
     * ]
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

        // ===== PERUBAHAN LOGIKA DIMULAI DI SINI =====
        // Kita tidak lagi menentukan lokasi asal di awal, karena setiap produk bisa berada di lokasi berbeda.
        // Penentuan lokasi tujuan tetap sama.
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

                // ===== LOGIKA PENCARIAN STOK ASAL YANG BARU =====

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

                // Jika setelah dicari di semua lokasi tetap tidak ada yang cukup, baru lempar error
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
                
                // ===== SELESAI PERUBAHAN LOGIKA PENCARIAN =====


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
            // (Tidak ada perubahan di sini)
            // ...
            
            return $transferId;
        });

        return redirect()->back()->with('success', "Transfer stok berhasil diproses (ID: {$result}).");
    }


    /**
     * Dapatkan lokasi utama untuk cabang:
     * - Prioritas: type/code = 'AVAILABLE'
     * - Fallback:  type/code = 'STORE'
     * - Jika tak ada satupun → buat lokasi 'AVAILABLE'
     */
    /**
     * Dapatkan lokasi utama untuk cabang:
     * - [DIPERBARUI] Prioritas: type/code = 'STORE'
     * - Fallback:   type/code = 'AVAILABLE'
     * - Jika tak ada satupun → buat lokasi 'AVAILABLE'
     */
    private function ensurePrimaryLocationId(int $branchId): int
    {
        $loc = DB::table('stock_locations')
            ->where('branch_id', $branchId)
            ->where(function ($q) {
                $q->whereIn('type', ['STORE', 'AVAILABLE'])
                  ->orWhereIn('code', ['STORE', 'AVAILABLE']);
            })
            // ===== PERUBAHAN DI BARIS INI =====
            // Logika dibalik untuk memprioritaskan 'STORE' terlebih dahulu.
            ->orderByRaw("FIELD(type, 'STORE', 'AVAILABLE')") 
            ->first();

        if ($loc) {
            return (int) $loc->id;
        }

        // Buat default AVAILABLE jika belum ada lokasi apa pun
        return (int) DB::table('stock_locations')->insertGetId([
            'branch_id' => $branchId,
            'code'      => 'AVAILABLE',
            'name'      => 'Available Stock',
            'type'      => 'AVAILABLE',
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




