# CRUD Produk (Owner) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give the owner role a full product management UI (list, edit, deactivate/reactivate) and fix the underlying bug where deactivated products still appear in the POS catalog.

**Architecture:** Extend the existing `ProductController` (query-builder style, `DB::table`, manual transactions + audit log inserts — no Eloquent, matching the rest of this controller) with `index()`, `edit()`, `update()`, `toggleActive()`. Add 4 new routes under the existing `role:1` (owner) middleware group. Extract the product form fields already in `products/create.blade.php` into a shared partial so create and edit render identical fields. Add one sidebar link. Separately, patch the 3 catalog queries that never filtered `is_active`.

**Tech Stack:** Laravel 12, Blade, MariaDB/MySQL, Tailwind (existing `layouts.dashboard` styling — brand color, `dark:` variants), query builder (no Eloquent models used in this controller).

## Global Constraints

- Owner-only: every new route/controller method sits behind `['auth','role:1']`, matching the existing `routes/web.php` groups for `/accounting` and `/reports`.
- No hard delete: `products` rows are referenced by `pos_sale_lines`, `stock_moves`, `project_boms`, `stock_quants` — deactivation (`is_active` toggle) is the only removal mechanism.
- No Eloquent models: `ProductController` uses `DB::table(...)` query builder throughout — new methods must match this style, not introduce `App\Models\Product::` usage.
- Audit every mutation: follow the existing pattern in `ProductController::store()` — insert into `audit_logs` with `event`, `user_id`, `ref_type='PRODUCT'`, `ref_id`, `payload` (JSON), `created_at`.
- `products` table has **no** `created_at`/`updated_at` columns (verified against `final_pvc.sql` schema at line 782) — never write those columns on this table.
- No new automated tests for this feature (locked in the spec, `docs/superpowers/specs/2026-07-11-product-crud-design.md`) — verification is manual per-task (tinker + browser), matching this project's existing manual-smoke-test convention (`docs/SMOKE_TEST_BROWSER_PLAN.md`).
- Every task must leave the app in a working state with no dead links — routes are only added in the task that also implements their controller method and view.

---

### Task 1: Fix `is_active` filter in POS catalog & product API

**Files:**
- Modify: `app/Http/Controllers/PosController.php:43-49` (method `index()`)
- Modify: `app/Http/Controllers/PosController.php:111-116` (method `pos()`)
- Modify: `app/Http/Controllers/ProductApiController.php:41-54` (method `getProductsByBranch()`)

**Interfaces:**
- Consumes: nothing new — pure query modification.
- Produces: nothing new — behavior-only fix. Downstream tasks don't depend on this.

- [ ] **Step 1: Confirm current (buggy) behavior with tinker**

Run:
```bash
php artisan tinker --execute="
\$before = DB::table('products as p')->where('p.id', 1)->selectRaw('p.id, p.is_active')->first();
echo 'product 1 is_active=' . \$before->is_active . PHP_EOL;
DB::table('products')->where('id', 1)->update(['is_active' => 0]);
\$stillShows = DB::table('products as p')->where('p.id', 1)->exists();
echo 'shows in unfiltered query (bug): ' . (\$stillShows ? 'yes' : 'no') . PHP_EOL;
DB::table('products')->where('id', 1)->update(['is_active' => 1]);
"
```
Expected output: `product 1 is_active=1` then `shows in unfiltered query (bug): yes` — confirming the query has no `is_active` filter today (product 1 is restored to active at the end of the snippet).

- [ ] **Step 2: Patch `PosController::index()`**

In `app/Http/Controllers/PosController.php`, find (around line 43-49):
```php
        $query = DB::table('products as p')
            ->selectRaw('p.id, p.sku, p.name, p.category_id, p.uom_id, p.hpp, p.selling_price, p.notes')
            // 2. Ubah subquery untuk menjumlahkan stok dari SEMUA lokasi di cabang tersebut.
            ->addSelect(DB::raw("(SELECT IFNULL(SUM(sq.qty),0)
                                 FROM stock_quants sq
                                 WHERE sq.product_id = p.id
                                   AND sq.location_id IN ({$locationIdsString})) as stock"));
```
Replace with:
```php
        $query = DB::table('products as p')
            ->selectRaw('p.id, p.sku, p.name, p.category_id, p.uom_id, p.hpp, p.selling_price, p.notes')
            // 2. Ubah subquery untuk menjumlahkan stok dari SEMUA lokasi di cabang tersebut.
            ->addSelect(DB::raw("(SELECT IFNULL(SUM(sq.qty),0)
                                 FROM stock_quants sq
                                 WHERE sq.product_id = p.id
                                   AND sq.location_id IN ({$locationIdsString})) as stock"))
            ->where('p.is_active', 1);
```

- [ ] **Step 3: Patch `PosController::pos()`**

In the same file, find (around line 111-116):
```php
        $query = DB::table('products as p')
            ->selectRaw('p.id, p.sku, p.name, p.category_id, p.uom_id, p.hpp, p.selling_price, p.notes')
            ->addSelect(DB::raw("(SELECT IFNULL(SUM(sq.qty),0)
                                 FROM stock_quants sq
                                 WHERE sq.product_id = p.id
                                   AND sq.location_id IN ({$locationIdsString})) as stock"));
```
Replace with:
```php
        $query = DB::table('products as p')
            ->selectRaw('p.id, p.sku, p.name, p.category_id, p.uom_id, p.hpp, p.selling_price, p.notes')
            ->addSelect(DB::raw("(SELECT IFNULL(SUM(sq.qty),0)
                                 FROM stock_quants sq
                                 WHERE sq.product_id = p.id
                                   AND sq.location_id IN ({$locationIdsString})) as stock"))
            ->where('p.is_active', 1);
```

- [ ] **Step 4: Patch `ProductApiController::getProductsByBranch()`**

