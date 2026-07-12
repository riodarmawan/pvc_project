<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PosController extends Controller
{

    /* =========================================================
     *  PAGE: KATALOG (/kasir)
     * =======================================================*/
      public function index(Request $request)
    {
        $user     = Auth::user();
        $branchId = (int) ($user->default_branch_id ?? 0);

        // kategori buat dropdown
        $categories = DB::table('product_categories')
            ->orderBy('name')->get();

        // filter
        $q     = trim((string) $request->get('q', ''));
        $catId = $request->get('cat_id');

        // ===== PERUBAHAN DIMULAI DI SINI =====

        // 1. Ambil SEMUA ID lokasi yang dimiliki oleh cabang saat ini.
        $branchLocationIds = DB::table('stock_locations')
                                ->where('branch_id', $branchId)
                                ->pluck('id')
                                ->all();
        
        // Konversi array menjadi string agar bisa dimasukkan ke query SQL, contoh: "45,48"
        // Jika tidak ada lokasi sama sekali, gunakan angka mustahil (-1) agar query tidak error.
        $locationIdsString = !empty($branchLocationIds) ? implode(',', $branchLocationIds) : '-1';

        // ambil produk + harga jual + stok TOTAL di cabang
        $query = DB::table('products as p')
            ->selectRaw('p.id, p.sku, p.name, p.category_id, p.uom_id, p.hpp, p.selling_price, p.notes')
            // 2. Ubah subquery untuk menjumlahkan stok dari SEMUA lokasi di cabang tersebut.
            ->addSelect(DB::raw("(SELECT IFNULL(SUM(sq.qty),0)
                                 FROM stock_quants sq
                                 WHERE sq.product_id = p.id
                                   AND sq.location_id IN ({$locationIdsString})) as stock"))
            ->where('p.is_active', 1);

        // ===== PERUBAHAN SELESAI =====


        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('p.sku', 'like', "%{$q}%")
                  ->orWhere('p.name', 'like', "%{$q}%");
            });
        }
        if (!empty($catId)) {
            $query->where('p.category_id', (int)$catId);
        }

        $query->orderBy('p.name');

        // pagination katalog
        $products = $query->paginate(20)->withQueryString();

        // hitung price dari selling_price (fallback ke hpp jika kosong)
        $products->getCollection()->transform(function ($row) {
            $row->price = $this->resolveSellingPrice($row->selling_price, $row->hpp);
            $row->stock = (int) floor((float) ($row->stock ?? 0));
            return $row;
        });

        // AJAX: return only catalog HTML
        if ($request->ajax() && $request->has('ajax_catalog')) {
            $html = view('kasir.partials._catalog', ['products' => $products])->render();
            return response()->json(['html' => $html]);
        }

        return view('kasir.index', [
            'categories' => $categories,
            'products'   => $products,
            'q'          => $q,
            'catId'      => $catId,
        ]);
    }


    /* =========================================================
     *  PAGE: POS SINGLE-PAGE (/kasir/pos)
     * =======================================================*/
    public function pos(Request $request)
    {
        $user     = Auth::user();
        $branchId = (int) ($user->default_branch_id ?? 0);

        $categories = DB::table('product_categories')->orderBy('name')->get();

        $q     = trim((string) $request->get('q', ''));
        $catId = $request->get('cat_id');

        // Location IDs for this branch
        $branchLocationIds = DB::table('stock_locations')
            ->where('branch_id', $branchId)
            ->pluck('id')
            ->all();
        $locationIdsString = !empty($branchLocationIds) ? implode(',', $branchLocationIds) : '-1';

        $query = DB::table('products as p')
            ->selectRaw('p.id, p.sku, p.name, p.category_id, p.uom_id, p.hpp, p.selling_price, p.notes')
            ->addSelect(DB::raw("(SELECT IFNULL(SUM(sq.qty),0)
                                 FROM stock_quants sq
                                 WHERE sq.product_id = p.id
                                   AND sq.location_id IN ({$locationIdsString})) as stock"))
            ->where('p.is_active', 1);

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('p.sku', 'like', "%{$q}%")
                  ->orWhere('p.name', 'like', "%{$q}%");
            });
        }
        if (!empty($catId)) {
            $query->where('p.category_id', (int)$catId);
        }

        $query->orderBy('p.name');
        $products = $query->paginate(20)->withQueryString();

        $products->getCollection()->transform(function ($row) {
            $row->price = $this->resolveSellingPrice($row->selling_price, $row->hpp);
            $row->stock = (int) floor((float) ($row->stock ?? 0));
            return $row;
        });

        // AJAX: return only catalog HTML for search/filter
        if ($request->ajax() && $request->has('ajax_catalog')) {
            $html = view('kasir.partials._pos_catalog', ['products' => $products])->render();
            return response()->json(['html' => $html]);
        }

        $cart       = $this->sessionCart();
        $payments   = $this->sessionPayments();
        $customerId = (int) session('pos.customer_id');
        [$total, $paid, $due] = $this->totals($cart, $payments);

        return view('kasir.pos', [
            'categories'       => $categories,
            'products'         => $products,
            'q'                => $q,
            'catId'            => $catId,
            'cart'             => $cart,
            'payments'         => $payments,
            'customerId'       => $customerId,
            'selectedCustomer' => $this->selectedCustomer($customerId),
            'total'            => $total,
            'paid'             => $paid,
            'due'              => $due,
        ]);
    }


    /* =========================================================
     *  PAGE: CHECKOUT (/kasir/checkout)
     * =======================================================*/
    public function checkout(Request $request)
{
    $cart       = $this->sessionCart();
    $payments   = $this->sessionPayments();
    $customerId = (int) session('pos.customer_id');

    // Pencarian pelanggan (?cq=...)
    $customerResults = [];
    if ($q = trim((string) $request->query('cq', ''))) {
        $customerResults = DB::table('customers')
            ->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('phone','like', "%{$q}%");
            })
            ->orderBy('name')->limit(30)->get();
    }

    [$total, $paid, $due] = $this->totals($cart, $payments);

    // Diskon per nota (session) — konsisten dengan renderCheckoutPartials().
    $discount = min(max(0.0, $this->sessionDiscount()), $total);
    $netTotal = round($total - $discount, 2);
    $due      = max(0.0, round($netTotal - $paid, 2));

    // AJAX customer search: return JSON with partial HTML
    if ($request->ajax() && $request->has('cq')) {
        $customerHtml = view('kasir.partials._customer', [
            'customerId'       => $customerId,
            'selectedCustomer' => $this->selectedCustomer($customerId),
            'customerResults'  => $customerResults,
        ])->render();
        return response()->json([
            'ok' => true,
            'html' => ['customer' => $customerHtml],
        ]);
    }

    return view('kasir.checkout', [
        'cart'             => array_values($cart),
        'payments'         => $payments,
        'customerId'       => $customerId,
        'selectedCustomer' => $this->selectedCustomer($customerId),
        'customerResults'  => $customerResults,
        'total'            => $total,
        'discount'         => $discount,
        'netTotal'         => $netTotal,
        'paid'             => $paid,
        'due'              => $due,
        'title'            => 'Checkout',
    ]);
}


    /* =========================================================
     *  CART ACTIONS
     * =======================================================*/
    public function cartAdd(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|min:1',
            'qty'        => 'required|integer|min:1',
        ]);

        $pid = (int) $request->input('product_id');
        $qty = (int) $request->input('qty');

        $user     = Auth::user();
        $branchId = (int) ($user->default_branch_id ?? 0);

        $product = DB::table('products')->where('id', $pid)->first();
        if (!$product) {
            return $this->jsonError('Produk tidak ditemukan.');
        }

        // Cegah jual diam-diam di harga modal: harga jual wajib diset.
        if (empty($product->selling_price) || (float) $product->selling_price <= 0) {
            return $this->jsonError("Harga jual untuk {$product->name} belum diset.");
        }

        $price = $this->resolveSellingPrice($product->selling_price, $product->hpp);

        // stok available
        $available = $this->availableStock($pid, $branchId);

        $cart = $this->sessionCart();
        $currentQty = isset($cart[$pid]) ? (int)$cart[$pid]['qty'] : 0;

        if (($currentQty + $qty) > $available) {
            return $this->jsonError("Stok tidak cukup. Tersedia: {$available}.");
        }

        $newQty = $currentQty + $qty;
        $cart[$pid] = [
            'product_id' => $pid,
            'sku'        => $product->sku,
            'name'       => $product->name,
            'price'      => $price,
            'qty'        => $newQty,
            'subtotal'   => round($price * $newQty, 2),
            'uom_id'     => (int)$product->uom_id,
        ];
        session(['pos.cart' => $cart]);

