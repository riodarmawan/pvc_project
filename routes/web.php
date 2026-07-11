<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\SalesHistoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CashController;
use App\Http\Controllers\ProjectController;
// Benar, sesuai dengan lokasi file Anda
use App\Http\Controllers\ProductApiController; 
use App\Http\Controllers\GeminiChatController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\OwnerDashboardController;
/* ========== Auth ========== */
Route::get('/login',  [AuthController::class,'showLogin'])->name('login');
Route::post('/login', [AuthController::class,'login'])->name('login.do');
Route::post('/logout',[AuthController::class,'logout'])->name('logout')->middleware('auth');

/* ========== Home per peran (role_id) ========== */
Route::middleware(['auth','role:1'])->get('/owner', [OwnerDashboardController::class, 'index'])->name('owner.home');
Route::middleware(['auth','role:2'])->get('/kc',    fn() => view('kc.index'))   ->name('kc.home');


Route::middleware(['auth'])->get('/products', [ProductApiController::class, 'getProductsByBranch']);
// Rute untuk halaman view Chat (GET request)
Route::get('/chat/{branch_id}', [GeminiChatController::class, 'showChatView'])->name('chat.view');

// Rute untuk API pengiriman pesan (POST request)
Route::post('/chat/{branch_id}', [GeminiChatController::class, 'handleChatMessage'])->name('chat.send');
/* ========== POS (Kasir, role_id=3) ========== */
Route::middleware(['auth','role:3'])->group(function () {
    // Halaman 1: Katalog (legacy)
    Route::get('/kasir',            [PosController::class,'index'])   ->name('kasir.home');

    // Halaman POS: Single-page (Catalog + Cart + Payment)
    Route::get('/kasir/pos',        [PosController::class,'pos'])     ->name('kasir.pos');

    // Halaman 2: Checkout (legacy)
    Route::get('/kasir/checkout',   [PosController::class,'checkout'])->name('kasir.checkout');

    // Cart
    Route::post('/kasir/cart/add',    [PosController::class,'cartAdd'])   ->name('kasir.cart.add');
    Route::post('/kasir/cart/update', [PosController::class,'cartUpdate'])->name('kasir.cart.update');
    Route::post('/kasir/cart/remove', [PosController::class,'cartRemove'])->name('kasir.cart.remove');
    Route::post('/kasir/cart/clear',  [PosController::class,'cartClear']) ->name('kasir.cart.clear');

    // Customer
    Route::get('/kasir/customer/search',  [PosController::class,'customerSearch'])->name('kasir.customer.search');
    Route::post('/kasir/customer/select',[PosController::class,'customerSelect'])->name('kasir.customer.select');
    Route::post('/kasir/customer/quick', [PosController::class,'customerQuick']) ->name('kasir.customer.quick');

    // Payments
    Route::post('/kasir/payments/add',   [PosController::class,'paymentAdd'])->name('kasir.pay.add');
    Route::post('/kasir/payments/clear', [PosController::class,'paymentClear'])->name('kasir.pay.clear');

    // Finalize
    Route::post('/kasir/finalize', [PosController::class,'finalize'])->name('kasir.finalize');

    Route::get('/kasir/history',                [SalesHistoryController::class,'index'])->name('kasir.history');
    Route::get('/kasir/history/ajax-table',     [SalesHistoryController::class,'ajaxTable'])->name('kasir.history.ajax-table');
    Route::get('/kasir/history/{id}/detail',     [SalesHistoryController::class,'detail'])->name('kasir.history.detail');
    Route::get('/kasir/history/{id}/invoice',    [SalesHistoryController::class,'invoice'])->name('kasir.history.invoice');
    Route::get('/kasir/history/{id}/refund/confirm', [PosController::class,'refundConfirm'])->name('kasir.history.refund.confirm');
    Route::post('/kasir/history/{id}/refund',    [PosController::class,'refund'])->name('kasir.history.refund');

    // Scan dihapus (tidak dipakai)
    Route::get('/kasir/products/new',  [ProductController::class,'create'])->name('kasir.products.new');
    Route::post('/kasir/products',     [ProductController::class,'store'])->name('kasir.products.store');
    Route::get('/kasir/cash',           [CashController::class,'index'])->name('kasir.cash');
    Route::post('/kasir/cash/out',      [CashController::class,'storeOut'])->name('kasir.cash.out');
    Route::post('/kasir/cash/adjust',   [CashController::class,'storeAdjust'])->name('kasir.cash.adjust');
    // List + create
    Route::get('/projects',                 [ProjectController::class,'index'])->name('projects.index');
    Route::get('/projects/create',          [ProjectController::class,'create'])->name('projects.create');

    // Cart & customer (projek terpasang)
    Route::post('/projects/cart/add',       [ProjectController::class,'cartAdd'])->name('projects.cart.add');
    Route::post('/projects/cart/update',    [ProjectController::class,'cartUpdate'])->name('projects.cart.update');
    Route::post('/projects/cart/remove',    [ProjectController::class,'cartRemove'])->name('projects.cart.remove');
    Route::post('/projects/cart/clear',     [ProjectController::class,'cartClear'])->name('projects.cart.clear');
    Route::post('/projects/customer/select',[ProjectController::class,'customerSelect'])->name('projects.customer.select');
    Route::post('/projects/customer/quick', [ProjectController::class,'customerQuick'])->name('projects.customer.quick');

    // Finalize project (redirect ke /projects)
    Route::post('/projects/finalize',       [ProjectController::class,'finalize'])->name('projects.finalize');

    // Return/Sisa
    Route::get('/projects/return/{id}',     [ProjectController::class,'returnForm'])->name('projects.return.form');
    Route::post('/projects/return/{id}',    [ProjectController::class,'returnProcess'])->name('projects.return.process');
    Route::get('/projects/leftover/list', [ProjectController::class, 'leftoverList'])
    ->name('projects.leftover.list');   // ← untuk datalist potongan sisa

// Tambah / bersihkan pembayaran billing proyek
Route::post('/projects/{project}/bill/pay',   [ProjectController::class,'billPayAdd'])
    ->name('projects.bill.pay.add');

Route::post('/projects/{project}/bill/clear', [ProjectController::class,'billClear'])
    ->name('projects.bill.pay.clear');
    // Tambahkan di group kasir (role:3)
Route::get('/projects/{project}/bill', [ProjectController::class,'billShow'])
    ->name('projects.bill.show');


    
});