In `app/Http/Controllers/ProductApiController.php`, find (around line 41-54):
```php
        $query = DB::table('products as p')
            ->leftJoin('product_categories as c', 'p.category_id', '=', 'c.id')
            ->select(
                'p.id', 
                'p.name', 
                'c.name as category_name',
                'p.hpp',
                'p.selling_price',
                'p.notes'
            )
            ->addSelect(DB::raw("(SELECT IFNULL(SUM(sq.qty),0)
                                 FROM stock_quants sq
                                 WHERE sq.product_id = p.id
                                   AND sq.location_id IN ({$locationIdsString})) as stock"));
```
Replace with:
```php
        $query = DB::table('products as p')
            ->leftJoin('product_categories as c', 'p.category_id', '=', 'c.id')
            ->select(
                'p.id', 
                'p.name', 
                'c.name as category_name',
                'p.hpp',
                'p.selling_price',
                'p.notes'
            )
            ->addSelect(DB::raw("(SELECT IFNULL(SUM(sq.qty),0)
                                 FROM stock_quants sq
                                 WHERE sq.product_id = p.id
                                   AND sq.location_id IN ({$locationIdsString})) as stock"))
            ->where('p.is_active', 1);
```

- [ ] **Step 5: Verify the fix with tinker**

Run:
```bash
php artisan tinker --execute="
DB::table('products')->where('id', 1)->update(['is_active' => 0]);
\$hidden = DB::table('products as p')->where('p.is_active', 1)->where('p.id', 1)->exists();
echo 'hidden when inactive: ' . (\$hidden ? 'NO (bug still present)' : 'YES (fixed)') . PHP_EOL;
DB::table('products')->where('id', 1)->update(['is_active' => 1]);
\$restored = DB::table('products as p')->where('p.is_active', 1)->where('p.id', 1)->exists();
echo 'visible again after restore: ' . (\$restored ? 'YES' : 'NO') . PHP_EOL;
"
```
Expected: `hidden when inactive: YES (fixed)` and `visible again after restore: YES`.

- [ ] **Step 6: Manual browser check**

`php artisan serve`, login as `kasir`/`kasir`, open `/kasir/pos`, confirm the catalog loads normally (no regressions from the added `where` clause — should show the same products as before, since all seed data has `is_active=1`).

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/PosController.php app/Http/Controllers/ProductApiController.php
git commit -m "fix: filter is_active in POS catalog and product API queries

Deactivating a product had zero effect anywhere — the is_active column
was set on create but never checked in the POS catalog or product API,
only in ProjectController's material picker. Wires the same filter into
PosController::index/pos and ProductApiController::getProductsByBranch.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

### Task 2: Extract shared product form partial

**Files:**
- Create: `resources/views/products/_form.blade.php`
- Modify: `resources/views/products/create.blade.php`