return $this->respond($request, [
        'ok' => true, 'message' => 'Item ditambahkan.', 'cart_count' => count($cart)
    ], 'kasir.checkout', 'Item ditambahkan.');
    }

    public function cartUpdate(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|min:1',
            'qty'        => 'required|integer|min:0', // 0 = hapus
        ]);

        $pid = (int)$request->input('product_id');
        $qty = (int)$request->input('qty');

        $user     = Auth::user();
        $branchId = (int) ($user->default_branch_id ?? 0);

        $cart = $this->sessionCart();

        if (!isset($cart[$pid])) {
            return $this->jsonError('Item tidak ada di keranjang.');
        }

        if ($qty === 0) {
            unset($cart[$pid]);
            session(['pos.cart' => $cart]);
            return $this->jsonOk('Item dihapus.', $this->renderCheckoutPartials($cart));
        }

        // cek stok
        $available = $this->availableStock($pid, $branchId);
        if ($qty > $available) {
            return $this->jsonError("Stok tidak cukup. Tersedia: {$available}.");
        }

        $row = $cart[$pid];
        $row['qty']      = $qty;
        $row['subtotal'] = round(((float)$row['price']) * $qty, 2);
        $cart[$pid]      = $row;

        session(['pos.cart' => $cart]);

        return $this->jsonOk('Qty diperbarui.', $this->renderCheckoutPartials($cart));
    }

    public function cartRemove(Request $request)
    {
        $request->validate(['product_id' => 'required|integer|min:1']);

        $pid  = (int)$request->input('product_id');
        $cart = $this->sessionCart();

        if (isset($cart[$pid])) {
            unset($cart[$pid]);
            session(['pos.cart' => $cart]);
        }

        return $this->jsonOk('Item dihapus.', $this->renderCheckoutPartials($cart));
    }

    public function cartClear()
    {
        session()->forget(['pos.cart', 'pos.discount']);
        return $this->jsonOk('Keranjang dikosongkan.', $this->renderCheckoutPartials([]));
    }

    /* =========================================================
     *  CUSTOMER
     * =======================================================*/

    /** Search customers by name/phone (AJAX) */
    public function customerSearch(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (strlen($q) < 1) {
            return response()->json(['ok' => true, 'customers' => []]);
        }

        $customers = DB::table('customers')
            ->where('name', 'like', "%{$q}%")
            ->orWhere('phone', 'like', "%{$q}%")
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn($c) => [
                'id'    => $c->id,
                'name'  => $c->name,
                'phone' => $c->phone,
            ]);

        return response()->json(['ok' => true, 'customers' => $customers]);
    }

   public function customerSelect(Request $request)
{
    // Jika tombol "Hapus Pilihan" ditekan
    if ($request->boolean('clear')) {
        session()->forget('pos.customer_id');
        return $this->jsonOk('Pilihan pelanggan dihapus.', $this->renderCheckoutPartials());
    }

    // Pilih pelanggan
    $request->validate(['customer_id' => 'required|integer|min:1']);
    $cid = (int) $request->input('customer_id');

    $exists = DB::table('customers')->where('id', $cid)->exists();
    if (!$exists) {
        return $this->jsonError('Pelanggan tidak ditemukan.', 404);
    }

    session(['pos.customer_id' => $cid]);

    return $this->jsonOk('Pelanggan dipilih.', $this->renderCheckoutPartials());
}


    public function customerQuick(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:160',
            'phone'   => 'nullable|string|max:30',
            'address' => 'nullable|string',
        ]);

        $id = DB::table('customers')->insertGetId([
            'name'    => $request->input('name'),
            'phone'   => $request->input('phone'),
            'address' => $request->input('address'),
        ]);

        session(['pos.customer_id' => $id]);

        return $this->jsonOk('Pelanggan ditambahkan.', $this->renderCheckoutPartials());
    }

    /* =========================================================
     *  PAYMENTS
     * =======================================================*/
    public function paymentAdd(Request $request)
    {
        $request->validate([
            'method' => 'required|in:CASH,CARD,QR,TRANSFER',
            'amount' => 'required|numeric|min:0.01',
            'ref_no' => 'nullable|string|max:80',
        ]);

        $pays = $this->sessionPayments();
        $pays[] = [
            'method' => $request->input('method'),
            'amount' => (float)$request->input('amount'),
            'ref_no' => trim((string)$request->input('ref_no')),
        ];
        session(['pos.payments' => $pays]);

        return $this->jsonOk('Pembayaran ditambahkan.', $this->renderCheckoutPartials());
    }

    public function paymentClear()
    {
        session()->forget('pos.payments');
        return $this->jsonOk('Pembayaran direset.', $this->renderCheckoutPartials());
    }

    /* =========================================================
     *  FINALIZE
     * =======================================================*/