/* ====== Cetak / Reprint (siapa pun yang login) ====== */
Route::middleware(['auth'])->group(function () {
    // Cetak SJ berbasis PROJECT ID
    Route::get('/projects/{project}/print-sj',        [ProjectController::class,'printSj'])
        ->name('projects.print.sj');

    // Cetak invoice berbasis PROJECT (auto-cari sale dari project)
    Route::get('/projects/{project}/print-invoice',   [ProjectController::class,'printInvoiceByProject'])
        ->name('projects.print.invoice.byproject');

        Route::get('/stock/adjust',  [\App\Http\Controllers\StockAdjustmentController::class, 'create'])->name('stock.adjust.create');
    Route::post('/stock/adjust', [\App\Http\Controllers\StockAdjustmentController::class, 'store'])->name('stock.adjust.store');
    Route::get('/stock/transfer',  [\App\Http\Controllers\StockTransferController::class, 'create'])->name('stock.transfer.create');
    Route::post('/stock/transfer', [\App\Http\Controllers\StockTransferController::class, 'store'])->name('stock.transfer.store');
    Route::get('/purchase/direct/create', [\App\Http\Controllers\DirectPurchaseController::class, 'create'])->name('purchase.direct.create');
Route::post('/purchase/direct', [\App\Http\Controllers\DirectPurchaseController::class, 'store'])->name('purchase.direct.store');

// Menampilkan form dan memproses pendaftaran supplier baru
Route::get('/suppliers/create', [\App\Http\Controllers\SupplierController::class, 'create'])->name('suppliers.create');
Route::post('/suppliers', [\App\Http\Controllers\SupplierController::class, 'store'])->name('suppliers.store');

Route::get('/products/create', [\App\Http\Controllers\ProductController::class, 'create'])->name('products.create');
    
    // Menyimpan data dari form produk baru
    Route::post('/products', [\App\Http\Controllers\ProductController::class, 'store'])->name('products.store');
// Rute untuk menampilkan halaman impor
    Route::get('/products/import', [ProductImportController::class, 'showForm'])->name('admin.products.import.form');

    // Rute untuk memproses file yang diunggah
    Route::post('/products/import', [ProductImportController::class, 'processImport'])->name('admin.products.import.process');
    /* ========== MANAJEMEN SISA POTONGAN ========== */
// Menampilkan daftar sisa potongan beserta filter
Route::get('/leftovers', [\App\Http\Controllers\LeftoverController::class, 'index'])->name('leftovers.index');

// Menghapus data sisa potongan
Route::delete('/leftovers/{id}', [\App\Http\Controllers\LeftoverController::class, 'destroy'])->name('leftovers.destroy');

// [BARU] Menampilkan form edit
Route::get('/leftovers/{id}/edit', [\App\Http\Controllers\LeftoverController::class, 'edit'])->name('leftovers.edit');

// [BARU] Memproses update dari form edit
Route::put('/leftovers/{id}', [\App\Http\Controllers\LeftoverController::class, 'update'])->name('leftovers.update');

Route::get('/reports/stock', [\App\Http\Controllers\StockReportController::class, 'index'])->name('reports.stock.index');
Route::get('/reports/transactions', [\App\Http\Controllers\TransactionHistoryController::class, 'index'])->name('reports.transactions.index');
Route::get('/reports/audit-log', [\App\Http\Controllers\AuditLogController::class, 'index'])->name('reports.audit-log');

// Menampilkan halaman formulir pembuatan cabang baru
    Route::get('/branches/create', [BranchController::class, 'create'])->name('admin.branches.create');
    
    // Menyimpan data dari formulir, membuat cabang, dan akun kasir otomatis
    Route::post('/branches', [BranchController::class, 'store'])->name('admin.branches.store');
    


});

