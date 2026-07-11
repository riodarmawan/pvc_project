# Desain — CRUD Produk (Owner)

Status: Approved
Tanggal: 2026-07-11

## Latar Belakang

`ProductController` saat ini hanya punya `create()` + `store()` (Create). Tidak ada halaman
daftar produk, tidak ada edit, dan tidak ada cara menonaktifkan produk dari UI — walau kolom
`products.is_active` sudah ada di skema dan diisi `1` secara default saat create.

Audit turut menemukan bahwa `is_active` **tidak difilter** di katalog POS (`PosController::index`,
`PosController::pos`) maupun API produk (`ProductApiController::getProductsByBranch`), padahal
`ProjectController::create` (material picker proyek, baris 297) **sudah benar** memfilter
`is_active=1`. Tanpa perbaikan ini, fitur nonaktifkan produk tidak berefek apa-apa di kasir.

## Cakupan

Full CRUD manajemen produk, **owner-only** (`role_id=1`):
- **List** — tabel semua produk dengan search + filter.
- **Edit** — ubah data produk yang sudah ada.
- **Nonaktifkan / Aktifkan** — soft-delete via toggle `is_active`, BUKAN hard delete.

Create (`/products/create`, `/kasir/products/new`) tidak diubah — kasir & owner tetap bisa
membuat produk baru seperti sekarang.

## Keputusan Kunci

- **Tidak ada hard delete.** Produk terhubung ke `pos_sale_lines`, `stock_moves`,
  `project_boms`, `stock_quants` — menghapus baris produk akan merusak riwayat transaksi/akuntansi.
  Nonaktifkan (toggle `is_active`) adalah pengganti yang aman.
- **Akses owner-only.** Menu & route baru diletakkan di grup middleware `['auth','role:1']` yang
  sudah ada di `routes/web.php`.
- **Root-cause fix wajib disertakan**: tambah `->where('p.is_active', 1)` ke 3 query katalog
  (`PosController::index`, `PosController::pos`, `ProductApiController::getProductsByBranch`)
  supaya konsisten dengan `ProjectController` dan supaya nonaktifkan benar-benar menyembunyikan
  produk dari POS.
- **Reuse form via partial.** Field form di `products/create.blade.php` di-extract ke
  `products/_form.blade.php`, dipakai bareng oleh create & edit — hindari duplikasi ~90 baris HTML.
- **Menu baru.** Tambah link "Kelola Produk" di submenu "Master Data" pada
  `resources/views/layouts/dashboard.blade.php` (baris ~156-159), mengarah ke `products.index`.
  Saat ini bahkan `products.create` tidak punya link menu — dari `products.index` akan ada tombol
  "+ Tambah Produk" ke `products.create`.

## Routes Baru

Ditambahkan di dalam grup `Route::middleware(['auth','role:1'])` (`routes/web.php`):

```php
Route::get('/products',            [ProductController::class,'index'])->name('products.index');
Route::get('/products/{id}/edit',  [ProductController::class,'edit'])->name('products.edit');
Route::put('/products/{id}',       [ProductController::class,'update'])->name('products.update');
Route::post('/products/{id}/toggle-active', [ProductController::class,'toggleActive'])->name('products.toggleActive');
```

Route existing `/products/create` (GET) dan `/products` (POST) di grup `auth` biasa — tidak dipindah,
tidak diubah aksesnya (di luar scope).

## Controller — `app/Http/Controllers/ProductController.php`

Method baru, mengikuti pola yang sudah ada di file ini (query builder `DB::table`, bukan Eloquent;
transaksi + audit log manual):

- **`index(Request $request)`**
  Paginated (20/page) atas `products` + `product_categories` (join untuk nama kategori).
  Filter: `q` (cari `sku`/`name` like), `category_id`, `status` (`active`/`inactive`/semua,
  default semua). Kolom ditampilkan: SKU, Nama, Kategori, HPP, Harga Jual, Status, Aksi.

- **`edit($id)`**
  `abort(404)` bila produk tak ditemukan. Ambil produk + daftar `product_categories` + `uoms`
  untuk populate form. Render `products.edit`.

- **`update(Request $request, $id)`**
  Validasi sama seperti `store()`, dengan pengecualian unique untuk row sendiri:
  `Rule::unique('products','sku')->ignore($id)`, sama untuk `barcode`.
  Field yang sama dengan `store()` (nama, uom, hpp, selling_price, kategori/kategori baru,
  atribut fisik, notes). Dalam `DB::transaction()`: update row `products`, insert audit log
  `PRODUCT_UPDATED` (payload: data lama vs baru). Redirect ke `products.index` dengan flash success.

- **`toggleActive($id)`**
  `abort(404)` bila tak ditemukan. Flip `is_active` (0↔1) dalam transaksi. Audit log
  `PRODUCT_ACTIVATED` atau `PRODUCT_DEACTIVATED` sesuai state baru. Redirect balik ke
  `products.index` (preserve query string filter/page) dengan flash success.

## Fix Katalog — wiring `is_active`

Tambahkan satu baris `->where('p.is_active', 1)` pada:
- `PosController::index()` — query `$query = DB::table('products as p')...` (±baris 43-49)
- `PosController::pos()` — query yang identik (±baris 111-116)
- `ProductApiController::getProductsByBranch()` — query `$query = DB::table('products as p')...`
  (±baris 41-54)

Tidak menyentuh query lain (mis. `resolveSellingPrice`, query histori/laporan) — di luar scope,
dan produk nonaktif tetap harus muncul di riwayat transaksi lama.

## Views

- **`resources/views/products/_form.blade.php`** (baru) — field form (SKU, nama, UOM, HPP,
  harga jual, kategori/kategori baru, atribut fisik, notes) di-extract dari
  `products/create.blade.php`. Menerima variabel opsional `$product` (null saat create) untuk
  prefill value via `old('field', $product->field ?? '')`.
- **`resources/views/products/create.blade.php`** — disesuaikan untuk `@include('products._form')`
  menggantikan markup field yang dipindah ke partial. Action form & behavior tidak berubah.
- **`resources/views/products/edit.blade.php`** (baru) — layout sama seperti create
  (`layouts.dashboard`), form action ke `products.update` dengan `@method('PUT')`,
  `@include('products._form', ['product' => $product])`.
- **`resources/views/products/index.blade.php`** (baru) — tabel produk + search box + filter
  kategori/status + pagination + tombol "+ Tambah Produk" (ke `products.create`) + aksi per baris
  (Edit, tombol Aktifkan/Nonaktifkan dengan konfirmasi `confirm()` sebelum submit).
- **`resources/views/layouts/dashboard.blade.php`** — tambah 1 baris link "Kelola Produk" ke
  `products.index` di submenu Master Data (`data-submenu-target="master"`, dekat baris 158).

## Audit Logging

Konsisten dengan pola `store()` yang sudah ada (`audit_logs` table, `event`, `user_id`, `ref_type`,
`ref_id`, `payload` JSON, `created_at`): tambah event `PRODUCT_UPDATED`, `PRODUCT_ACTIVATED`,
`PRODUCT_DEACTIVATED`.

## Tidak Dikerjakan (out of scope)

- Hard delete produk.
- Mengubah akses/route `products.create` & `kasir.products.new` yang sudah ada.
- Kolom jumlah stok di halaman list produk baru ini (sudah ada di Laporan Stok terpisah).
- Test otomatis baru (feature test Laravel) — item ini kecil & UI-facing CRUD standar; tidak ada
  logika akuntansi/keuangan yang perlu di-assert seperti alur POS/proyek. Verifikasi manual di
  browser sudah cukup (lihat item testing di plan implementasi).