public function finalize(Request $request)
{
    // Anti double-submit: kunci per kasir agar klik/retry bersamaan tak menduplikasi.
    $lock = Cache::lock('pos-finalize-'.(int) (Auth::id() ?? 0), 10);
    if (! $lock->get()) {
        return $this->jsonError('Transaksi sebelumnya masih diproses. Coba lagi sebentar.');
    }
    try {
        return $this->performFinalize($request);
    } finally {
        $lock->release();
    }
}

private function performFinalize(Request $request)
{
    $user     = Auth::user();
    $branchId = (int) ($user->default_branch_id ?? 0);

    $cart       = $this->sessionCart();
    $payments   = $this->sessionPayments();   // <— ini masih “tendered” (mis. cash 150rb)
    $customerId = session('pos.customer_id');

    if (empty($cart)) {
        return $this->jsonError('Keranjang masih kosong.');
    }

    [$grossTotal, $paid, $due] = $this->totals($cart, $payments);

    // Diskon per nota (level transaksi). Pendapatan diakui netto.
    $discount = min(max(0.0, (float) $request->input('discount', 0)), $grossTotal);
    $total    = round($grossTotal - $discount, 2);   // netto yang ditagihkan

    if (round($total - $paid, 2) > 0.0001) {
        return $this->jsonError('Pembayaran belum mencukupi.');
    }

    // ====== Alokasi pembayaran ke total (applied amount) + hitung kembalian ======
    $remaining    = $total;
    $appliedPays  = [];     // yang benar-benar dipakai & akan disimpan ke DB
    $change       = 0.0;    // kembalian tunai

    foreach ($payments as $p) {
        $amt = max(0.0, (float)($p['amount'] ?? 0));
        if ($remaining > 0 && $amt > 0) {
            $use = min($amt, $remaining);        // porsi yang dipakai
            if ($use > 0) {
                $appliedPays[] = [
                    'method' => $p['method'],
                    'amount' => $use,
                    'ref_no' => $p['ref_no'] ?? null,
                ];
                $remaining -= $use;
            }
            // kelebihan (overpay)
            $rest = $amt - $use;
            if ($rest > 0 && $p['method'] === 'CASH') {
                $change += $rest;                 // hanya cash yang memunculkan kembalian
            }
        } else {
            // total sudah tertutup; bila ada cash tambahan → semuanya kembalian
            if ($amt > 0 && $p['method'] === 'CASH') {
                $change += $amt;
            }
        }
    }
    // ============================================================================

    // Anti stok minus: cek ulang stok
    foreach ($cart as $pid => $row) {
        $available = $this->availableStock($pid, $branchId);
        if ($row['qty'] > $available) {
            return $this->jsonError("Stok {$row['name']} tidak cukup. Tersedia: {$available}.");
        }
    }

    $saleId = DB::transaction(function () use ($user, $branchId, $cart, $appliedPays, $customerId, $total, $discount, $change) {
        // Header
        $saleId = DB::table('pos_sales')->insertGetId([
            'branch_id'    => $branchId,
            'cashier_id'   => (int)$user->id,
            'customer_id'  => $customerId ?: null,
            'sale_datetime'=> now(),
            'status'       => 'PAID',
            'total'        => round($total, 2),
            'discount'     => round($discount, 2),
            // Simpan info kembalian sederhana di notes (bisa dibaca invoice riwayat)
            'notes'        => $change > 0 ? ('CHANGE='.$change) : null,
        ]);

        $availableLocId = $this->availableLocationId($branchId);

        // Lines + stock moves
        foreach ($cart as $pid => $row) {
            $product = DB::table('products')->where('id', $pid)->first();

            DB::table('pos_sale_lines')->insert([
                'pos_sale_id' => $saleId,
                'product_id'  => $pid,
                'uom_id'      => (int)($product->uom_id ?? 0),
                'qty'         => (int)$row['qty'],
                'price'       => (float)$row['price'],
                'discount'    => 0,
                'subtotal'    => round((float)$row['subtotal'], 2),
            ]);

            // stock move (keluar)
            DB::table('stock_moves')->insert([
                'product_id'       => $pid,
                'uom_id'           => (int)($product->uom_id ?? 0),
                'qty'              => (float)$row['qty'],
                'from_location_id' => $availableLocId ?: null,
                'to_location_id'   => null,
                'ref_type'         => 'POS',
                'ref_id'           => $saleId,
                'state'            => 'DONE',
                'created_by'       => (int)$user->id,
                'created_at'       => now(),
            ]);

            if ($availableLocId) {
                // Kunci baris stok lalu kurangi dengan guard agar tidak bisa negatif
                // (anti-oversell pada transaksi bersamaan).
                $quant = DB::table('stock_quants')
                    ->where('product_id', $pid)
                    ->where('location_id', $availableLocId)
                    ->lockForUpdate()
                    ->first();

                $current = (float) ($quant->qty ?? 0);
                if ($current + 0.0001 < (float) $row['qty']) {
                    abort(422, "Stok {$row['name']} tidak cukup di lokasi penjualan.");
                }

                DB::table('stock_quants')
                    ->where('product_id', $pid)
                    ->where('location_id', $availableLocId)
                    ->update(['qty' => round($current - (float) $row['qty'], 4)]);
            }
        }

        // Payments: simpan HANYA applied amount supaya kas & laporan akurat
        foreach ($appliedPays as $p) {
            DB::table('pos_payments')->insert([
                'pos_sale_id' => $saleId,
                'method'      => $p['method'],
                'amount'      => round((float)$p['amount'], 2),
                'ref_no'      => $p['ref_no'] ?: null,
            ]);
        }

        // === AKUNTANSI: Jurnal otomatis (dalam transaksi agar atomik dengan penjualan) ===
        \App\Services\AccountingService::journalPosSale($saleId, $total, $appliedPays[0]['method'] ?? 'CASH', $customerId, $branchId, $appliedPays);
        $cogs = \App\Services\AccountingService::calculateCogs($cart);
        if ($cogs > 0) {
            \App\Services\AccountingService::journalPosCogs($saleId, $cogs, $branchId);
        }

        return $saleId;
    });

    // Bersihkan session
    session()->forget(['pos.cart','pos.payments','pos.customer_id','pos.discount']);

    // Siapkan invoice HTML yang menampilkan kembalian
    $invoiceHtml = $this->renderInvoiceHtml($saleId);

    return $this->respond($request, [
        'ok'           => true,
        'message'      => 'Transaksi selesai.',
        'invoice_html' => $invoiceHtml,
        'html'         => $this->renderCheckoutPartials()
    ], 'kasir.checkout', 'Transaksi selesai.');
}

    /**
     * Retur penjualan: kembalikan stok + jurnal pembalik (revenue & HPP). Full-sale.
     */
    public function refund(Request $request, $id)
    {
        $user     = Auth::user();
        $branchId = (int) ($user->default_branch_id ?? 0);

        $sale = DB::table('pos_sales')->where('id', $id)->where('branch_id', $branchId)->first();
        if (!$sale) {
            return $this->jsonError('Penjualan tidak ditemukan.', 404);
        }
        if ($sale->status !== 'PAID') {
            return $this->jsonError('Hanya penjualan berstatus PAID yang dapat diretur.');
        }

        $reason = trim((string) $request->input('reason')) ?: 'Retur penjualan';
        $locId  = $this->availableLocationId($branchId);

        DB::transaction(function () use ($sale, $user, $branchId, $reason, $locId) {
            $refundId = (int) DB::table('pos_refunds')->insertGetId([
                'sale_id'     => $sale->id,
                'approved_by' => (int) $user->id,
                'reason'      => $reason,
                'created_at'  => now(),
            ]);

            $totalCost = 0.0;
            foreach (DB::table('pos_sale_lines')->where('pos_sale_id', $sale->id)->get() as $ln) {
                if ($locId) {
                    $quant = DB::table('stock_quants')
                        ->where('product_id', $ln->product_id)->where('location_id', $locId);
                    if ($quant->exists()) {
                        $quant->increment('qty', (float) $ln->qty);
                    } else {
                        DB::table('stock_quants')->insert([
                            'product_id' => $ln->product_id, 'location_id' => $locId, 'qty' => (float) $ln->qty,
                        ]);
                    }
                }
                DB::table('stock_moves')->insert([
                    'product_id'       => $ln->product_id, 'uom_id' => (int) $ln->uom_id, 'qty' => (float) $ln->qty,
                    'from_location_id' => null, 'to_location_id' => $locId ?: null,
                    'ref_type'         => 'POS', 'ref_id' => $refundId, 'state' => 'DONE',
                    'created_by'       => (int) $user->id, 'created_at' => now(),
                ]);
                $hpp = (float) DB::table('products')->where('id', $ln->product_id)->value('hpp');
                $totalCost += $hpp * (float) $ln->qty;
            }

            DB::table('pos_sales')->where('id', $sale->id)->update(['status' => 'REFUND']);

            \App\Services\AccountingService::journalPosRefund($refundId, (float) $sale->total, $branchId);
            \App\Services\AccountingService::journalReturnToInventory($refundId, $totalCost, $branchId);
        });

        return $this->respond($request, [
            'ok' => true, 'message' => 'Retur berhasil diproses.',
        ], 'kasir.history', 'Retur berhasil diproses.');
    }

    /** Halaman konfirmasi retur (server-rendered). */
    public function refundConfirm($id)
    {
        $user     = Auth::user();
        $branchId = (int) ($user->default_branch_id ?? 0);

        $sale = DB::table('pos_sales')->where('id', $id)->where('branch_id', $branchId)->first();
        if (!$sale) {
            return redirect()->route('kasir.history')->withErrors('Transaksi tidak ditemukan.');
        }
        if ($sale->status !== 'PAID') {
            return redirect()->route('kasir.history')->withErrors('Hanya penjualan berstatus PAID yang dapat diretur.');
        }

        $lines = DB::table('pos_sale_lines as l')
            ->join('products as p', 'p.id', '=', 'l.product_id')
            ->where('l.pos_sale_id', $sale->id)
            ->selectRaw('p.sku, p.name, l.qty, l.price, l.subtotal')
            ->get();

        return view('kasir.refund_confirm', ['sale' => $sale, 'lines' => $lines]);
    }

    /* =========================================================
     *  HELPERS
     * =======================================================*/

    private function resolveSellingPrice($sellingPrice, $hpp): float
    {
        // Prioritas: selling_price
        if (!empty($sellingPrice) && (float) $sellingPrice > 0) {
            return round((float) $sellingPrice, 2);
        }
        // Fallback: harga beli (hpp) tanpa markup
        return round((float) ($hpp ?? 0), 2);
    }

    private function availableLocationId(int $branchId): ?int
    {
        if ($branchId <= 0) return null;
        return DB::table('stock_locations')
            ->where('branch_id', $branchId)
            ->where('type', 'AVAILABLE')
            ->value('id');
    }