/* ========== PRODUK (OWNER ONLY - CRUD) ========== */
Route::middleware(['auth','role:1'])->group(function () {
    Route::get('/products/{id}/edit', [\App\Http\Controllers\ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{id}',      [\App\Http\Controllers\ProductController::class, 'update'])->name('products.update');
});

/* ========== AKUNTANSI (OWNER ONLY) ========== */
Route::middleware(['auth','role:1'])->prefix('accounting')->name('accounting.')->group(function () {
    Route::get('/chart', [\App\Http\Controllers\AccountController::class, 'index'])->name('chart');
    Route::post('/chart', [\App\Http\Controllers\AccountController::class, 'store'])->name('chart.store');
    Route::put('/chart/{id}', [\App\Http\Controllers\AccountController::class, 'update'])->name('chart.update');
    Route::delete('/chart/{id}', [\App\Http\Controllers\AccountController::class, 'destroy'])->name('chart.destroy');

    Route::get('/journal', [\App\Http\Controllers\JournalController::class, 'index'])->name('journal');
    Route::get('/journal/create', [\App\Http\Controllers\JournalController::class, 'create'])->name('journal.create');
    Route::post('/journal', [\App\Http\Controllers\JournalController::class, 'store'])->name('journal.store');
    Route::post('/journal/{id}/post', [\App\Http\Controllers\JournalController::class, 'post'])->name('journal.post');
});

/* ========== LAPORAN KEUANGAN (OWNER ONLY) ========== */
Route::middleware(['auth','role:1'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/income-statement', [\App\Http\Controllers\FinancialReportController::class, 'incomeStatement'])->name('income_statement');
    Route::get('/cash-reconciliation', [\App\Http\Controllers\FinancialReportController::class, 'cashReconciliation'])->name('cash_reconciliation');
});

/* ========== Root redirect sesuai role ========== */
Route::get('/', function () {
    if (auth()->check()) {
        $rid = (int) (auth()->user()->role_id ?? 0);
        return match ($rid) {
            1 => redirect()->route('owner.home'),
            2 => redirect()->route('kc.home'),
            3 => redirect()->route('kasir.home'),
            default => redirect()->route('login'),
        };
    }
    return redirect()->route('login');
});