**Interfaces:**
- Consumes: view variables already passed to `products.create` — `$categories` (collection of `product_categories` rows with `id`,`name`,`code`), `$uoms` (collection of `uoms` rows with `id`,`name`,`code`).
- Produces: partial `products._form`, includable from any view that has `$categories`/`$uoms` in scope. Optionally accepts `$product` (a `stdClass` row from `DB::table('products')->first()`, or entirely undefined) — every field prefills via `old('field', $product->field ?? '')`, which is `''`/`null` safely whether `$product` is undefined, `null`, or a real row (PHP's `??` suppresses undefined-variable/property notices through the whole chain). Task 3 depends on this partial for `products/edit.blade.php`.

- [ ] **Step 1: Create the shared form partial**

Create `resources/views/products/_form.blade.php`:
```blade
{{-- Form field produk — dipakai bersama oleh products.create & products.edit.
     $product tidak didefinisikan saat create (null-safe lewat ?? di setiap value). --}}

<!-- Bagian Informasi Dasar -->
<div class="rounded-2xl border bg-white shadow-card border-slate-200
            dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
    <div class="p-6 md:p-7 space-y-4">
        <div>
            <h2 class="text-base font-semibold">Informasi Dasar & Harga</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">Informasi utama dan harga beli pertama (HPP) untuk produk ini.</p>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <div class="space-y-2">
                <label for="sku" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                    SKU (Kode Unik Produk)
                </label>
                <input type="text" name="sku" id="sku" value="{{ old('sku', $product->sku ?? '') }}" required
                       class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                              focus:outline-none focus:ring-2 focus:ring-brand/40
                              dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            </div>

            <div class="space-y-2">
                <label for="name" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                    Nama Produk
                </label>
                <input type="text" name="name" id="name" value="{{ old('name', $product->name ?? '') }}" required
                       class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                              focus:outline-none focus:ring-2 focus:ring-brand/40
                              dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            </div>

            <div class="space-y-2">
                <label for="uom_id" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                    Satuan Unit (UOM)
                </label>
                <select id="uom_id" name="uom_id" required
                        class="w-full appearance-none bg-white border border-slate-200 rounded-xl h-11 px-3 pr-9 text-sm
                               focus:outline-none focus:ring-2 focus:ring-brand/40
                               dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                    <option value="">Pilih Satuan</option>
                    @foreach($uoms as $uom)
                        <option value="{{ $uom->id }}" @selected(old('uom_id', $product->uom_id ?? null) == $uom->id)>{{ $uom->name }} ({{$uom->code}})</option>
                    @endforeach
                </select>
            </div>

            {{-- ===== INPUT HPP & HARGA JUAL ===== --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label for="hpp" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                        Harga Beli (HPP)
                    </label>
                    <input type="number" name="hpp" id="hpp" value="{{ old('hpp', $product->hpp ?? '') }}" placeholder="Contoh: 55000" step="1"
                           class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-brand/40
                                  dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                </div>
                <div class="space-y-2">
                    <label for="selling_price" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                        Harga Jual
                    </label>
                    <input type="number" name="selling_price" id="selling_price" value="{{ old('selling_price', $product->selling_price ?? '') }}" placeholder="Contoh: 75000" step="1"
                           class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-brand/40
                                  dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                </div>
            </div>
            {{-- ================================== --}}
        </div>
    </div>
</div>

<!-- Bagian Kategori -->
<div class="rounded-2xl border bg-white shadow-card border-slate-200
            dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
    <div class="p-6 md:p-7 space-y-4">
        <div>
            <h2 class="text-base font-semibold">Kategori</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">Pilih kategori yang sudah ada, atau buat yang baru.</p>
        </div>

        <div id="category-selection" class="space-y-3">
            <div class="space-y-2">
                <label for="category_id" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                    Kategori yang Tersedia
                </label>
                <select id="category_id" name="category_id"
                        class="w-full appearance-none bg-white border border-slate-200 rounded-xl h-11 px-3 pr-9 text-sm
                               focus:outline-none focus:ring-2 focus:ring-brand/40
                               dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                    <option value="">Pilih Kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id ?? null) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">
                Tidak menemukan kategori?
                <button type="button" id="show-new-category-btn"
                        class="inline-flex items-center h-8 px-3 rounded-lg border hover:bg-slate-100 border-slate-200
                               dark:hover:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
                    Buat Baru
                </button>
            </p>
        </div>

        <div id="new-category-form" class="space-y-3">
            <div class="grid md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label for="new_category_name" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                        Nama Kategori Baru
                    </label>
                    <input type="text" name="new_category_name" id="new_category_name" value="{{ old('new_category_name') }}"
                           class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-brand/40
                                  dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                </div>
                <div class="space-y-2">
                    <label for="new_category_code" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                        Kode Kategori Baru
                    </label>
                    <input type="text" name="new_category_code" id="new_category_code" value="{{ old('new_category_code') }}"
                           class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-brand/40
                                  dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                </div>
            </div>
            <button type="button" id="cancel-new-category-btn"
                    class="inline-flex items-center h-10 px-4 rounded-xl border hover:bg-slate-100 border-slate-200
                           dark:hover:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
                Batal
            </button>
        </div>
    </div>
</div>

<!-- Bagian Atribut Tambahan -->
<div class="rounded-2xl border bg-white shadow-card border-slate-200
            dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
    <div class="p-6 md:p-7 space-y-4">
        <h2 class="text-base font-semibold">Atribut Tambahan (Opsional)</h2>

        <div class="grid md:grid-cols-2 gap-4">
            <div class="space-y-2">
                <label for="material" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Material</label>
                <input type="text" name="material" id="material" value="{{ old('material', $product->material ?? '') }}"
                       class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                              focus:outline-none focus:ring-2 focus:ring-brand/40
                              dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            </div>

            <div class="space-y-2">
                <label for="series" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Series</label>
                <input type="text" name="series" id="series" value="{{ old('series', $product->series ?? '') }}"
                       class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                              focus:outline-none focus:ring-2 focus:ring-brand/40
                              dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            </div>

            <div class="space-y-2">
                <label for="length_cm" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Panjang (cm)</label>
                <input type="number" name="length_cm" id="length_cm" value="{{ old('length_cm', $product->length_cm ?? '') }}"
                       class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                              focus:outline-none focus:ring-2 focus:ring-brand/40
                              dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            </div>

            <div class="space-y-2">
                <label for="width_mm" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Lebar (mm)</label>
                <input type="number" name="width_mm" id="width_mm" value="{{ old('width_mm', $product->width_mm ?? '') }}"
                       class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                              focus:outline-none focus:ring-2 focus:ring-brand/40
                              dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            </div>

            <div class="space-y-2">
                <label for="thickness_mm" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Tebal (mm)</label>
                <input type="number" step="0.01" name="thickness_mm" id="thickness_mm" value="{{ old('thickness_mm', $product->thickness_mm ?? '') }}"
                       class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                              focus:outline-none focus:ring-2 focus:ring-brand/40
                              dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            </div>

            <div class="space-y-2">
                <label for="barcode" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Barcode</label>
                <input type="text" name="barcode" id="barcode" value="{{ old('barcode', $product->barcode ?? '') }}"
                       class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                              focus:outline-none focus:ring-2 focus:ring-brand/40
                              dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            </div>

            <div class="md:col-span-2 space-y-2">
                <label for="notes" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Catatan Tambahan</label>
                <textarea id="notes" name="notes" rows="3"
                          class="w-full bg-white border border-slate-200 rounded-xl p-3 text-sm
                                 focus:outline-none focus:ring-2 focus:ring-brand/40
                                 dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">{{ old('notes', $product->notes ?? '') }}</textarea>
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 2: Replace the inline fields in `create.blade.php` with the partial**

In `resources/views/products/create.blade.php`, find the block spanning from `<!-- Bagian Informasi Dasar -->` through the end of `<!-- Bagian Atribut Tambahan -->` (currently lines 39-238, everything between `@csrf` and `<!-- Tombol Aksi -->`):
```blade
        <!-- Bagian Informasi Dasar -->
        <div class="rounded-2xl border bg-white shadow-card border-slate-200
                    dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            <div class="p-6 md:p-7 space-y-4">
                <div>
                    <h2 class="text-base font-semibold">Informasi Dasar & Harga</h2>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Informasi utama dan harga beli pertama (HPP) untuk produk ini.</p>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label for="sku" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                            SKU (Kode Unik Produk)
                        </label>
                        <input type="text" name="sku" id="sku" value="{{ old('sku') }}" required
                               class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-brand/40
                                      dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                    </div>

                    <div class="space-y-2">
                        <label for="name" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                            Nama Produk
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                               class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-brand/40
                                      dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                    </div>

                    <div class="space-y-2">
                        <label for="uom_id" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                            Satuan Unit (UOM)
                        </label>
                        <select id="uom_id" name="uom_id" required
                                class="w-full appearance-none bg-white border border-slate-200 rounded-xl h-11 px-3 pr-9 text-sm
                                       focus:outline-none focus:ring-2 focus:ring-brand/40
                                       dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                            <option value="">Pilih Satuan</option>
                            @foreach($uoms as $uom)
                                <option value="{{ $uom->id }}" @selected(old('uom_id') == $uom->id)>{{ $uom->name }} ({{$uom->code}})</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- ===== INPUT HPP & HARGA JUAL ===== --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label for="hpp" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                                Harga Beli (HPP)
                            </label>
                            <input type="number" name="hpp" id="hpp" value="{{ old('hpp') }}" placeholder="Contoh: 55000" step="1"
                                   class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-brand/40
                                          dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                        </div>
                        <div class="space-y-2">
                            <label for="selling_price" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                                Harga Jual
                            </label>
                            <input type="number" name="selling_price" id="selling_price" value="{{ old('selling_price') }}" placeholder="Contoh: 75000" step="1"
                                   class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-brand/40
                                          dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                        </div>
                    </div>
                    {{-- ================================== --}}
                </div>
            </div>
        </div>

        <!-- Bagian Kategori -->
        <div class="rounded-2xl border bg-white shadow-card border-slate-200
                    dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            <div class="p-6 md:p-7 space-y-4">
                <div>
                    <h2 class="text-base font-semibold">Kategori</h2>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Pilih kategori yang sudah ada, atau buat yang baru.</p>
                </div>

                <div id="category-selection" class="space-y-3">
                    <div class="space-y-2">
                        <label for="category_id" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                            Kategori yang Tersedia
                        </label>
                        <select id="category_id" name="category_id"
                                class="w-full appearance-none bg-white border border-slate-200 rounded-xl h-11 px-3 pr-9 text-sm
                                       focus:outline-none focus:ring-2 focus:ring-brand/40
                                       dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                            <option value="">Pilih Kategori</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        Tidak menemukan kategori?
                        <button type="button" id="show-new-category-btn"
                                class="inline-flex items-center h-8 px-3 rounded-lg border hover:bg-slate-100 border-slate-200
                                       dark:hover:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
                            Buat Baru
                        </button>
                    </p>
                </div>

                <div id="new-category-form" class="space-y-3">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label for="new_category_name" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                                Nama Kategori Baru
                            </label>
                            <input type="text" name="new_category_name" id="new_category_name" value="{{ old('new_category_name') }}"
                                   class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-brand/40
                                          dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                        </div>
                        <div class="space-y-2">
                            <label for="new_category_code" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">
                                Kode Kategori Baru
                            </label>
                            <input type="text" name="new_category_code" id="new_category_code" value="{{ old('new_category_code') }}"
                                   class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-brand/40
                                          dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                        </div>
                    </div>
                    <button type="button" id="cancel-new-category-btn"
                            class="inline-flex items-center h-10 px-4 rounded-xl border hover:bg-slate-100 border-slate-200
                                   dark:hover:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
                        Batal
                    </button>
                </div>
            </div>
        </div>

        <!-- Bagian Atribut Tambahan -->
        <div class="rounded-2xl border bg-white shadow-card border-slate-200
                    dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            <div class="p-6 md:p-7 space-y-4">
                <h2 class="text-base font-semibold">Atribut Tambahan (Opsional)</h2>

                <div class="grid md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label for="material" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Material</label>
                        <input type="text" name="material" id="material" value="{{ old('material') }}"
                               class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-brand/40
                                      dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                    </div>

                    <div class="space-y-2">
                        <label for="series" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Series</label>
                        <input type="text" name="series" id="series" value="{{ old('series') }}"
                               class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-brand/40
                                      dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                    </div>

                    <div class="space-y-2">
                        <label for="length_cm" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Panjang (cm)</label>
                        <input type="number" name="length_cm" id="length_cm" value="{{ old('length_cm') }}"
                               class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-brand/40
                                      dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                    </div>

                    <div class="space-y-2">
                        <label for="width_mm" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Lebar (mm)</label>
                        <input type="number" name="width_mm" id="width_mm" value="{{ old('width_mm') }}"
                               class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-brand/40
                                      dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                    </div>

                    <div class="space-y-2">
                        <label for="thickness_mm" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Tebal (mm)</label>
                        <input type="number" step="0.01" name="thickness_mm" id="thickness_mm" value="{{ old('thickness_mm') }}"
                               class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-brand/40
                                      dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                    </div>

                    <div class="space-y-2">
                        <label for="barcode" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Barcode</label>
                        <input type="text" name="barcode" id="barcode" value="{{ old('barcode') }}"
                               class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-brand/40
                                      dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
                    </div>

                    <div class="md:col-span-2 space-y-2">
                        <label for="notes" class="block text-xs uppercase tracking-wide text-slate-600 dark:text-slate-400">Catatan Tambahan</label>
                        <textarea id="notes" name="notes" rows="3"
                                  class="w-full bg-white border border-slate-200 rounded-xl p-3 text-sm
                                         focus:outline-none focus:ring-2 focus:ring-brand/40
                                         dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
```
Replace with:
```blade
        @include('products._form')
```

- [ ] **Step 3: Clear compiled view cache and manually verify the create page still works**

```bash
php artisan view:clear
```
Then: `php artisan serve`, login as owner (`owner`/`owner`), open `/products/create`, confirm the three cards (Informasi Dasar & Harga / Kategori / Atribut Tambahan) render exactly as before, fill SKU `TEST-PARTIAL-01`, name `Test Partial Refactor`, pick any UOM, HPP `1000`, harga jual `1500`, pick an existing category, submit. Expect redirect back to `/products/create` with the flash message `Produk "Test Partial Refactor" berhasil didaftarkan.` and no PHP errors (check `storage/logs/laravel.log` if anything looks wrong).

- [ ] **Step 4: Commit**

```bash
git add resources/views/products/_form.blade.php resources/views/products/create.blade.php
git commit -m "refactor: extract product form fields into shared partial

Prep for the upcoming edit page — products/_form.blade.php now holds the
SKU/kategori/atribut fields so create and edit render identical markup
instead of duplicating ~200 lines of Blade.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

### Task 3: Edit product (owner)

**Files:**
- Modify: `routes/web.php` (new route group)
- Modify: `app/Http/Controllers/ProductController.php` (add `edit()`, `update()`)
- Create: `resources/views/products/edit.blade.php`

**Interfaces:**
- Consumes: `products._form` partial from Task 2 (expects `$categories`, `$uoms` in scope, optional `$product`).
- Produces: named routes `products.edit` (GET, param `{id}`) and `products.update` (PUT, param `{id}`) — Task 4's list view links to `products.edit`. Controller methods `ProductController::edit($id)` and `ProductController::update(Request $request, $id)`.

- [ ] **Step 1: Add the routes**

In `routes/web.php`, find:
```php
});

/* ========== AKUNTANSI (OWNER ONLY) ========== */
```
Replace with:
```php
});

/* ========== PRODUK (OWNER ONLY - CRUD) ========== */
Route::middleware(['auth','role:1'])->group(function () {
    Route::get('/products/{id}/edit', [\App\Http\Controllers\ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{id}',      [\App\Http\Controllers\ProductController::class, 'update'])->name('products.update');
});

/* ========== AKUNTANSI (OWNER ONLY) ========== */
```

- [ ] **Step 2: Add `edit()` and `update()` to `ProductController`**

In `app/Http/Controllers/ProductController.php`, find the closing brace of the class (the final `}` after `store()`'s closing `}` on line 138):
```php
        } catch (\Exception $e) {
            DB::rollBack();
            // Kembalikan ke form dengan error dan input yang sudah diisi
            return redirect()->back()->withInput()->with('error', 'Gagal mendaftarkan produk: ' . $e->getMessage());
        }
    }
}
```
Replace with:
```php
        } catch (\Exception $e) {
            DB::rollBack();
            // Kembalikan ke form dengan error dan input yang sudah diisi
            return redirect()->back()->withInput()->with('error', 'Gagal mendaftarkan produk: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan form edit produk (owner).
     */
    public function edit($id)
    {
        $product = DB::table('products')->where('id', $id)->first();
        abort_if(!$product, 404);

        $categories = DB::table('product_categories')->orderBy('name')->get();
        $uoms       = DB::table('uoms')->orderBy('name')->get();

        return view('products.edit', [
            'product'    => $product,
            'categories' => $categories,
            'uoms'       => $uoms,
        ]);
    }

    /**
     * Menyimpan perubahan data produk (owner).
     */
    public function update(Request $request, $id)
    {
        $product = DB::table('products')->where('id', $id)->first();
        abort_if(!$product, 404);

        $data = $request->validate([
            'sku'             => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($id)],
            'name'            => ['required', 'string', 'max:160'],
            'uom_id'          => ['required', 'integer', 'exists:uoms,id'],
            'hpp'             => ['required', 'numeric', 'min:0'],
            'selling_price'   => ['required', 'numeric', 'min:0'],

            'category_id'       => ['nullable', 'required_without:new_category_name', 'exists:product_categories,id'],
            'new_category_name' => ['nullable', 'required_without:category_id', 'string', 'max:100', 'unique:product_categories,name'],
            'new_category_code' => ['nullable', 'required_with:new_category_name', 'string', 'max:20', 'unique:product_categories,code'],

            'material'        => ['nullable', 'string', 'max:30'],
            'series'          => ['nullable', 'string', 'max:60'],
            'pattern_code'    => ['nullable', 'string', 'max:60'],
            'finish'          => ['nullable', 'string', 'max:40'],
            'length_cm'       => ['nullable', 'integer'],
            'width_mm'        => ['nullable', 'integer'],
            'thickness_mm'    => ['nullable', 'numeric'],
            'barcode'         => ['nullable', 'string', 'max:64', Rule::unique('products', 'barcode')->ignore($id)],
            'notes'           => ['nullable', 'string'],
        ], [
            'sku.unique' => 'SKU ini sudah digunakan oleh produk lain.',
            'category_id.required_without' => 'Pilih kategori atau isi nama kategori baru.',
            'new_category_name.required_without' => 'Pilih kategori atau isi nama kategori baru.',
            'new_category_name.unique' => 'Nama kategori ini sudah ada.',
            'new_category_code.required_with' => 'Kode untuk kategori baru wajib diisi.',
            'new_category_code.unique' => 'Kode kategori ini sudah ada.',
            'barcode.unique' => 'Barcode ini sudah digunakan oleh produk lain.',
        ]);

        $userId = (int) Auth::id();

        try {
            DB::beginTransaction();

            $categoryId = $data['category_id'] ?? null;

            if (!empty($data['new_category_name'])) {
                $categoryId = DB::table('product_categories')->insertGetId([
                    'code' => $data['new_category_code'],
                    'name' => $data['new_category_name'],
                ]);
            }

            $notes = $data['notes'] ?? '';
            $notes = preg_replace('/hpp\s*:\s*[0-9\.]+\s*/i', '', $notes);

            $productData = [
                'sku'            => $data['sku'],
                'name'           => $data['name'],
                'category_id'    => $categoryId,
                'uom_id'         => $data['uom_id'],
                'hpp'            => (float) $data['hpp'],
                'selling_price'  => (float) $data['selling_price'],
                'notes'          => $notes,
                'material'       => $data['material'] ?? null,
                'series'         => $data['series'] ?? null,
                'pattern_code'   => $data['pattern_code'] ?? null,
                'finish'         => $data['finish'] ?? null,
                'length_cm'      => $data['length_cm'] ?? null,
                'width_mm'       => $data['width_mm'] ?? null,
                'thickness_mm'   => $data['thickness_mm'] ?? null,
                'barcode'        => $data['barcode'] ?? null,
            ];

            DB::table('products')->where('id', $id)->update($productData);

            DB::table('audit_logs')->insert([
                'event'      => 'PRODUCT_UPDATED',
                'user_id'    => $userId,
                'ref_type'   => 'PRODUCT',
                'ref_id'     => $id,
                'payload'    => json_encode(['before' => (array) $product, 'after' => $productData]),
                'created_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('products.edit', $id)->with('success', 'Produk "' . $data['name'] . '" berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui produk: ' . $e->getMessage());
        }
    }
}
```

- [ ] **Step 3: Create the edit view**

Create `resources/views/products/edit.blade.php`:
```blade
@extends('layouts.dashboard', ['title' => 'Edit Produk'])

@section('content')
<div class="space-y-6">
    <div class="space-y-2">
        <h1 class="text-xl md:text-2xl font-semibold">Edit Produk</h1>
        <p class="text-slate-600 dark:text-slate-400">Perbarui data produk "{{ $product->name }}".</p>
    </div>

    {{-- Notifikasi Sukses dan Error --}}
    @if (session('success'))
        <div role="alert"
             class="rounded-xl border px-4 py-3 text-sm bg-emerald-50 border-emerald-200 text-emerald-700
                    dark:bg-emerald-500/15 dark:border-emerald-500/30 dark:text-emerald-200">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if (session('error') || $errors->any())
        <div role="alert"
             class="rounded-xl border px-4 py-3 text-sm bg-rose-50 border-rose-200 text-rose-700
                    dark:bg-rose-500/15 dark:border-rose-500/30 dark:text-rose-200">
            <p class="font-semibold">Terjadi Kesalahan</p>
            @if(session('error'))
                <p class="mt-1">{{ session('error') }}</p>
            @else
                <ul class="list-disc pl-5 mt-1 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li class="leading-6">{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    <form action="{{ route('products.update', $product->id) }}" method="POST" id="formProduct">
        @csrf
        @method('PUT')

        @include('products._form')

        <!-- Tombol Aksi -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('products.create') }}"
               class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border hover:bg-slate-100 border-slate-200
                      dark:hover:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
                Batal
            </a>
            <button type="submit" id="btnSubmitProduct"
                    class="inline-flex items-center gap-2 h-11 px-5 rounded-xl border text-white bg-brand hover:bg-brand/90 border-transparent
                           dark:bg-brandDark dark:hover:bg-brandDark/90">
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>

{{-- JS: toggle kategori baru, normalisasi angka, trim, cegah submit ganda --}}
<script>
(function(){
  const selWrap = document.getElementById('category-selection');
  const newWrap = document.getElementById('new-category-form');
  const btnShow = document.getElementById('show-new-category-btn');
  const btnCancel = document.getElementById('cancel-new-category-btn');

  const hasOldNewCat = !!("{{ old('new_category_name') }}" || "{{ old('new_category_code') }}");
  function showNewCatForm(){
    selWrap?.classList.add('hidden');
    newWrap?.classList.remove('hidden');
  }
  function showSelectForm(){
    newWrap?.classList.add('hidden');
    selWrap?.classList.remove('hidden');
    const name = document.getElementById('new_category_name');
    const code = document.getElementById('new_category_code');
    if (name) name.value = '';
    if (code) code.value = '';
  }
  if (hasOldNewCat) showNewCatForm(); else showSelectForm();
  btnShow?.addEventListener('click', showNewCatForm);
  btnCancel?.addEventListener('click', showSelectForm);

  const form = document.getElementById('formProduct');
  const btn  = document.getElementById('btnSubmitProduct');
  form?.addEventListener('submit', () => {
    ['sku','name','new_category_name','new_category_code','material','series','barcode','notes'].forEach(id => {
      const el = document.getElementById(id);
      if (el && typeof el.value === 'string') el.value = el.value.trim();
    });
    ['hpp','length_cm','width_mm','thickness_mm'].forEach(id => {
      const el = document.getElementById(id);
      if (el && typeof el.value === 'string') el.value = el.value.replace(',', '.');
    });
    if (btn) {
      btn.disabled = true;
      btn.classList.add('opacity-70','cursor-not-allowed');
    }
  });
})();
</script>
@endsection
```

- [ ] **Step 4: Verify routes registered**

```bash
php artisan route:list --name=products
```
Expected: rows for `products.create`, `products.store` (existing) plus new `GET products/{id}/edit … products.edit` and `PUT products/{id} … products.update`.

- [ ] **Step 5: Manual browser verification**

```bash
php artisan tinker --execute="echo DB::table('products')->where('sku','TEST-PARTIAL-01')->value('id');"
```
Note the printed ID (from Task 2's test product). Login as owner, visit `/products/{that-id}/edit`, confirm SKU/name/HPP/harga jual are prefilled with `TEST-PARTIAL-01` / `Test Partial Refactor` / `1000` / `1500`. Change harga jual to `2000`, submit. Expect: redirected back to the same edit page, flash message `Produk "Test Partial Refactor" berhasil diperbarui.`, and the harga jual field now shows `2000` on reload.

Confirm the audit trail:
```bash
php artisan tinker --execute="
\$id = DB::table('products')->where('sku','TEST-PARTIAL-01')->value('id');
print_r(DB::table('audit_logs')->where('ref_type','PRODUCT')->where('ref_id',\$id)->where('event','PRODUCT_UPDATED')->latest('id')->first());
"
```
Expected: one row with `event = PRODUCT_UPDATED` and a `payload` containing `"selling_price":2000` under `after`.

- [ ] **Step 6: Commit**

```bash
git add routes/web.php app/Http/Controllers/ProductController.php resources/views/products/edit.blade.php
git commit -m "feat: add product edit (owner)

Adds ProductController::edit/update behind role:1, reusing the
products._form partial. Redirects back to the edit page on save
(no dependency on the not-yet-built product list page).

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

### Task 4: Product list + deactivate/reactivate (owner) + sidebar menu

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/ProductController.php` (add `index()`, `toggleActive()`)
- Create: `resources/views/products/index.blade.php`
- Modify: `resources/views/layouts/dashboard.blade.php` (add sidebar link)

**Interfaces:**
- Consumes: route `products.edit` (Task 3) for the per-row "Edit" link.
- Produces: named routes `products.index` (GET) and `products.toggleActive` (POST, param `{id}`). No later task depends on these.

- [ ] **Step 1: Add the routes**

In `routes/web.php`, find the block added in Task 3:
```php
/* ========== PRODUK (OWNER ONLY - CRUD) ========== */
Route::middleware(['auth','role:1'])->group(function () {
    Route::get('/products/{id}/edit', [\App\Http\Controllers\ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{id}',      [\App\Http\Controllers\ProductController::class, 'update'])->name('products.update');
});
```
Replace with:
```php
/* ========== PRODUK (OWNER ONLY - CRUD) ========== */
Route::middleware(['auth','role:1'])->group(function () {
    Route::get('/products',                     [\App\Http\Controllers\ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{id}/edit',            [\App\Http\Controllers\ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{id}',                 [\App\Http\Controllers\ProductController::class, 'update'])->name('products.update');
    Route::post('/products/{id}/toggle-active',  [\App\Http\Controllers\ProductController::class, 'toggleActive'])->name('products.toggleActive');
});
```

- [ ] **Step 2: Add `index()` and `toggleActive()` to `ProductController`**

In `app/Http/Controllers/ProductController.php`, find the closing brace after `update()` (added in Task 3):
```php
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui produk: ' . $e->getMessage());
        }
    }
}
```
Replace with:
```php
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui produk: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan daftar produk untuk owner (search, filter kategori & status).
     */
    public function index(Request $request)
    {
        $q      = trim((string) $request->get('q', ''));
        $catId  = $request->get('category_id');
        $status = $request->get('status', 'all');

        $query = DB::table('products as p')
            ->leftJoin('product_categories as c', 'p.category_id', '=', 'c.id')
            ->select('p.id', 'p.sku', 'p.name', 'p.hpp', 'p.selling_price', 'p.is_active', 'c.name as category_name');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('p.sku', 'like', "%{$q}%")
                  ->orWhere('p.name', 'like', "%{$q}%");
            });
        }
        if (!empty($catId)) {
            $query->where('p.category_id', (int) $catId);
        }
        if ($status === 'active') {
            $query->where('p.is_active', 1);
        } elseif ($status === 'inactive') {
            $query->where('p.is_active', 0);
        }

        $products = $query->orderBy('p.name')->paginate(20)->withQueryString();

        $categories = DB::table('product_categories')->orderBy('name')->get();

        return view('products.index', [
            'products'   => $products,
            'categories' => $categories,
            'q'          => $q,
            'catId'      => $catId,
            'status'     => $status,
        ]);
    }

    /**
     * Aktifkan / nonaktifkan produk. Pengganti hapus asli agar riwayat
     * transaksi/stok yang mereferensikan produk ini tidak rusak.
     */
    public function toggleActive($id)
    {
        $product = DB::table('products')->where('id', $id)->first();
        abort_if(!$product, 404);

        $newStatus = $product->is_active ? 0 : 1;
        $userId    = (int) Auth::id();

        DB::transaction(function () use ($id, $newStatus, $userId) {
            DB::table('products')->where('id', $id)->update(['is_active' => $newStatus]);

            DB::table('audit_logs')->insert([
                'event'      => $newStatus ? 'PRODUCT_ACTIVATED' : 'PRODUCT_DEACTIVATED',
                'user_id'    => $userId,
                'ref_type'   => 'PRODUCT',
                'ref_id'     => $id,
                'payload'    => json_encode(['is_active' => $newStatus]),
                'created_at' => now(),
            ]);
        });

        $msg = $newStatus ? 'Produk diaktifkan kembali.' : 'Produk dinonaktifkan.';
        return back()->with('success', $msg);
    }
}
```

- [ ] **Step 3: Create the list view**

Create `resources/views/products/index.blade.php`:
```blade
@extends('layouts.dashboard', ['title' => 'Kelola Produk'])

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="space-y-1">
            <h1 class="text-xl md:text-2xl font-semibold">Kelola Produk</h1>
            <p class="text-slate-600 dark:text-slate-400">Daftar seluruh produk, ubah data, atau nonaktifkan produk yang sudah tidak dijual.</p>
        </div>
        <a href="{{ route('products.create') }}"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border text-white bg-brand hover:bg-brand/90 border-transparent
                  dark:bg-brandDark dark:hover:bg-brandDark/90">
            + Tambah Produk
        </a>
    </div>

    @if (session('success'))
        <div role="alert"
             class="rounded-xl border px-4 py-3 text-sm bg-emerald-50 border-emerald-200 text-emerald-700
                    dark:bg-emerald-500/15 dark:border-emerald-500/30 dark:text-emerald-200">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <form method="GET" action="{{ route('products.index') }}" class="flex flex-wrap gap-3">
        <input type="text" name="q" value="{{ $q }}" placeholder="Cari SKU / nama produk..."
               class="flex-1 min-w-[200px] h-10 px-3 rounded-xl border border-slate-200 text-sm
                      focus:outline-none focus:ring-2 focus:ring-brand/40
                      dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">

        <select name="category_id"
                class="h-10 px-3 rounded-xl border border-slate-200 text-sm
                       dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            <option value="">Semua Kategori</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected($catId == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>

        <select name="status"
                class="h-10 px-3 rounded-xl border border-slate-200 text-sm
                       dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
            <option value="all" @selected($status === 'all')>Semua Status</option>
            <option value="active" @selected($status === 'active')>Aktif</option>
            <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
        </select>

        <button type="submit"
                class="h-10 px-4 rounded-xl border border-slate-200 text-sm hover:bg-slate-100 dark:hover:bg-white/5 dark:border-[rgba(148,163,184,.12)]">
            Filter
        </button>
    </form>

    <div class="rounded-2xl border bg-white shadow-card border-slate-200 overflow-x-auto
                dark:bg-[#0f172a] dark:border-[rgba(148,163,184,.12)]">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-[rgba(148,163,184,.12)]">
                    <th class="px-4 py-3">SKU</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Kategori</th>
                    <th class="px-4 py-3 text-right">HPP</th>
                    <th class="px-4 py-3 text-right">Harga Jual</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr class="border-b border-slate-100 dark:border-[rgba(148,163,184,.08)]">
                        <td class="px-4 py-3">{{ $product->sku }}</td>
                        <td class="px-4 py-3">{{ $product->name }}</td>
                        <td class="px-4 py-3">{{ $product->category_name ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $product->hpp, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $product->selling_price, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            @if($product->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200">Aktif</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-slate-300">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('products.edit', $product->id) }}" class="text-brand hover:underline">Edit</a>
                                <form action="{{ route('products.toggleActive', $product->id) }}" method="POST"
                                      onsubmit="return confirm('{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }} produk ini?');">
                                    @csrf
                                    <button type="submit" class="text-slate-500 hover:underline">
                                        {{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-slate-500">Tidak ada produk yang cocok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $products->links() }}
</div>
@endsection
```

- [ ] **Step 4: Add the sidebar link**

In `resources/views/layouts/dashboard.blade.php`, find the Master Data block:
```blade
      {{-- Master Data --}}
      <div>
        <button type="button" data-submenu="master"
          class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-sm transition
                 {{ $isParent('admin.*', 'suppliers.*') ? 'text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
          <span class="flex items-center gap-3">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4"/>
            </svg>
            Master Data
          </span>
          <svg class="h-4 w-4 text-slate-400 transition-transform duration-200 {{ $isParent('admin.*', 'suppliers.*') ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div class="ml-3 mt-1 space-y-1 {{ $isParent('admin.*', 'suppliers.*') ? '' : 'hidden' }}" data-submenu-target="master">
          <a href="{{ route('suppliers.create') }}" class="block px-3 py-2 rounded-lg text-sm {{ $isActive('suppliers.create') }}">Buat Supplier</a>
          <a href="{{ route('admin.products.import.form') }}" class="block px-3 py-2 rounded-lg text-sm {{ $isActive('admin.products.import.form') }}">Import Data Produk</a>
          <a href="{{ route('admin.branches.create') }}" class="block px-3 py-2 rounded-lg text-sm {{ $isActive('admin.branches.create') }}">Buat Cabang Baru</a>
        </div>
      </div>
```
Replace with:
```blade
      {{-- Master Data --}}
      <div>
        <button type="button" data-submenu="master"
          class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-sm transition
                 {{ $isParent('admin.*', 'suppliers.*', 'products.*') ? 'text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
          <span class="flex items-center gap-3">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4"/>
            </svg>
            Master Data
          </span>
          <svg class="h-4 w-4 text-slate-400 transition-transform duration-200 {{ $isParent('admin.*', 'suppliers.*', 'products.*') ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div class="ml-3 mt-1 space-y-1 {{ $isParent('admin.*', 'suppliers.*', 'products.*') ? '' : 'hidden' }}" data-submenu-target="master">
          <a href="{{ route('products.index') }}" class="block px-3 py-2 rounded-lg text-sm {{ $isActive('products.index') }}">Kelola Produk</a>
          <a href="{{ route('suppliers.create') }}" class="block px-3 py-2 rounded-lg text-sm {{ $isActive('suppliers.create') }}">Buat Supplier</a>
          <a href="{{ route('admin.products.import.form') }}" class="block px-3 py-2 rounded-lg text-sm {{ $isActive('admin.products.import.form') }}">Import Data Produk</a>
          <a href="{{ route('admin.branches.create') }}" class="block px-3 py-2 rounded-lg text-sm {{ $isActive('admin.branches.create') }}">Buat Cabang Baru</a>
        </div>
      </div>
```

- [ ] **Step 5: Verify routes registered**

```bash
php artisan route:list --name=products
```
Expected: now also lists `GET products … products.index` and `POST products/{id}/toggle-active … products.toggleActive`.

- [ ] **Step 6: Manual browser verification — list, search, filter, sidebar**

Login as owner, confirm the sidebar's **Master Data** submenu now shows **Kelola Produk** as the first item and highlights the submenu when active. Click it (or visit `/products`), confirm the table lists products with SKU/name/category/HPP/harga jual/status. Type `TEST-PARTIAL` in the search box and submit — confirm only `TEST-PARTIAL-01` (from Task 2/3) shows. Clear search, filter Status = `Nonaktif` — expect empty table (no inactive products yet).

- [ ] **Step 7: Manual browser verification — deactivate/reactivate, tying back to Task 1's catalog fix**

On `/products`, find `TEST-PARTIAL-01`, click **Nonaktifkan**, confirm the `confirm()` dialog, accept it. Expect: page reloads, flash message "Produk dinonaktifkan.", status badge now shows **Nonaktif**, row action now reads **Aktifkan**.

Confirm the audit log:
```bash
php artisan tinker --execute="
\$id = DB::table('products')->where('sku','TEST-PARTIAL-01')->value('id');
print_r(DB::table('audit_logs')->where('ref_type','PRODUCT')->where('ref_id',\$id)->where('event','PRODUCT_DEACTIVATED')->latest('id')->first());
echo 'is_active now: ' . DB::table('products')->where('id',\$id)->value('is_active') . PHP_EOL;
"
```
Expected: one `PRODUCT_DEACTIVATED` row, `is_active now: 0`.

Then, still logged in as owner (or switch to `kasir`/`kasir`), open `/kasir/pos`, search for `Test Partial Refactor` — confirm it does **not** appear (this is Task 1's fix actually taking effect through this feature). Go back to `/products`, click **Aktifkan** on the same product to restore it, confirm status flips back to **Aktif** and it reappears in `/kasir/pos`.

- [ ] **Step 8: Clean up the test product**

```bash
php artisan tinker --execute="
\$id = DB::table('products')->where('sku','TEST-PARTIAL-01')->value('id');
DB::table('audit_logs')->where('ref_type','PRODUCT')->where('ref_id',\$id)->delete();
DB::table('products')->where('id',\$id)->delete();
echo 'cleaned up product id ' . \$id . PHP_EOL;
"
```
(Safe here — this test product has no `pos_sale_lines`/`stock_moves`/`project_boms` references since it was only ever used for manual verification in this plan, never sold or stocked.)

- [ ] **Step 9: Commit**

```bash
git add routes/web.php app/Http/Controllers/ProductController.php resources/views/products/index.blade.php resources/views/layouts/dashboard.blade.php
git commit -m "feat: add product list + deactivate/reactivate (owner)

Adds ProductController::index/toggleActive behind role:1, a searchable
product list view, and a 'Kelola Produk' sidebar entry under Master
Data. Deactivating now visibly removes a product from the POS catalog,
closing the loop with the is_active fix from the earlier commit.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

## Post-plan smoke check (optional but recommended)

After all 4 tasks land, re-run the existing automated suite once to confirm nothing in `ProductController`/`PosController`/`ProductApiController` broke any of the accounting-critical tests referenced in `docs/PRODUCTION_READINESS_MAPPING.md`:
```bash
php artisan test
```
Expected: same pass count as before this plan started (this feature touches no accounting/journal code paths, so no test should newly fail).