/**
 * [DIPERBARUI] Menghitung total stok suatu produk di SEMUA lokasi dalam satu cabang.
 */
private function availableStock(int $productId, int $branchId): int
{
    // 1. Ambil SEMUA ID lokasi yang dimiliki oleh cabang ini.
    $locationIds = DB::table('stock_locations')
                       ->where('branch_id', $branchId)
                       ->pluck('id');

    // Jika cabang tidak punya lokasi sama sekali, stok pasti 0.
    if ($locationIds->isEmpty()) {
        return 0;
    }

    // 2. Jumlahkan (SUM) kuantitas produk dari semua lokasi tersebut.
    $totalQty = DB::table('stock_quants')
        ->where('product_id', $productId)
        ->whereIn('location_id', $locationIds) // <-- Gunakan whereIn untuk mencari di banyak lokasi
        ->sum('qty');

    return (int) floor((float) ($totalQty ?? 0));
}


    private function sessionCart(): array
    {
        return (array) session('pos.cart', []);
    }

    private function sessionPayments(): array
    {
        return (array) session('pos.payments', []);
    }

    private function sessionDiscount(): float
    {
        return (float) session('pos.discount', 0);
    }

    /** hitung total, paid, due */
    private function totals(array $cart = null, array $payments = null): array
    {
        $cart     = $cart     ?? $this->sessionCart();
        $payments = $payments ?? $this->sessionPayments();

        $total = 0.0;
        foreach ($cart as $row) {
            $total += (float)($row['subtotal'] ?? 0);
        }
        $paid = 0.0;
        foreach ($payments as $p) {
            $paid += (float)($p['amount'] ?? 0);
        }
        $due = round($total - $paid, 2);
        return [round($total,2), round($paid,2), $due];
    }

    /** render partials untuk halaman checkout */
    // Render semua panel checkout (dipakai saat AJAX update)
