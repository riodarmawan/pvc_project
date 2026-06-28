<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class ProjectController extends Controller
{
    /* =======================
     | Session Keys & Helpers
     |=======================*/
    protected function sKey($key)
    {
        return "project.$key";
    }

    protected function branchId()
    {
        return (int) (auth()->user()->default_branch_id ?? 0);
    }

protected function activeCart()
{
    $default = [
        'materials' => [],
        'leftovers' => [],
    ];

    $cart = session($this->sKey('cart'), $default);
    if (!is_array($cart)) $cart = $default;
    if (!isset($cart['materials']) || !is_array($cart['materials'])) $cart['materials'] = [];
    if (!isset($cart['leftovers']) || !is_array($cart['leftovers'])) $cart['leftovers'] = [];

    foreach ($cart['materials'] as &$m) {
        $m['row_id']           = $m['row_id']           ?? (string) \Illuminate\Support\Str::uuid();
        $m['product_id']       = (int)   ($m['product_id']       ?? 0);
        $m['name']             = (string)($m['name']             ?? '');
        $m['uom_id']           = (int)   ($m['uom_id']           ?? 0);
        $m['uom']              = $m['uom']               ?? null;
        $m['qty']              = (float) ($m['qty']              ?? 0);
        $m['from_location_id'] = isset($m['from_location_id']) ? (int)$m['from_location_id'] : null;
        $m['price']            = isset($m['price']) ? (float)$m['price'] : 0.0; // kalau sudah pakai HPP
    }
    unset($m);

    foreach ($cart['leftovers'] as &$l) {
        $l['row_id']         = $l['row_id']         ?? (string) \Illuminate\Support\Str::uuid();
        $l['piece_id']       = (int)   ($l['piece_id']       ?? 0);
        $l['product_id']     = (int)   ($l['product_id']     ?? 0);
        $l['name']           = (string)($l['name']           ?? '');
        $l['used_length_m']  = (float) ($l['used_length_m']  ?? 0);
        $l['available_m']    = isset($l['available_m']) ? (float)$l['available_m'] : null;
        $l['price']          = isset($l['price']) ? (float)$l['price'] : 0.0; // ← HARGA per meter
    }
    unset($l);

    session([$this->sKey('cart') => $cart]);
    return $cart;
}





    protected function activeServices()
    {
        return session($this->sKey('services'), []); // [row_id, name, price]
    }

    protected function activeCustomer()
    {
        return session($this->sKey('customer'), null); // ['id'=>..,'name'=>..,'phone'=>..,'address'=>..]
    }

    protected function saveCart($cart)
    {
        session([$this->sKey('cart') => $cart]);
    }

    protected function saveServices($services)
    {
        session([$this->sKey('services') => $services]);
    }

    protected function saveCustomer($cust)
    {
        session([$this->sKey('customer') => $cust]);
    }

    protected function clearAllSession()
    {
        session()->forget([
            $this->sKey('cart'),
            $this->sKey('services'),
            $this->sKey('customer'),
        ]);
    }protected function getProductPrice(int $productId, int $branchId): float
{
    // 1) Coba tabel product_prices with branch
    $price = DB::table('product_prices')
        ->where('product_id', $productId)
        ->where(function($q) use ($branchId) {
            $q->where('branch_id', $branchId)->orWhereNull('branch_id');
        })
        ->orderByRaw('branch_id IS NULL') // utamakan yang by-branch
        ->value('price');

    if ($price !== null) return (float)$price;

    // 2) Coba kolom umum di products (pakai yang ada)
    foreach (['selling_price','sell_price','retail_price','price'] as $col) {
        $val = DB::table('products')->where('id', $productId)->value($col);
        if ($val !== null) return (float)$val;
    }
    // 3) Default 0
    return 0.0;
}

// ====== HELPER: hitung total jasa + material dari session ======
// Di dalam file: app/Http/Controllers/ProjectController.php

protected function computeCartTotalsFromSession(array $cart, array $services, int $branchId): array
{
    // === Materials (bagian ini kemungkinan sudah benar)
    $materials      = $cart['materials'] ?? [];
    $totalMaterials = 0.0;
    foreach ($materials as $m) {
        $qty   = (float)($m['qty'] ?? 0);
        $price = (float)($m['price'] ?? 0);
        if ($price <= 0 && !empty($m['product_id'])) {
            $price = $this->productHpp((int)$m['product_id']);
        }
        if ($qty > 0) {
            $totalMaterials += $qty * max(0, $price);
        }
    }

    // =================================================================
    //            PERBAIKAN UTAMA ADA DI BAGIAN INI
    // =================================================================
    $leftovers      = $cart['leftovers'] ?? [];
    $totalLeftovers = 0.0;
    foreach ($leftovers as $r) {
        $len = (float)($r['used_length_m'] ?? 0);
        // Ganti logika ini agar mengenali 'price_m' sebagai prioritas
        $pr  = (float)($r['price_m'] ?? ($r['price'] ?? 0)); 
        
        if ($len > 0 && $pr >= 0) {
            $totalLeftovers += $len * $pr;
        }
    }
    // =================================================================
    //                  AKHIR BAGIAN PERBAIKAN
    // =================================================================

    // === Services (bagian ini kemungkinan sudah benar)
    $totalServices = 0.0;
    foreach ($services as $s) {
        $totalServices += (float)($s['price'] ?? 0);
    }

    return [
        'total_materials' => $totalMaterials,
        'total_leftovers' => $totalLeftovers, // <-- Nilai ini sekarang akan benar
        'total_services'  => $totalServices,
        'grand_total'     => $totalMaterials + $totalLeftovers + $totalServices,
    ];
}



protected function syncSaleLinesFromProjectAll(int $saleId, int $projectId, int $branchId): void
{
    DB::table('pos_sale_lines')->where('pos_sale_id', $saleId)->delete();

    // 1) Jasa dari project_services (qty = 1, product = SRV-GEN)
    $serviceProduct = $this->ensureServiceProduct();
    $services = DB::table('project_services')->where('project_id', $projectId)->orderBy('id')->get();
    foreach ($services as $s) {
        $price = (float)$s->price;
        DB::table('pos_sale_lines')->insert([
            'pos_sale_id' => $saleId,
            'product_id'  => (int)$serviceProduct->id,
            'uom_id'      => (int)$serviceProduct->uom_id,
            'qty'         => 1,
            'price'       => $price,
            'discount'    => 0,
            'subtotal'    => $price,
        ]);
    }

    // 2) Material terpakai (prioritas dari stock_moves PROJECT_ISSUE)
    $mats = DB::table('stock_moves as sm')
        ->join('products as p','p.id','=','sm.product_id')
        ->where('sm.ref_type','PROJECT_ISSUE')
        ->where('sm.ref_id', $projectId)
        ->where('sm.state', 'DONE')
        ->where('sm.qty','>',0)
        ->groupBy('sm.product_id','sm.uom_id')
        ->selectRaw('sm.product_id, sm.uom_id, SUM(sm.qty) as qty')
        ->get();

    // fallback kalau belum ada move → pakai project_boms
    if ($mats->isEmpty()) {
        $mats = DB::table('project_boms as b')
            ->groupBy('b.product_id','b.uom_id')
            ->where('b.project_id', $projectId)
            ->selectRaw('b.product_id, b.uom_id, SUM(b.qty_planned) as qty')
            ->get();
    }

    foreach ($mats as $m) {
        $pid = (int)$m->product_id;
        $uom = (int)$m->uom_id;
        $qty = (float)$m->qty;
        if ($pid <= 0 || $qty <= 0) continue;

        $unit = $this->getProductPrice($pid, $branchId);
        $sub  = $unit * $qty;

        DB::table('pos_sale_lines')->insert([
            'pos_sale_id' => $saleId,
            'product_id'  => $pid,
            'uom_id'      => $uom,
            'qty'         => $qty,
            'price'       => $unit,
            'discount'    => 0,
            'subtotal'    => $sub,
        ]);
    }
}
/**
 * Ambil angka HPP dari kolom products.hpp (fallback ke notes untuk legacy).
 */
protected function parseHppFromNotes(?string $notes, ?float $hppColumn = null): float
{
    // Prioritas: kolom hpp
    if (!empty($hppColumn) && (float) $hppColumn > 0) {
        return (float) $hppColumn;
    }
    // Fallback: parse dari notes
    if (!$notes) return 0.0;
    if (preg_match('/hpp\s*:\s*([0-9\.\,]+)/i', $notes, $m)) {
        $onlyDigits = preg_replace('/[^\d]/', '', $m[1]); // buang titik/koma/simbol lain
        return (float) $onlyDigits;
    }
    return 0.0;
}

/** Ambil HPP per product_id */
protected function productHpp(int $productId): float
{
    $product = DB::table('products')->where('id', $productId)->select('hpp', 'notes')->first();
    if (!$product) return 0.0;
    return $this->parseHppFromNotes($product->notes, $product->hpp);
}

    /* =======================
     | Index & Create
     |=======================*/
    public function index(Request $req)
    {
        $branchId = $this->branchId();

        $projects = DB::table('projects')
            ->leftJoin('customers','customers.id','=','projects.customer_id')
            ->where('projects.branch_id',$branchId)
            ->orderByDesc('projects.id')
            ->select('projects.*','customers.name as customer_name')
            ->paginate(15);

        return view('projects.index', [
            'title'    => 'Projek Terpasang',
            'projects' => $projects,
        ]);
    }

// Di dalam file: app/Http/Controllers/ProjectController.php

public function create(Request $req)
{
    $branchId   = $this->branchId();
    $availableLocId = DB::table('stock_locations')
        ->where('branch_id', $branchId)->where('type','AVAILABLE')
        ->value('id');

$products = DB::table('products as p')
    ->join('stock_quants as q', function($j) use ($availableLocId) {
        $j->on('q.product_id','=','p.id');
        if ($availableLocId) $j->where('q.location_id','=',$availableLocId);
        else $j->whereRaw('1=0');
    })
    ->where('p.is_active',1)
    ->where('q.qty', '>', 0)
    ->orderBy('p.name')
    ->limit(300)
    ->selectRaw('p.*, q.qty as stock_qty')
    ->get()
    ->map(function ($p) {
        $p->hpp = $this->parseHppFromNotes($p->notes ?? '', $p->hpp ?? null);
        return $p;
    });


    $customers = DB::table('customers')->orderByDesc('id')->limit(50)->get();

    $cart     = $this->activeCart();
    $services = $this->activeServices();
    $customer = $this->activeCustomer();
    $totals   = $this->computeCartTotalsFromSession($cart, $services, $branchId);

    // =================================================================
    //                    PERUBAHAN UTAMA DI SINI
    // =================================================================

    // 1. Ambil ID semua potongan sisa yang sudah ada di cart
    $inCartPieceIds = collect($cart['leftovers'] ?? [])->pluck('piece_id')->all();

    // 2. Modifikasi query untuk MENGECUALIKAN ID yang ada di cart
// Di dalam ProjectController.php -> create()

$availableLeftovers = DB::table('leftover_pieces as lp')
    ->join('products as p','p.id','=','lp.product_id')
    ->whereNull('lp.consumed_at')
    ->where(function($q){ $q->whereNull('lp.reserved_project_id'); })
    ->where('lp.branch_id',$branchId)
    ->where('lp.condition', 'GOOD') // <-- PERUBAHAN DI SINI
    ->when(!empty($inCartPieceIds), function ($query) use ($inCartPieceIds) {
        return $query->whereNotIn('lp.id', $inCartPieceIds);
    })
    ->select('lp.*','p.name as product_name','p.sku')
    ->orderByDesc('lp.id')
    ->limit(100)->get();

        
    // =================================================================
    //                  AKHIR BAGIAN PERUBAHAN
    // =================================================================

    return view('projects.create', [
        'title'     => 'Buat Projek Terpasang',
        'products'  => $products,
        'customers' => $customers,
        'cart'      => $cart,
        'services'  => $services,
        'customer'  => $customer,
        'leftovers' => $availableLeftovers, // <-- Variabel ini sekarang sudah terfilter
        'sum'       => $totals,
    ]);
}



    /**
 * Pastikan UOM 'PCS' ada, kembalikan id-nya.
 */
protected function ensureUomPcsId(): int
{
    $uom = DB::table('uoms')->where('code', 'PCS')->first();
    if (!$uom) {
        $id = DB::table('uoms')->insertGetId([
            'code' => 'PCS',
            'name' => 'Pieces',
        ]);
        return (int)$id;
    }
    return (int)$uom->id;
}

/**
 * Pastikan kategori 'SERV' (Services) ada, kembalikan id-nya.
 */
protected function ensureServiceCategoryId(): int
{
    $cat = DB::table('product_categories')->where('code', 'SERV')->first();
    if (!$cat) {
        $id = DB::table('product_categories')->insertGetId([
            'code' => 'SERV',
            'name' => 'Services',
            'parent_id' => null,
        ]);
        return (int)$id;
    }
    return (int)$cat->id;
}

/**
 * Pastikan produk placeholder Jasa ada (sku: SRV-GEN), kembalikan row-nya.
 */
protected function ensureServiceProduct(): object
{
    $p = DB::table('products')->where('sku', 'SRV-GEN')->first();
    if ($p) return $p;

    $uomId = $this->ensureUomPcsId();
    $catId = $this->ensureServiceCategoryId();

    $id = DB::table('products')->insertGetId([
        'sku'         => 'SRV-GEN',
        'name'        => 'JASA / SERVICE',
        'category_id' => $catId,
        'uom_id'      => $uomId,
        'material'    => null,
        'series'      => null,
        'pattern_code'=> null,
        'finish'      => null,
        'length_cm'   => null,
        'width_mm'    => null,
        'thickness_mm'=> null,
        'barcode'     => null,
        'is_active'   => 1,
        'notes'       => 'Placeholder service line untuk billing proyek',
    ]);

    return DB::table('products')->where('id', $id)->first();
}

/**
 * Ambil (atau buat) POS sale untuk proyek tertentu.
 * Membuat SALE DRAFT + sinkron baris dari project_services jika belum ada.
 */
protected function getOrCreateProjectSale(int $projectId)
{
    $sale = DB::table('pos_sales')->where('project_id', $projectId)->first();
    if ($sale) return $sale;

    $project = DB::table('projects')->where('id', $projectId)->first();
    abort_if(!$project, 404, 'Project tidak ditemukan');

    return DB::transaction(function () use ($project) {
        $now = Carbon::now();

        $saleId = DB::table('pos_sales')->insertGetId([
            'branch_id'     => (int)$project->branch_id,
            'cashier_id'    => (int)auth()->id(),
            'customer_id'   => $project->customer_id,
            'sale_datetime' => $now,
            'status'        => 'DRAFT',
            'total'         => 0,
            'notes'         => 'Billing Proyek #' . $project->code,
            'project_id'    => (int)$project->id,
        ]);

        // ✅ sinkron jasa + material
        $this->syncSaleLinesFromProject($saleId, (int)$project->id, (int)$project->branch_id);
        $this->refreshSaleTotal($saleId);

        // Akrual billing: akui pendapatan + piutang saat tagihan diterbitkan.
        $srvId          = (int) $this->ensureServiceProduct()->id;
        $total          = (float) DB::table('pos_sales')->where('id', $saleId)->value('total');
        $serviceRevenue = (float) DB::table('pos_sale_lines')
            ->where('pos_sale_id', $saleId)->where('product_id', $srvId)->sum('subtotal');
        $goodsRevenue   = max(0.0, $total - $serviceRevenue);
        \App\Services\AccountingService::journalProjectBilling($saleId, $goodsRevenue, $serviceRevenue, $project->customer_id, (int) $project->branch_id);

        return DB::table('pos_sales')->where('id', $saleId)->first();
    });
}


/**
 * Hapus & isi ulang baris POS dari `project_services`.
 */
protected function syncServiceLinesFromProject(int $saleId, int $serviceProductId, int $uomId, int $projectId): void
{
    // Hapus baris lama untuk sale ini
    DB::table('pos_sale_lines')->where('pos_sale_id', $saleId)->delete();

    // Ambil services
    $services = DB::table('project_services')->where('project_id', $projectId)->orderBy('id')->get();

    // Insert baris per service (qty=1)
    foreach ($services as $s) {
        $price = (float)$s->price;
        DB::table('pos_sale_lines')->insert([
            'pos_sale_id' => $saleId,
            'product_id'  => $serviceProductId,
            'uom_id'      => $uomId,
            'qty'         => 1,
            'price'       => $price,
            'discount'    => 0,
            'subtotal'    => $price,
        ]);
    }
}
/**
 * Ambil harga jual produk untuk keperluan billing proyek.
 * Urutan sumber:
 * 1) product_prices (branch-specific lalu default/null)
 * 2) kolom products.selling_price
 * 3) kolom products.hpp (fallback)
 * 4) parse "hpp:xxxxx" dari products.notes (legacy fallback). 
 *    (Jika tidak ada semuanya → 0)
 */
protected function getProductSellPrice(int $productId, ?int $branchId = null): float
{
    // 1) Tabel product_prices (jika ada)
    if (Schema::hasTable('product_prices')) {
        $q = DB::table('product_prices')->where('product_id', $productId);

        if ($branchId) {
            $row = (clone $q)->where('branch_id', $branchId)->first();
            if ($row && isset($row->price)) {
                return (float)$row->price;
            }
        }
        $row = DB::table('product_prices')
            ->where('product_id', $productId)
            ->whereNull('branch_id')
            ->first();
        if ($row && isset($row->price)) {
            return (float)$row->price;
        }
    }

    // 2) Kolom products.selling_price
    $product = DB::table('products')->select('selling_price', 'hpp', 'notes')->where('id', $productId)->first();
    if ($product && !empty($product->selling_price) && (float) $product->selling_price > 0) {
        return (float) $product->selling_price;
    }

    // 3) Kolom products.hpp (fallback)
    if ($product && !empty($product->hpp) && (float) $product->hpp > 0) {
        return (float) $product->hpp;
    }

    // 4) Fallback: parse "hpp:xxxxx" dari notes (legacy)
    $notes = (string) ($product->notes ?? '');
    if ($notes && preg_match('/hpp\s*:\s*([0-9]+)/i', $notes, $m)) {
        return (float)$m[1];
    }

    return 0.0;
}
/**
 * Sinkronkan SELURUH baris tagihan untuk sale proyek:
 * - Hapus semua baris lama
 * - Tambahkan baris JASA dari project_services (qty=1)
 * - Tambahkan baris MATERIAL dari project_boms (qty=qty_planned, harga = harga jual)
 */
// ============ HELPERS UNTUK INVOICE ============
/** Pastikan UOM 'M' (meter) ada. */
protected function ensureUomMeterId(): int
{
    $u = DB::table('uoms')->where('code', 'M')->first();
    if ($u) return (int) $u->id;

    return (int) DB::table('uoms')->insertGetId([
        'code' => 'M',
        'name' => 'Meter',
    ]);
}

/** Ambil HPP dari products.hpp (fallback ke notes untuk legacy) */
protected function parseHpp(int $productId): float
{
    $product = DB::table('products')->where('id', $productId)->select('hpp', 'notes')->first();
    if (!$product) return 0.0;
    
    // Prioritas: kolom hpp
    if (!empty($product->hpp) && (float) $product->hpp > 0) {
        return (float) $product->hpp;
    }
    
    // Fallback: parse dari notes
    $notes = (string) ($product->notes ?? '');
    if ($notes === '') return 0.0;
    if (preg_match('/hpp\s*:\s*([0-9\.]+)/i', $notes, $m)) {
        return (float) str_replace('.', '', $m[1]);
    }
    return 0.0;
}

/**
 * Sinkronkan baris penjualan proyek ke tabel pos_sale_lines:
 * - Jasa: dari project_services (qty=1, price=price)
 * - Material: dari project_boms (qty=qty_planned, price=HPP produk)
 * - Leftover terpakai: dari leftover_piece_consumptions (qty=used_m, uom=M, price=price_per_m jika ada)
 *
 * Baris lama di pos_sale_lines sale ini akan DIHAPUS lalu diisi ulang.
 */
protected function syncSaleLinesFromProject(int $saleId, int $projectId, ?int $branchId = null): void
{
    $meterUom = $this->ensureUomMeterId();

    DB::transaction(function () use ($saleId, $projectId, $meterUom) {
        // bersihkan dulu
        DB::table('pos_sale_lines')->where('pos_sale_id', $saleId)->delete();

        $now = now();

        // 1) SERVICES
        $services = DB::table('project_services')
            ->where('project_id', $projectId)
            ->orderBy('id')
            ->get();

        // produk placeholder jasa
        $srvProduct = $this->ensureServiceProduct();

        foreach ($services as $s) {
            $price = (float) $s->price;
            DB::table('pos_sale_lines')->insert([
                'pos_sale_id' => $saleId,
                'product_id'  => (int) $srvProduct->id,
                'uom_id'      => (int) $srvProduct->uom_id,
                'qty'         => 1,
                'price'       => $price,
                'discount'    => 0,
                'subtotal'    => $price,
            ]);
        }

        // 2) MATERIAL (BOM terpakai)
        $boms = DB::table('project_boms as b')
            ->join('products as p','p.id','=','b.product_id')
            ->where('b.project_id', $projectId)
            ->select('b.product_id','b.uom_id','b.qty_planned')
            ->orderBy('b.id')
            ->get();

        foreach ($boms as $b) {
            $qty   = (float) $b->qty_planned;
            if ($qty <= 0) continue;

            $price = $this->parseHpp((int)$b->product_id); // HPP dari notes
            $sub   = $qty * $price;

            DB::table('pos_sale_lines')->insert([
                'pos_sale_id' => $saleId,
                'product_id'  => (int) $b->product_id,
                'uom_id'      => (int) $b->uom_id,
                'qty'         => $qty,
                'price'       => $price,
                'discount'    => 0,
                'subtotal'    => $sub,
            ]);
        }

        // 3) LEFTOVER YANG DIPAKAI
        //   kolom price_per_m OPSIONAL. Jika tidak ada → 0.
        $hasPricePerM = false;
        try {
            // uji cepat ada kolom
            DB::select('SELECT price_per_m FROM leftover_piece_consumptions WHERE 1=0');
            $hasPricePerM = true;
        } catch (\Throwable $e) { /* kolom tidak ada */ }

        $cons = DB::table('leftover_piece_consumptions as c')
            ->join('leftover_pieces as lp','lp.id','=','c.piece_id')
            ->join('products as p','p.id','=','lp.product_id')
            ->where('c.project_id', $projectId)
            ->select('lp.product_id','c.used_m',
                DB::raw($hasPricePerM ? 'c.price_per_m' : '0 as price_per_m'))
            ->orderBy('c.id')
            ->get();

        foreach ($cons as $row) {
            $qtyM   = (float) $row->used_m;
            if ($qtyM <= 0) continue;

            $priceM = (float) $row->price_per_m;
            $sub    = $qtyM * $priceM;

            DB::table('pos_sale_lines')->insert([
                'pos_sale_id' => $saleId,
                'product_id'  => (int) $row->product_id,
                'uom_id'      => $meterUom,   // meter
                'qty'         => $qtyM,
                'price'       => $priceM,
                'discount'    => 0,
                'subtotal'    => $sub,
            ]);
        }

        // refresh total
        $this->refreshSaleTotal($saleId);
    });
}


/**
 * Recalculate total di pos_sales dari pos_sale_lines.
 */
protected function refreshSaleTotal(int $saleId): void
{
    $total = (float) DB::table('pos_sale_lines')
        ->where('pos_sale_id', $saleId)
        ->sum('subtotal');

    DB::table('pos_sales')->where('id', $saleId)->update(['total' => $total]);
}

/**
 * Hitung total pembayaran yang sudah masuk untuk sale tertentu.
 */
protected function salePaidAmount(int $saleId): float
{
    return (float) DB::table('pos_payments')
        ->where('pos_sale_id', $saleId)
        ->sum('amount');
}

    /* =======================
     | Customer actions
     |=======================*/
    public function customerSelect(Request $req)
    {
        $data = $req->validate([
            'customer_id' => 'required|integer|exists:customers,id',
        ]);

        $c = DB::table('customers')->where('id',$data['customer_id'])->first();
        $this->saveCustomer([
            'id'      => $c->id,
            'name'    => $c->name,
            'phone'   => $c->phone,
            'address' => $c->address,
        ]);

        return back()->with('ok','Customer dipilih.');
    }

    public function customerQuick(Request $req)
    {
        $v = $req->validate([
            'name'    => 'required|string|max:160',
            'phone'   => 'nullable|string|max:30',
            'address' => 'nullable|string',
        ]);

        $id = DB::table('customers')->insertGetId([
            'name'       => $v['name'],
            'phone'      => $v['phone'] ?? null,
            'address'    => $v['address'] ?? null,
        ]);

        $this->saveCustomer([
            'id'      => $id,
            'name'    => $v['name'],
            'phone'   => $v['phone'] ?? null,
            'address' => $v['address'] ?? null,
        ]);

        return back()->with('ok','Customer baru dibuat & dipilih.');
    }

    /* =======================
     | Cart actions (materials, leftovers, services)
     |=======================*/
public function cartAdd(Request $req)
{
    $type = $req->input('type'); // 'material' | 'leftover' | 'service'
    $cart = $this->activeCart();
    $services = $this->activeServices();

    if ($type === 'material') {
        $v = $req->validate([
            'product_id' => 'required|integer|exists:products,id',
            'uom_id'     => 'required|integer|exists:uoms,id',
            'qty'        => 'required|numeric|min:0.001',
        ]);

        $p = DB::table('products')->select('id','name','hpp','notes')->where('id',$v['product_id'])->first();
        $u = DB::table('uoms')->select('id','code')->where('id',$v['uom_id'])->first();
        $hpp = (float) $this->productHpp((int)$v['product_id']);
        // ===== HPP WAJIB DIISI KE CART =====
        $price = $this->productHpp((int)$p->id);
        if ($price <= 0 && !empty($p->notes)) {
            // fallback: parse "hpp:33000" di kolom notes (legacy)
            if (preg_match('/hpp\s*:\s*([0-9\.\,]+)/i', $p->notes, $m)) {
                $num = str_replace(['.', ' '], '', $m[1]); // ribuan
                $num = str_replace(',', '.', $num);        // desimal
                $price = (float)$num;
            }
        }

        $cart['materials'][] = [
            'row_id'     => (string) \Illuminate\Support\Str::uuid(),
            'product_id' => (int) $v['product_id'],
            'name'       => (string) $p->name,
            'uom_id'     => (int) $v['uom_id'],
            'uom'        => (string) $u->code,
            'qty'        => (float) $v['qty'],
            'price'      => $this->getProductSellPrice((int) $v['product_id'], $this->branchId()), // harga jual (margin)
        ];
        $this->saveCart($cart);

        return back()->with('ok','Material ditambahkan.');
    }

    if ($type === 'leftover') {
        // Simpan ke session saja; konsumsi & penandaan habis dilakukan saat finalize
        $v = $req->validate([
            'piece_id'       => 'required|integer|exists:leftover_pieces,id',
            'used_length_m'  => 'required|numeric|min:0.001',
            'price'          => 'nullable|numeric|min:0',    // harga per meter
        ]);

        $piece = DB::table('leftover_pieces as lp')
            ->join('products as p','p.id','=','lp.product_id')
            ->where('lp.id',$v['piece_id'])
            ->select('lp.*','p.name as product_name')
            ->first();

        if (!$piece)                     return back()->withErrors('Potongan sisa tidak ditemukan.');
        if ($piece->consumed_at)         return back()->withErrors('Potongan sisa sudah digunakan.');
        if (!is_null($piece->reserved_project_id) && (int)$piece->reserved_project_id !== 0) {
            return back()->withErrors('Potongan sisa sedang di-reserve project lain.');
        }
        if ((float)$v['used_length_m'] - (float)$piece->length_m > 0.0001) {
            return back()->withErrors('Panjang pakai melebihi panjang potongan.');
        }

        $cart['leftovers'][] = [
            'row_id'        => (string) \Illuminate\Support\Str::uuid(),
            'piece_id'      => (int) $piece->id,
            'product_id'    => (int) $piece->product_id,
            'name'          => (string) $piece->product_name,
            'available_m'   => (float) $piece->length_m,
            'used_length_m' => (float) $v['used_length_m'],
            'price_m'       => isset($v['price']) ? (float)$v['price'] : 0.0,  // ikut ke ringkasan & invoice
        ];

        $this->saveCart($cart);
        return back()->with('ok','Pemakaian potongan sisa ditambahkan.');
    }

    if ($type === 'service') {
        $v = $req->validate([
            'name'  => 'required|string|max:160',
            'price' => 'required|numeric|min:0',
        ]);

        $services[] = [
            'row_id' => (string) \Illuminate\Support\Str::uuid(),
            'name'   => trim($v['name']),
            'price'  => (float) $v['price'],
        ];
        $this->saveServices($services);
        return back()->with('ok','Service ditambahkan.');
    }

    return back()->withErrors('Tipe penambahan tidak dikenali.');
}




public function cartUpdate(Request $req)
{
    $kind = $req->input('kind');

    if ($kind === 'material') {
        $v = $req->validate([
            'row_id' => 'required|string',
            'qty'    => 'required|numeric|min:0.001',
        ]);
        $cart = $this->activeCart();
        foreach ($cart['materials'] as &$r) {
            if ($r['row_id'] === $v['row_id']) {
                $r['qty'] = (float) $v['qty'];
                // kalau price belum ada (legacy), isi ulang dari HPP
                if (!isset($r['price']) || $r['price'] <= 0) {
                    $r['price'] = $this->productHpp((int)$r['product_id']);
                }
                $this->saveCart($cart);
                return back()->with('ok','Jumlah material diperbarui.');
            }
        }
        return back()->withErrors('Baris material tidak ditemukan.');
    }

if ($kind === 'leftover') {
    $v = $req->validate([
        'row_id'        => 'required|string',
        'used_length_m' => 'required|numeric|min:0.001',
        // terima dua-duanya agar kompatibel dengan form lama
        'price_m'       => 'nullable|numeric|min:0',
        'price'         => 'nullable|numeric|min:0',
    ]);

    $newPrice = array_key_exists('price_m', $v)
        ? $v['price_m']
        : ($v['price'] ?? null);

    $cart = $this->activeCart();

    foreach ($cart['leftovers'] as &$r) {
        if ($r['row_id'] === $v['row_id']) {
            if ($v['used_length_m'] > (float)($r['available_m'] ?? 0) + 0.0001) {
                return back()->withErrors('Panjang pakai melebihi sisa tersedia.');
            }

            $r['used_length_m'] = (float)$v['used_length_m'];

            if ($newPrice !== null) {
                $r['price_m'] = (float)$newPrice; // simpan konsisten sebagai price_m
                unset($r['price']);               // bersihkan key lama kalau ada
            }

            $this->saveCart($cart);
            return back()->with('ok', 'Panjang/harga potongan diperbarui.');
        }
    }

    return back()->withErrors('Baris potongan sisa tidak ditemukan.');
}


    if ($kind === 'service') {
        $v = $req->validate([
            'row_id' => 'required|string',
            'name'   => 'required|string|max:160',
            'price'  => 'required|numeric|min:0',
        ]);
        $services = $this->activeServices();
        foreach ($services as &$r) {
            if ($r['row_id'] === $v['row_id']) {
                $r['name']  = trim($v['name']);
                $r['price'] = (float) $v['price'];
                $this->saveServices($services);
                return back()->with('ok','Service diperbarui.');
            }
        }
        return back()->withErrors('Baris service tidak ditemukan.');
    }

    return back()->withErrors('Kind tidak dikenali.');
}



public function cartRemove(Request $req)
{
    $kind = $req->input('kind'); // 'material' | 'leftover' | 'service'
    $rowId = $req->input('row_id');

    if ($kind === 'material') {
        $cart = $this->activeCart();
        $cart['materials'] = array_values(array_filter(
            $cart['materials'],
            fn($r) => $r['row_id'] !== $rowId
        ));
        $this->saveCart($cart);
        return back()->with('ok','Material dihapus dari cart.');
    }

    if ($kind === 'leftover') {
        $cart = $this->activeCart();
        // tidak perlu sentuh DB; cukup keluarkan dari cart
        $cart['leftovers'] = array_values(array_filter(
            $cart['leftovers'],
            fn($r) => $r['row_id'] !== $rowId
        ));
        $this->saveCart($cart);
        return back()->with('ok','Pemakaian potongan sisa dibatalkan.');
    }

    if ($kind === 'service') {
        $services = $this->activeServices();
        $services = array_values(array_filter($services, fn($r)=>$r['row_id']!==$rowId));
        $this->saveServices($services);
        return back()->with('ok','Service dihapus.');
    }

    return back()->withErrors('Kind tidak dikenali.');
}


public function cartClear()
{
    // tidak ada reservasi DB, cukup bersihkan session
    $this->saveCart(['materials'=>[],'leftovers'=>[]]);
    $this->saveServices([]);
    return back()->with('ok','Cart dikosongkan.');
}

/**
 * Tambah pembayaran ke POS sale proyek (CASH/CARD/QR/TRANSFER).
 * Request: method, amount, ref_no (opsional).
 */
public function billPayAdd(Request $req, int $projectId)
{
// BENAR
$v = $req->validate([
    'method' => 'required|in:CASH,CARD,QR,TRANSFER',
    'amount' => 'required|numeric|min:0.01',
    'ref_no' => 'nullable|string|max:80',
]);

    $sale = $this->getOrCreateProjectSale($projectId);

DB::transaction(function () use ($sale, $v) { // $v adalah hasil validasi
    // [+] Hitung sisa tagihan terlebih dahulu
    $paidBefore = $this->salePaidAmount((int)$sale->id);
    $totalBill  = (float)$sale->total;
    $dueAmount  = max(0, $totalBill - $paidBefore);

    $tenderedAmount = (float)$v['amount'];
    
    // [+] Tentukan jumlah yang akan dicatat
    $appliedAmount  = min($tenderedAmount, $dueAmount);

    if ($appliedAmount > 0) {
        DB::table('pos_payments')->insert([
            'pos_sale_id' => $sale->id,
            'method'      => $v['method'],
            'amount'      => $appliedAmount,
            'ref_no'      => $v['ref_no'] ?? null,
        ]);

        // === AKUNTANSI: penerimaan kas (dalam transaksi agar atomik) ===
        \App\Services\AccountingService::journalCollectPayment(
            $appliedAmount, $sale->customer_id, $sale->branch_id, $v['method']
        );
    }

    // [+] Hitung total bayar TERBARU setelah insert
    $paidAfter = $paidBefore + $appliedAmount;

    // Tutup sale jika sudah lunas
    if ($paidAfter + 0.0001 >= $totalBill) {
        DB::table('pos_sales')->where('id', $sale->id)->update(['status' => 'PAID']);
        DB::table('projects')->where('id', (int)$sale->project_id)->update(['status' => 'IN_PROGRESS']);
    }
});

            return back()->with('ok', 'Pembayaran ditambahkan.');
}

/**
 * Hapus semua pembayaran untuk billing project.
 */
public function billClear(int $projectId)
{
    $sale = $this->getOrCreateProjectSale($projectId);

    DB::table('pos_payments')->where('pos_sale_id', $sale->id)->delete();
    DB::table('pos_sales')->where('id', $sale->id)->update(['status' => 'DRAFT']);
    DB::table('projects')->where('id', $projectId)->update(['status' => 'READY_TO_BILL']);

    return back()->with('ok', 'Semua pembayaran proyek dihapus.');
}

/**
 * Ambil material terpakai untuk proyek, ter-aggregate per produk + uom.
 * Sumber utama: stock_moves (ref_type=PROJECT_ISSUE, state DONE).
 * Jika belum ada move (kasus legacy), fallback ke project_boms (qty_planned).
 *
 * @return array<array{product_id:int,uom_id:int,qty:float,name:string,sku:string,uom:string}>
 */
// Di dalam file: app/Http/Controllers/ProjectController.php

protected function fetchProjectMaterials(int $projectId): array
{
    // 1. Ambil material yang dikeluarkan dari GUDANG UTAMA (qty > 0)
    $fromStock = DB::table('stock_moves as sm')
        ->join('products as p', 'p.id', '=', 'sm.product_id')
        ->join('uoms as u', 'u.id', '=', 'sm.uom_id')
        ->where('sm.ref_type', 'PROJECT_ISSUE')
        ->where('sm.ref_id', $projectId)
        ->where('sm.qty', '>', 0) // <-- Hanya dari stok utama
        ->select(
            'p.id as product_id', 'p.name', 'p.sku', 'p.length_cm',
            'u.id as uom_id', 'u.code as uom', 'sm.qty'
        )
        ->get()
        ->map(function ($r) {
            // Jika produk punya panjang, display-nya adalah total meter. Jika tidak, qty biasa.
            $isLengthBased = $r->length_cm !== null && (float)$r->length_cm > 0;
            if ($isLengthBased) {
                $displayQty = ((float)$r->length_cm / 100.0) * (float)$r->qty;
                $displayUom = 'm';
            } else {
                $displayQty = (float)$r->qty;
                $displayUom = (string)$r->uom;
            }
            return [
                'product_id' => (int)$r->product_id,
                'uom_id'     => (int)$r->uom_id,
                'name'       => (string)$r->name,
                'sku'        => (string)$r->sku,
                'length_cm'  => $isLengthBased ? (float)$r->length_cm : null,
                'display_qty_issued' => $displayQty,
                'display_uom'        => $displayUom,
            ];
        });

    // 2. Ambil material yang dipakai dari POTONGAN SISA
    $fromLeftovers = DB::table('leftover_piece_consumptions as c')
        ->join('leftover_pieces as lp', 'lp.id', '=', 'c.piece_id')
        ->join('products as p', 'p.id', '=', 'lp.product_id')
        ->join('uoms as u', 'u.id', '=', 'p.uom_id')
        ->where('c.project_id', $projectId)
        ->select(
            'p.id as product_id', 'p.name', 'p.sku', 'p.length_cm',
            'u.id as uom_id', 'u.code as uom', 'c.used_m'
        )
        ->get()
        ->map(function ($r) {
            return [
                'product_id' => (int)$r->product_id,
                'uom_id'     => (int)$r->uom_id,
                'name'       => (string)$r->name,
                'sku'        => (string)$r->sku,
                'length_cm'  => (float)$r->length_cm,
                'display_qty_issued' => (float)$r->used_m, // Display-nya pasti meter
                'display_uom'        => 'm',
            ];
        });
        
    // 3. Gabungkan keduanya dan kembalikan sebagai array
    return $fromStock->merge($fromLeftovers)->toArray();
}

public function billShow(int $projectId)
{
    $project = DB::table('projects')->where('id', $projectId)->first();
    abort_if(!$project, 404);

    // Pastikan sale ada
    $sale = $this->getOrCreateProjectSale($projectId);

    // ✅ sinkron jasa + material setiap buka halaman
    $this->syncSaleLinesFromProject((int)$sale->id, $projectId, (int)$project->branch_id);
    $this->refreshSaleTotal((int)$sale->id);

    // Reload data terkini
    $sale     = DB::table('pos_sales')->where('id', $sale->id)->first();
    $lines    = DB::table('pos_sale_lines')->where('pos_sale_id', $sale->id)->orderBy('id')->get();
    $payments = DB::table('pos_payments')->where('pos_sale_id', $sale->id)->orderBy('id')->get();
    $paid     = $this->salePaidAmount((int)$sale->id);
    $due      = max(0, ((float)$sale->total - $paid));

    return view('projects.bill', [
        'project'  => $project,
        'sale'     => $sale,
        'lines'    => $lines,
        'payments' => $payments,
        'paid'     => $paid,
        'due'      => $due,
        'title'    => 'Pembayaran Proyek — '.$project->code,
    ]);
}



    /* =======================
     | Finalize project
     |=======================*/
public function finalize(Request $req)
{
    // Anti double-submit: kunci per user agar klik/retry bersamaan tak menduplikasi proyek.
    $lock = \Illuminate\Support\Facades\Cache::lock('project-finalize-'.(int) (auth()->id() ?? 0), 10);
    if (! $lock->get()) {
        return back()->withErrors('Proyek sebelumnya masih diproses. Coba lagi sebentar.')->withInput();
    }
    try {
        return $this->performFinalize($req);
    } finally {
        $lock->release();
    }
}

private function performFinalize(Request $req)
{
    $data = $req->validate([
        'title'       => 'required|string|max:160',
        'customer_id' => 'nullable|integer|exists:customers,id',
        'pay_method'  => 'required|in:CASH,CARD,QR,TRANSFER',
        'pay_amount'  => 'required|numeric|min:0',
        'pay_ref'     => 'nullable|string|max:80',
    ]);

    $user     = auth()->user();
    $uid      = (int) ($user->id ?? 0);
    $branchId = (int) ($user->default_branch_id ?? 0);
    abort_if(!$branchId, 422, 'User tidak punya default branch.');

    $cart         = $this->activeCart();
    $services     = $this->activeServices();
    $useLeftovers = $cart['leftovers'] ?? [];

    $totals = $this->computeCartTotalsFromSession($cart, $services, $branchId);
    $grand  = (float) $totals['grand_total'];

    if ((float)$data['pay_amount'] + 0.0001 < $grand) {
        return back()->withErrors('Jumlah pembayaran kurang dari total (Rp '.number_format($grand,0,',','.').').')->withInput();
    }

    // generate kode proyek
    $codeBase = 'PRJ-'.now()->format('ymd').'-';
    $seq=1; do { $code=$codeBase.str_pad((string)$seq,4,'0',STR_PAD_LEFT); $seq++; }
    while (DB::table('projects')->where('code',$code)->exists());

    // lokasi default — gunakan AVAILABLE (stok awal dari seeder)
    $storeLocId = (int) DB::table('stock_locations')
        ->where('branch_id',$branchId)->where('type','AVAILABLE')->value('id');
    abort_if(!$storeLocId, 422, 'Lokasi AVAILABLE untuk branch belum ada.');

    $projectId = DB::transaction(function () use ($req,$uid,$branchId,$code,$cart,$services,$storeLocId,$data,$useLeftovers) {
        $now = now();

        // 1) PROJECT
        $projectId = (int) DB::table('projects')->insertGetId([
            'branch_id'   => $branchId,
            'customer_id' => $req->input('customer_id'),
            'code'        => $code,
            'title'       => $req->input('title'),
            'status'      => 'READY_TO_BILL',
            'created_by'  => $uid,
            'created_at'  => $now,
        ]);

        // 2) ISSUE MATERIAL + BOM
        foreach (($cart['materials'] ?? []) as $row) {
            $pid = (int)($row['product_id'] ?? 0);
            $uom = (int)($row['uom_id'] ?? 0);
            $qty = (float)($row['qty'] ?? 0);
            if ($pid <= 0 || $uom <= 0 || $qty <= 0) continue;

            DB::table('project_boms')->insert([
                'project_id'=>$projectId,'product_id'=>$pid,'uom_id'=>$uom,'qty_planned'=>$qty,
            ]);

            $quant = DB::table('stock_quants')
                ->where('product_id',$pid)->where('location_id',$storeLocId)
                ->lockForUpdate()->first();

            $newQty = ((float)($quant->qty ?? 0)) - $qty;
            if ($newQty < -0.0001) abort(422, "Stok tidak cukup untuk produk ID {$pid} di lokasi {$storeLocId}.");

            if ($quant) DB::table('stock_quants')->where('id',$quant->id)->update(['qty'=>$newQty]);
            else DB::table('stock_quants')->insert(['product_id'=>$pid,'location_id'=>$storeLocId,'qty'=>max(0,$newQty)]);

            DB::table('stock_moves')->insert([
                'product_id'=>$pid,'uom_id'=>$uom,'qty'=>$qty,
                'from_location_id'=>$storeLocId,'to_location_id'=>null,
                'ref_type'=>'PROJECT_ISSUE','ref_id'=>$projectId,
                'state'=>'DONE','created_by'=>$uid,'created_at'=>$now,
            ]);
        }

        // 2b) KONSUMSI POTONGAN SISa + JEJAK STOCK_MOVE (qty=0)
        foreach ($useLeftovers as $r) {
            $pieceId = (int) ($r['piece_id'] ?? 0);
            $used    = (float) $r['used_length_m'];
            $avail   = (float) ($r['available_m'] ?? 0);
            if ($pieceId <= 0 || $used <= 0) continue;

            $piece = DB::table('leftover_pieces')->where('id', $pieceId)->lockForUpdate()->first();
            if (!$piece) abort(422, 'Potongan sisa tidak ditemukan.');
            if ($piece->consumed_at) abort(422, 'Potongan sisa sudah habis.');
            if (!is_null($piece->reserved_project_id) && (int)$piece->reserved_project_id !== 0) {
                abort(422, 'Potongan sisa sedang di-reserve proyek lain.');
            }

            $lenPiece = $avail > 0 ? (float)$avail : (float)$piece->length_m;
            if ($used - $lenPiece > 0.0001) abort(422, 'Panjang pakai melebihi panjang potongan.');

            // catat konsumsi parsial / total
            DB::table('leftover_piece_consumptions')->insert([
                'piece_id'   => $pieceId,
                'project_id' => $projectId,
                'used_m'     => $used,
                'created_at' => $now,
            ]);

            if ($used + 0.0001 >= $lenPiece) {
                // Habis → tandai kepemilikan project + consumed
                DB::table('leftover_pieces')->where('id', $pieceId)->update([
                    'reserved_project_id' => $projectId,
                    'consumed_at'         => $now,
                ]);
            }

            DB::table('stock_moves')->insert([
                'product_id'       => (int)$piece->product_id,
                'uom_id'           => DB::table('products')->where('id',$piece->product_id)->value('uom_id') ?? 1,
                'qty'              => 0,
                'from_location_id' => null,
                'to_location_id'   => null,
                'ref_type'         => 'PROJECT_ISSUE',
                'ref_id'           => $projectId,
                'state'            => 'DONE',
                'created_by'       => $uid,
                'created_at'       => $now,
            ]);
        }

        // 3) SIMPAN SERVICES
        foreach (($services ?? []) as $s) {
            $nm = trim((string)($s['name'] ?? ''));
            $pr = (float)($s['price'] ?? 0);
            if ($nm === '' && $pr <= 0) continue;
            DB::table('project_services')->insert([
                'project_id'=>$projectId,'name'=>$nm ?: 'Service','price'=>$pr,'created_at'=>$now,
            ]);
        }

        // 4) POS HEADER
        $saleId = DB::table('pos_sales')->insertGetId([
            'branch_id'=>$branchId,'cashier_id'=>(int)auth()->id(),'customer_id'=>$req->input('customer_id'),
            'sale_datetime'=>$now,'status'=>'DRAFT','total'=>0,'notes'=>'Billing Proyek #'.$code,'project_id'=>$projectId,
        ]);

        // 4a) JASA → produk SRV-GEN
        $srvProd = $this->ensureServiceProduct();
        $serviceRows = DB::table('project_services')->where('project_id',$projectId)->orderBy('id')->get();
        foreach ($serviceRows as $s) {
            $price = (float)$s->price;
            DB::table('pos_sale_lines')->insert([
                'pos_sale_id'=>$saleId,'product_id'=>(int)$srvProd->id,'uom_id'=>(int)$srvProd->uom_id,
                'qty'=>1,'price'=>$price,'discount'=>0,'subtotal'=>$price,
            ]);
        }

        // 4b) POTONGAN SISA → masuk ke rincian (qty = meter, price = harga per m)
        foreach ($useLeftovers as $r) {
            $piece = DB::table('leftover_pieces as lp')
                ->join('products as p','p.id','=','lp.product_id')
                ->where('lp.id',$r['piece_id'])
                ->select('lp.product_id','p.uom_id')
                ->first();
            if (!$piece) continue;

            $qty   = (float)($r['used_length_m'] ?? 0);
            $price = (float)($r['price_m'] ?? 0);
            if ($qty <= 0 || $price < 0) continue;

            DB::table('pos_sale_lines')->insert([
                'pos_sale_id'=>$saleId,
                'product_id' =>(int)$piece->product_id,
                'uom_id'     =>(int)$piece->uom_id,
                'qty'        =>$qty,
                'price'      =>$price,
                'discount'   =>0,
                'subtotal'   =>$qty * $price,
            ]);
        }

        // 4c) MATERIAL (HPP)
        foreach (($cart['materials'] ?? []) as $m) {
            $pid = (int)($m['product_id'] ?? 0);
            $uom = (int)($m['uom_id'] ?? 0);
            $qty = (float)($m['qty'] ?? 0);
            if ($pid <= 0 || $uom <= 0 || $qty <= 0) continue;

            $price = isset($m['price']) ? (float)$m['price'] : $this->productHpp($pid);
            DB::table('pos_sale_lines')->insert([
                'pos_sale_id'=>$saleId,'product_id'=>$pid,'uom_id'=>$uom,
                'qty'=>$qty,'price'=>$price,'discount'=>0,'subtotal'=>$qty * $price,
            ]);
        }

        // 4d) TOTAL
        $this->refreshSaleTotal($saleId);

        // 5) PAYMENT
// [+] LOGIKA PEMBAYARAN BARU
$tenderedAmount = (float)$req->input('pay_amount');
$actualTotal = (float) DB::table('pos_sales')->where('id', $saleId)->value('total'); // Ambil total tagihan
$appliedAmount = min($tenderedAmount, $actualTotal); // Tentukan jumlah yang akan dicatat
$change = $tenderedAmount - $appliedAmount; // Hitung kembalian

// 5) PAYMENT (Simpan jumlah yang benar)
DB::table('pos_payments')->insert([
    'pos_sale_id' => $saleId,
    'method'      => $req->input('pay_method'),
    'amount'      => $appliedAmount, // <-- PERBAIKAN: Simpan jumlah yang seharusnya
    'ref_no'      => $req->input('pay_ref'),
]);

// [+] Simpan catatan kembalian (opsional tapi bagus)
if ($change > 0 && $req->input('pay_method') === 'CASH') {
    DB::table('pos_sales')->where('id', $saleId)->update(['notes' => 'Billing Proyek #'.$code . ' | CHANGE='.$change]);
}

        // 6) STATUS POS + PROYEK
        $paid  = $this->salePaidAmount($saleId);
        $total = (float) DB::table('pos_sales')->where('id',$saleId)->value('total');

        if ($paid + 0.0001 >= $total) {
            DB::table('pos_sales')->where('id',$saleId)->update(['status'=>'PAID']);
            DB::table('projects')->where('id',$projectId)->update(['status' => 'IN_PROGRESS']);
        }

        // 7) AUDIT
        DB::table('audit_logs')->insert([
            'event'=>'PROJECT_FINALIZE','user_id'=>$uid,'ref_type'=>'PROJECT','ref_id'=>$projectId,
            'payload'=>json_encode(['cart'=>$cart,'services'=>$services]),'created_at'=>$now,
        ]);

        // 8) AKUNTANSI (akrual, di dalam transaksi agar atomik dengan billing)
        $serviceRevenue = 0.0;
        foreach (($services ?? []) as $s) {
            $serviceRevenue += (float) ($s['price'] ?? 0);
        }
        $goodsRevenue = max(0.0, $total - $serviceRevenue);
        \App\Services\AccountingService::journalProjectBilling($saleId, $goodsRevenue, $serviceRevenue, $data['customer_id'] ?? null, $branchId);

        if ($appliedAmount > 0) {
            \App\Services\AccountingService::journalCollectPayment($appliedAmount, $data['customer_id'] ?? null, $branchId, $req->input('pay_method'));
        }

        // COGS: material di HPP + konsumsi leftover
        $cogs = 0.0;
        foreach (($cart['materials'] ?? []) as $m) {
            $pid = (int) ($m['product_id'] ?? 0);
            $qty = (float) ($m['qty'] ?? 0);
            if ($pid > 0 && $qty > 0) {
                $cogs += $qty * $this->productHpp($pid);
            }
        }
        foreach ($useLeftovers as $r) {
            $pieceId = (int) ($r['piece_id'] ?? 0);
            $used    = (float) ($r['used_length_m'] ?? 0);
            if ($pieceId <= 0 || $used <= 0) continue;
            $costPerM = (float) DB::table('leftover_pieces')->where('id', $pieceId)->value('cost_per_m') ?? 0;
            if ($costPerM > 0) {
                $cogs += $used * $costPerM;
            }
        }
        if ($cogs > 0) {
            \App\Services\AccountingService::journalPosCogs($saleId, $cogs, $branchId);
        }

        return $projectId;
    });

    // selesai
    $this->clearAllSession();
    return redirect()->route('projects.print.invoice.byproject', $projectId)
        ->with('ok', 'Proyek dibuat & sudah dibayar.');
}





    /* =======================
     | Return / Sisa
     |=======================*/



// Di dalam file: app/Http/Controllers/ProjectController.php

public function returnForm($id)
{
    $project = DB::table('projects')
        ->leftJoin('customers','customers.id','=','projects.customer_id')
        ->where('projects.id',$id)
        ->select('projects.*','customers.name as customer_name')
        ->first();

    if (!$project) abort(404);

    if ($project->status === 'DONE') {
        return redirect()
            ->route('projects.index')
            ->withErrors('Proyek sudah DONE. Form pengembalian/sisa terkunci.');
    }

    // Mengambil data material yang benar-benar dikeluarkan untuk proyek ini
    $issuedMaterials = $this->fetchProjectMaterials((int)$id);

    // Variabel ini tetap ada jika Anda punya logika lain yang membutuhkannya
    $products = DB::table('products')->where('is_active',1)->orderBy('name')->get();

    // PASTIKAN ANDA MENGIRIM 'issuedMaterials' KE VIEW
    return view('projects.return', [
        'title'           => 'Pengembalian / Sisa Projek',
        'project'         => $project,
        'products'        => $products,
        'issuedMaterials' => $issuedMaterials, // <-- INI BAGIAN PENTINGNYA
    ]);
}
// Di dalam ProjectController.php

public function returnProcess(Request $req, $id)
{
    $project = DB::table('projects')->where('id', $id)->first();
    if (!$project) abort(404);

    if (strtoupper((string)$project->status) === 'DONE') {
        return redirect()->route('projects.index')
            ->withErrors('Proyek sudah DONE, pengembalian tidak dapat diubah lagi.');
    }

    // Validasi baru yang lebih fleksibel
    $data = $req->validate([
        'items'                   => 'array',
        'items.*.product_id'      => 'required|integer|exists:products,id',
        'items.*.uom_id'          => 'required|integer|exists:uoms,id',
        'items.*.length_m'        => 'nullable|numeric|min:0', // Sisa dalam meter
        'items.*.return_qty'      => 'nullable|numeric|min:0', // Sisa dalam qty
        'items.*.condition'       => 'nullable|in:GOOD,DAMAGED',
    ]);

    $rows = collect($data['items'] ?? [])
        ->filter(function ($it) {
            // Simpan hanya jika ada sisa yang diinput (baik length_m atau return_qty)
            $hasLength = isset($it['length_m']) && (float)$it['length_m'] > 0.0001;
            $hasQty = isset($it['return_qty']) && (float)$it['return_qty'] > 0.0001;
            return $it['product_id'] > 0 && ($hasLength || $hasQty);
        });

    DB::transaction(function () use ($rows, $project) {
        $now = now();
        $branchId = (int)$project->branch_id;
        $userId = (int)auth()->id();
        
        // Ambil lokasi AVAILABLE untuk branch ini
        $storeLocId = (int) DB::table('stock_locations')
            ->where('branch_id', $branchId)->where('type', 'AVAILABLE')->value('id');
        abort_if(!$storeLocId, 422, 'Lokasi AVAILABLE untuk branch ini tidak ditemukan.');

        foreach ($rows as $it) {
            $productId = (int)$it['product_id'];
            $uomId = (int)$it['uom_id'];
            $condition = $it['condition'] ?? 'GOOD';
            
            // Cek apakah produk ini berbasis panjang
            $productLength = DB::table('products')->where('id', $productId)->value('length_cm');
            $isLengthBased = $productLength !== null && $productLength > 0;

            if ($isLengthBased && isset($it['length_m'])) {
                // --- PROSES SEBAGAI SISA POTONGAN (LEFTOVER) ---
                // Ambil HPP produk untuk cost_per_m
                $productHpp = (float) DB::table('products')->where('id', $productId)->value('hpp') ?? 0;
                $lengthCm = (float) $productLength;
                $costPerM = $lengthCm > 0 ? round($productHpp / ($lengthCm / 100.0), 2) : 0;

                DB::table('leftover_pieces')->insert([
                    'branch_id'   => $branchId,
                    'product_id'  => $productId,
                    'length_m'    => (float)$it['length_m'],
                    'cost_per_m'  => $costPerM,
                    'condition'   => $condition,
                    'source_type' => 'PROJECT_RETURN',
                    'source_id'   => (int)$project->id,
                    'created_at'  => $now,
                ]);

            } elseif (!$isLengthBased && isset($it['return_qty'])) {
                // --- PROSES SEBAGAI PENGEMBALIAN STOK KE GUDANG ---
                $returnQty = (float)$it['return_qty'];

                // 1. Buat jejak di stock_moves
                DB::table('stock_moves')->insert([
                    'product_id'       => $productId,
                    'uom_id'           => $uomId,
                    'qty'              => $returnQty,
                    'from_location_id' => null, // Dari "luar"
                    'to_location_id'   => $storeLocId, // Masuk ke gudang
                    'ref_type'         => 'PROJECT_RETURN',
                    'ref_id'           => (int)$project->id,
                    'state'            => 'DONE',
                    'created_by'       => $userId,
                    'created_at'       => $now,
                ]);

                // 2. Tambahkan kuantitas di stock_quants
                DB::table('stock_quants')
                    ->where('product_id', $productId)
                    ->where('location_id', $storeLocId)
                    ->increment('qty', $returnQty);
            }
        }

        // Apapun hasilnya, tandai proyek DONE
        DB::table('projects')->where('id', $project->id)->update([
            'status' => 'DONE',
        ]);
    });

    return redirect()->route('projects.index')->with(
        'ok',
        $rows->count() > 0
            ? 'Sisa proyek tercatat & proyek ditandai DONE.'
            : 'Tidak ada sisa yang dikembalikan. Proyek ditandai DONE.'
    );
}



    /* =======================
     | Leftover datalist (JSON sederhana)
     |=======================*/
    public function leftoverList(Request $req)
    {
        $branchId = $this->branchId();
        $q = DB::table('leftover_pieces as lp')
            ->join('products as p','p.id','=','lp.product_id')
            ->where('lp.branch_id',$branchId)
            ->whereNull('lp.consumed_at')
            ->where(function($s){ $s->whereNull('lp.reserved_project_id'); })
            ->select('lp.id','lp.product_id','p.name','p.sku','lp.length_m','lp.condition')
            ->orderByDesc('lp.id')
            ->limit(100);

        if ($req->filled('product_id')) {
            $q->where('lp.product_id',(int)$req->input('product_id'));
        }
        if ($req->filled('keyword')) {
            $kw = '%'.$req->input('keyword').'%';
            $q->where(function($w) use ($kw) {
                $w->where('p.name','like',$kw)->orWhere('p.sku','like',$kw);
            });
        }

        return response()->json($q->get());
    }

    /* =======================
     | Cetak
     |=======================*/
public function printSj($projectId)
{
    $project = DB::table('projects')
        ->leftJoin('customers','customers.id','=','projects.customer_id')
        ->where('projects.id',$projectId)
        ->select(
            'projects.*',
            'customers.name as customer_name',
            'customers.address as customer_address',
            'customers.phone as customer_phone'
        )->first();
    abort_if(!$project, 404);

    // Material keluar (dari gudang) untuk proyek
    $moves = DB::table('stock_moves as sm')
        ->join('products as p','p.id','=','sm.product_id')
        ->join('uoms as u','u.id','=','sm.uom_id')
        ->where('sm.ref_type','PROJECT_ISSUE')
        ->where('sm.ref_id', $projectId)
        ->select('p.sku','p.name','u.code as uom','sm.qty')
        ->get();

    // Pemakaian meter: gabungkan (pakai sebagian) + (habis total)
    $piecesA = DB::table('leftover_piece_consumptions as c')
        ->join('leftover_pieces as lp','lp.id','=','c.piece_id')
        ->join('products as p','p.id','=','lp.product_id')
        ->where('c.project_id', $projectId)
        ->select('p.sku','p.name', DB::raw('c.used_m as length_m'))
        ->get();

    $piecesB = DB::table('leftover_pieces as lp')
        ->join('products as p','p.id','=','lp.product_id')
        ->where('lp.reserved_project_id', $projectId)
        ->whereNotNull('lp.consumed_at')
        ->select('p.sku','p.name', DB::raw('lp.length_m as length_m'))
        ->get();

    $pieces = $piecesA->merge($piecesB);

    return view('projects.print_sj', [
        'title'   => 'Cetak Surat Jalan',
        'project' => $project,
        'moves'   => $moves,
        'pieces'  => $pieces,
        'now'     => now(),
    ]);
}
// Di dalam ProjectController.php
// Di dalam file: app/Http/Controllers/ProjectController.php

// Di dalam file: app/Http/Controllers/ProjectController.php

// Di dalam file: app/Http-d/Controllers/ProjectController.php

public function printInvoiceByProject(int $projectId)
{
    $project = DB::table('projects')->where('id', $projectId)->first();
    abort_if(!$project, 404);

    $sale = $this->getOrCreateProjectSale($projectId);
    $payments = DB::table('pos_payments')->where('pos_sale_id', $sale->id)->orderBy('id')->get();
    $paid = $this->salePaidAmount((int)$sale->id);
    $due  = max(0, ((float)$sale->total - $paid));
    $change = max(0, ($paid - (float)$sale->total));

    // Ambil rincian JASA
    $serviceProduct = $this->ensureServiceProduct();
    $serviceLines = DB::table('project_services')
        ->where('project_id', $projectId)
        ->orderBy('id')
        ->select('name as product_name', DB::raw('1 as qty'), 'price', 'price as subtotal')
        ->get();

    // Ambil rincian MATERIAL yang ditagihkan
    $billedMaterialLines = DB::table('pos_sale_lines as sl')
        ->join('products as p', 'p.id', '=', 'sl.product_id')
        ->where('sl.pos_sale_id', $sale->id)
        ->where('sl.product_id', '!=', $serviceProduct->id)
        ->orderBy('sl.id')
        ->select('sl.*', 'p.name as product_name')
        ->get();

    // ========================================================================
    // PERBAIKAN DI SINI: KEMBALIKAN LOGIKA PENGAMBILAN DATA LAMPIRAN
    // ========================================================================

    // 1. Ambil data untuk "Material dari Stok" langsung dari `stock_moves`
    $materials = DB::table('stock_moves as sm')
        ->join('products as p','p.id','=','sm.product_id')
        ->join('uoms as u','u.id','=','sm.uom_id')
        ->where('sm.ref_type','PROJECT_ISSUE')
        ->where('sm.ref_id', $projectId)
        ->where('sm.qty', '>', 0) // <-- Hanya dari stok gudang
        ->select('p.sku','p.name','u.code as uom','sm.qty')
        ->get()
        ->map(fn($r) => (array)$r) // Ubah menjadi array agar konsisten dengan view
        ->toArray();

    // 2. Ambil data untuk "Pemakaian Potongan Sisa"
    $meterA = DB::table('leftover_piece_consumptions as c')
        ->join('leftover_pieces as lp','lp.id','=','c.piece_id')
        ->join('products as p','p.id','=','lp.product_id')
        ->where('c.project_id', $projectId)
        ->select('p.sku','p.name', DB::raw('c.used_m as length_m'));
        // Tidak perlu get() dulu, kita gabung dengan query B

    $meter = DB::table('leftover_pieces as lp')
        ->join('products as p','p.id','=','lp.product_id')
        ->where('lp.reserved_project_id', $projectId)
        ->whereNotNull('lp.consumed_at')
        ->select('p.sku','p.name', DB::raw('lp.length_m as length_m'))
        ->union($meterA) // Gabungkan dengan query A
        ->get();

    // ========================================================================
    // AKHIR PERBAIKAN
    // ========================================================================

    return view('projects.print_invoice', [
        'project'             => $project,
        'sale'                => $sale,
        'payments'            => $payments,
        'paid'                => $paid,
        'due'                 => $due,
        'change'              => $change,
        'serviceLines'        => $serviceLines,
        'billedMaterialLines' => $billedMaterialLines,
        'materials'           => $materials, // <-- Data ini sekarang punya format yang benar
        'meter'               => $meter,
        'title'               => 'Invoice Proyek ' . $project->code,
    ]);
}





    /* =======================
     | Stock helpers (sederhana)
     |=======================*/
    protected function ensureBranchLocation($branchId, $type)
    {
        // Gunakan UNIQUE (branch_id,type)
        $loc = DB::table('stock_locations')
            ->where('branch_id',$branchId)
            ->where('type',$type)
            ->first();

        if ($loc) return $loc->id;

        return DB::table('stock_locations')->insertGetId([
            'branch_id' => $branchId,
            'code'      => strtoupper($type),
            'name'      => ucfirst(strtolower($type)).' Location',
            'type'      => $type,
        ]);
    }

    protected function decreaseQuant($productId, $locationId, $qty)
    {
        $row = DB::table('stock_quants')
            ->where('product_id',$productId)
            ->where('location_id',$locationId)
            ->lockForUpdate()
            ->first();

        if ($row) {
            $new = max(0, (float)$row->qty - (float)$qty);
            DB::table('stock_quants')
                ->where('id',$row->id)
                ->update(['qty'=>$new]);
        } else {
            // jika belum ada, buat 0 lalu minus tidak diizinkan -> tetap 0
            DB::table('stock_quants')->insert([
                'product_id'  => $productId,
                'location_id' => $locationId,
                'qty'         => 0,
            ]);
        }
    }
}
