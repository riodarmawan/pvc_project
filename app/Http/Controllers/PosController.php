<?php

namespace App\Http\Controllers;


    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Str;

    class PosController extends Controller
    {
        /** markup harga dari HPP (contoh 30%) */
        private const PRICE_MARKUP = 1;

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

    // ambil produk + harga (hpp*markup) + stok TOTAL di cabang
    $query = DB::table('products as p')
        ->selectRaw('p.id, p.sku, p.name, p.category_id, p.uom_id, p.notes')
        // 2. Ubah subquery untuk menjumlahkan stok dari SEMUA lokasi di cabang tersebut.
        ->addSelect(DB::raw("(SELECT IFNULL(SUM(sq.qty),0)
                            FROM stock_quants sq
                            WHERE sq.product_id = p.id
                            AND sq.location_id IN ({$locationIdsString})) as stock"))
        // ⭐ TAMBAHAN: Filter hanya produk yang aktif
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

    // hitung price dari notes->hpp
    $products->getCollection()->transform(function ($row) {
        $row->price = $this->priceFromNotes($row->notes);
        $row->stock = (int) floor((float) ($row->stock ?? 0));
        return $row;
    });

    return view('kasir.index', [
        'categories' => $categories,
        'products'   => $products,
        'q'          => $q,
        'catId'      => $catId,
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

        return view('kasir.checkout', [
            'cart'             => array_values($cart),
            'payments'         => $payments,
            'customerId'       => $customerId,
            'selectedCustomer' => $this->selectedCustomer($customerId), // << penting
            'customerResults'  => $customerResults,
            'total'            => $total,
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

    // harga dari notes->hpp * markup
    $price = $this->priceFromNotes($product->notes);

    // stok available
    $available = $this->availableStock($pid, $branchId);

    $cart = $this->sessionCart();
    $currentQty = isset($cart[$pid]) ? (int)$cart[$pid]['qty'] : 0;

    if (($currentQty + $qty) > $available) {
        return $this->jsonError("Stok tidak cukup. Tersedia: {$available}.");
    }

    $newQty = $currentQty + $qty;
    
    // SIMPLIFIED: No discount logic
    $cart[$pid] = [
        'product_id' => $pid,
        'sku'        => $product->sku,
        'name'       => $product->name,
        'price'      => $price,
        'qty'        => $newQty,
        'subtotal'   => round($price * $newQty, 2), // Simple calculation
        'uom_id'     => (int)$product->uom_id,
        'is_custom_price' => false, // Default: original price
    ];
    session(['pos.cart' => $cart]);

    return $this->respond($request, [
        'ok'       => true,
        'message'  => 'Item ditambahkan.',
        'redirect' => route('kasir.home')
    ], 'kasir.home', 'Item ditambahkan.');
}

// Tambahkan di dalam class PosController
public function discountUpdate(Request $request)
{
    $request->validate([
        'discount' => 'nullable|numeric|min:0',
    ]);

    $discount = (float)$request->input('discount', 0);
    
    // Ambil total belanja untuk validasi
    $cart = $this->sessionCart();
    $grossTotal = 0;
    foreach ($cart as $row) {
        $grossTotal += (float)($row['subtotal'] ?? 0);
    }

    if ($discount > $grossTotal) {
        return $this->jsonError("Diskon tidak boleh lebih besar dari total belanja (Rp " . number_format($grossTotal, 0, ',', '.') . ").");
    }

    // Simpan total discount ke session
    session(['pos.total_discount' => $discount]);

    return $this->jsonOk('Diskon diperbarui.', $this->renderCheckoutPartials());
}

public function cartUpdate(Request $request)
{
    $request->validate([
        'product_id' => 'required|integer|min:1',
        'qty'        => 'required|integer|min:0', // 0 berarti hapus
        'price'      => 'nullable|numeric|min:0', // NEW: editable price
    ]);

    $pid = (int)$request->input('product_id');
    $qty = (int)$request->input('qty');
    $customPrice = $request->input('price'); // NEW

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

    $available = $this->availableStock($pid, $branchId);
    if ($qty > $available) {
        return $this->jsonError("Stok tidak cukup. Tersedia: {$available}.");
    }

    $row = $cart[$pid];
    $row['qty'] = $qty;
    
    // NEW: Handle custom price
    if ($customPrice !== null && $customPrice !== '') {
        $row['price'] = (float)$customPrice;
        $row['is_custom_price'] = true;
    }
    
    // SIMPLIFIED: Subtotal = price × qty (no discount)
    $row['subtotal'] = round((float)$row['price'] * $qty, 2);

    $cart[$pid] = $row;
    session(['pos.cart' => $cart]);

    return $this->jsonOk('Item diperbarui.', $this->renderCheckoutPartials($cart));
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
            session()->forget('pos.cart');
            return $this->jsonOk('Keranjang dikosongkan.', $this->renderCheckoutPartials([]));
        }

        /* =========================================================
        *  CUSTOMER
        * =======================================================*/
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
        'notes'  => 'nullable|string|max:500', // TAMBAH VALIDASI INI
    ]);

    // TAMBAH: Simpan catatan ke session
    $notes = trim((string)$request->input('notes', ''));
    if ($notes !== '') {
        session(['pos.notes' => $notes]);
    }

    $pays = $this->sessionPayments();
    $pays[] = [
        'method' => $request->input('method'),
        'amount' => (float)$request->input('amount'),
        'ref_no' => trim((string)$request->input('ref_no')),
    ];
    session(['pos.payments' => $pays]);

    return $this->jsonOk('Pembayaran ditambahkan dan catatan disimpan.', $this->renderCheckoutPartials());
}


        public function paymentClear()
        {
            session()->forget('pos.payments');
            return $this->jsonOk('Pembayaran direset.', $this->renderCheckoutPartials());
        }
public function notesUpdate(Request $request)
{
    $request->validate([
        'notes' => 'nullable|string|max:500',
    ]);

    $notes = trim((string)$request->input('notes', ''));
    session(['pos.notes' => $notes]);

    return $this->jsonOk('Catatan disimpan.', $this->renderCheckoutPartials());
}

        /* =========================================================
        *  FINALIZE
        * =======================================================*/
    public function finalize(Request $request)
    {
        $user     = Auth::user();
        $branchId = (int) ($user->default_branch_id ?? 0);

        $cart       = $this->sessionCart();
        $payments   = $this->sessionPayments();   // <— ini masih “tendered” (mis. cash 150rb)
        $customerId = session('pos.customer_id');
        $notes      = session('pos.notes', '');

        if (empty($cart)) {
            return $this->jsonError('Keranjang masih kosong.');
        }

        [$total, $paid, $due] = $this->totals($cart, $payments);
        if ($due > 0.0001) {
            return $this->jsonError('Pembayaran belum mencukupi.');
        }

        // ====== Alokasi pembayaran ke total (applied amount) + hitung kembalian ======
    $remaining    = $total;
    $appliedPays  = [];
    $change       = 0.0;

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

    $saleId = DB::transaction(function () use ($user, $branchId, $cart, $appliedPays, $customerId, $total, $change, $notes) {
        // UBAH: Simpan kembalian di kolom change_amount dan notes di kolom notes
$saleId = DB::table('pos_sales')->insertGetId([
    'branch_id'     => $branchId,
    'cashier_id'    => (int)$user->id,
    'customer_id'   => $customerId ?: null,
    'sale_datetime' => now(),
    'status'        => 'PAID',
    'total'         => round($total, 2),
    // REMOVED: 'discount' => 0,
    'change_amount' => round($change, 2),
    'notes'         => trim($notes) ?: null,
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
        'price'       => (float)$row['price'], // Custom price will be saved
        // REMOVED: 'discount' => 0,
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
                    DB::table('stock_quants')
                        ->where('product_id', $pid)
                        ->where('location_id', $availableLocId)
                        ->decrement('qty', (float)$row['qty']);
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

            return $saleId;
        });

        // Bersihkan session
// Bersihkan session (tambahkan pos.total_discount)
session()->forget(['pos.cart','pos.payments','pos.customer_id','pos.notes']);


        // Siapkan invoice HTML yang menampilkan kembalian
return $this->respond($request, [
    'ok'      => true,
    'message' => 'Transaksi selesai.',
    'redirect' => route('kasir.history')
], 'kasir.history', 'Transaksi selesai.');

    }

        /* =========================================================
        *  (OPSIONAL) SCAN ADD — tetap disediakan agar route lama tidak error
        *  Tidak dipakai di UI baru; fungsi ini hanya proxy ke cartAdd.
        * =======================================================*/
        public function scanAdd(Request $request)
        {
            $request->validate([
                'term' => 'required|string|max:160',
                'qty'  => 'required|integer|min:1',
            ]);

            $term = trim($request->input('term'));
            $qty  = (int)$request->input('qty');

            $product = DB::table('products')
                ->where('sku', $term)
                ->orWhere('barcode', $term)
                ->orWhere('name','like',"%{$term}%")
                ->orderBy('sku')->first();

            if (!$product) return $this->jsonError('Produk tidak ditemukan.');

            // forward ke cartAdd
            $fake = new Request([
                'product_id' => (int)$product->id,
                'qty'        => $qty,
            ]);
            return $this->cartAdd($fake);
        }

        /* =========================================================
        *  HELPERS
        * =======================================================*/

        private function priceFromNotes($notes): float
        {
            $hpp = 0.0;
            if ($notes && preg_match('/hpp\s*:\s*([\d\.]+)/i', (string)$notes, $m)) {
                $hpp = (float)$m[1];
            }
            $price = $hpp * self::PRICE_MARKUP;
            return round($price ?: 0.0, 2);
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

        /** hitung total, paid, due */
/** hitung total, paid, due */
private function totals(array $cart = null, array $payments = null): array
{
    $cart     = $cart     ?? $this->sessionCart();
    $payments = $payments ?? $this->sessionPayments();

    // SIMPLIFIED: Just sum all subtotals (no discount logic)
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



private function renderCheckoutPartials(array $cart = null): array
{
    $cart        = $cart ?? $this->sessionCart();
    $payments    = $this->sessionPayments();
    $customerId  = (int) session('pos.customer_id');
    $customer    = $this->selectedCustomer($customerId);
    $notes       = session('pos.notes', '');

    [$total, $paid, $due] = $this->totals($cart, $payments);

    return [
        'cart' => view('kasir.partials._cart', [
            'cart' => array_values($cart),
        ])->render(),

        'customer' => view('kasir.partials._customer', [
            'customerId'       => $customerId,
            'selectedCustomer' => $customer,
            'customerResults'  => [],
        ])->render(),

        'payments' => view('kasir.partials._payments', [
            'payments' => $payments,
            'notes'    => $notes,
        ])->render(),

        'summary' => view('kasir.partials._summary', [
            'cart'  => array_values($cart),
            'total' => $total,
            'paid'  => $paid,
            'due'   => $due,
        ])->render(),
    ];
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