private function renderCheckoutPartials(array $cart = null): array
{
    $cart        = $cart ?? $this->sessionCart();
    $payments    = $this->sessionPayments();
    $customerId  = (int) session('pos.customer_id');
    $customer    = $this->selectedCustomer($customerId);

    [$total, $paid, $due] = $this->totals($cart, $payments);

    // Diskon per nota (session, dibaca ulang di finalize dari body request — lihat performFinalize).
    $discount = min(max(0.0, $this->sessionDiscount()), $total);
    $netTotal = round($total - $discount, 2);
    $due      = max(0.0, round($netTotal - $paid, 2));

    return [
        'cart' => view('kasir.partials._cart', [
            'cart' => array_values($cart),
        ])->render(),

        'customer' => view('kasir.partials._customer', [
            'customerId'       => $customerId,
            'selectedCustomer' => $customer,  // << penting
            'customerResults'  => [],         // hasil cari hanya saat GET /kasir/checkout?cq=...
        ])->render(),

        'payments' => view('kasir.partials._payments', [
            'payments' => $payments,
        ])->render(),

        'summary' => view('kasir.partials._summary', [
            'cart'     => array_values($cart),
            'total'    => $total,
            'discount' => $discount,
            'netTotal' => $netTotal,
            'paid'     => $paid,
            'due'      => $due,
        ])->render(),
    ];
}

/**
 * Set diskon per nota (checkout legacy). Disimpan di session agar bertahan
 * lintas refresh AJAX cart/pembayaran, sama seperti pola cart/payments/customer.
 */
public function discountSet(Request $request)
{
    $cart     = $this->sessionCart();
    [$total]  = $this->totals($cart, $this->sessionPayments());
    $discount = min(max(0.0, (float) $request->input('discount', 0)), $total);

    session(['pos.discount' => $discount]);

    return $this->jsonOk('Diskon diterapkan.', $this->renderCheckoutPartials($cart));
}


    private function jsonOk(string $message, array $htmlPartials = [] )
    {
        return response()->json([
            'ok'      => true,
            'message' => $message,
            'html'    => $htmlPartials,
        ]);
    }// Tambah di PosController (helper kecil)
private function selectedCustomer(?int $customerId)
{
    return $customerId ? \DB::table('customers')->where('id', $customerId)->first() : null;
}


    private function jsonError(string $message, int $status = 422)
    {
        return response()->json([
            'ok'      => false,
            'message' => $message,
        ], $status);
    }
// Tambah di bawah helpers lain
private function respond(Request $req, array $jsonPayload, string $redirectRoute, string $flash = null)
{
    if ($req->expectsJson() || $req->ajax()) {
        return response()->json($jsonPayload);
    }
    if ($flash) session()->flash('success', $flash);
    return redirect()->route($redirectRoute);
}

    /** sangat ringkas — untuk modal cetak */
    private function renderInvoiceHtml(int $saleId): string
{
    $sale = DB::table('pos_sales as s')
        ->leftJoin('customers as c','c.id','=','s.customer_id')
        ->leftJoin('branches  as b','b.id','=','s.branch_id')
        ->where('s.id', $saleId)
        ->selectRaw('s.*, c.name as customer_name, c.phone as customer_phone, b.name as branch_name')
        ->first();

    $lines = DB::table('pos_sale_lines as l')
        ->join('products as p','p.id','=','l.product_id')
        ->where('l.pos_sale_id', $saleId)
        ->selectRaw('p.sku, p.name, l.qty, l.price, l.subtotal')
        ->get();

    // Pembayaran (sudah applied), kelompokkan per metode
    $pays = DB::table('pos_payments')
        ->select('method', DB::raw('SUM(amount) as amt'))
        ->where('pos_sale_id', $saleId)
        ->groupBy('method')->get();

    $sumPaid = 0.0;
    $payRows = '';
    foreach ($pays as $p) {
        $sumPaid += (float)$p->amt;
        $payRows .= '<div>'.e($p->method).': <b>Rp '.number_format((float)$p->amt,2,',','.').'</b></div>';
    }

    // Baca kembalian dari notes (jika ada)
    $change = 0.0;
    if (!empty($sale->notes) && preg_match('/CHANGE\s*=\s*([\d\.]+)/i', (string)$sale->notes, $m)) {
        $change = (float)$m[1];
    }

    $html  = '<div class="space-y-2">';
    $html .= '<div class="text-sm text-gray-600">No. Transaksi: <b>#'.$saleId.'</b></div>';
    $html .= '<div class="text-sm text-gray-600">Tanggal: '.now()->format('Y-m-d H:i').'</div>';
    $html .= '<div class="text-sm text-gray-600">Cabang: '.e($sale->branch_name ?? '-').'</div>';
    if ($sale->customer_name) {
        $html .= '<div class="text-sm text-gray-600">Pelanggan: '.e($sale->customer_name).' '.($sale->customer_phone ? ' • '.e($sale->customer_phone) : '').'</div>';
    }
    $html .= '</div><hr class="my-3">';

    $html .= '<table class="w-full text-sm"><thead><tr>'
          .  '<th class="text-left p-1">Produk</th>'
          .  '<th class="text-right p-1">Qty</th>'
          .  '<th class="text-right p-1">Harga</th>'
          .  '<th class="text-right p-1">Subtotal</th>'
          .  '</tr></thead><tbody>';
    foreach ($lines as $ln) {
        $html .= '<tr>'
              .  '<td class="p-1">'.e($ln->name).' <span class="text-gray-500">('.e($ln->sku).')</span></td>'
              .  '<td class="p-1 text-right">'.(int)$ln->qty.'</td>'
              .  '<td class="p-1 text-right">Rp '.number_format((float)$ln->price,2,',','.').'</td>'
              .  '<td class="p-1 text-right">Rp '.number_format((float)$ln->subtotal,2,',','.').'</td>'
              .  '</tr>';
    }
    $html .= '</tbody></table><hr class="my-3">';

    // Ringkasan bayar
    $html .= '<div class="text-sm">';
    $html .= $payRows ?: '<div>Pembayaran: —</div>';
    $html .= '<div>Total: <b>Rp '.number_format((float)$sale->total,2,',','.').'</b></div>';
    if ($change > 0) {
        $html .= '<div>Kembali: <b>Rp '.number_format($change,2,',','.').'</b></div>';
    }
    $html .= '</div>';

    return $html;
}
}
