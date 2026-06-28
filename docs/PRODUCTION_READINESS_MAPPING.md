# Mapping Kesiapan Produksi — Role Kasir & Owner

Status audit per 2026-06-28. Fokus: kebenaran akuntansi (double-entry) + kebenaran kode +
dukungan terhadap proses bisnis. Lewati role KC (tidak dipakai).

Prioritas:
- **P0 Blocker** — integritas data/akuntansi rusak. Wajib sebelum prod.
- **P1 High** — kebenaran akuntansi / gap proses bisnis penting.
- **P2 Medium** — hardening produksi.
- **DECISION** — butuh keputusan bisnis pemilik sebelum scope dikunci.

---

## Status Implementasi (update 2026-06-28)

Test harness: MariaDB `final_pvc_test` (skema disalin dari DB asli), feature test via
`tests/BusinessTestCase.php`. Jalankan: `php artisan test`.

**SELESAI & terverifikasi (13 test, 132 assertion hijau — `php artisan test`):**
- ✅ **A1** — jurnal POS dipindah ke dalam `DB::transaction` (atomik). Test: `PosSaleAtomicityTest`.
- ✅ **A2** — `billPayAdd` scope diperbaiki; penerimaan kas cicilan kini terbukukan. Test: `ProjectInstallmentTest`.
- ✅ **A3** — pengakuan pendapatan proyek jadi **akrual** (DR Piutang penuh; pembayaran → DR Kas/Bank, CR Piutang) via `journalProjectBilling`. Test: `ProjectBillingTest`, `ProjectInstallmentTest`.
- ✅ **A7** — **retur penjualan**: route `kasir.history.refund` + `PosController::refund`; balikkan stok + jurnal pembalik revenue (4900) & HPP (1300/5100 via `journalReturnToInventory`). Test: `PosRefundTest`. (UI tombol belum dibuat.)
- ✅ **A8** — pendapatan jasa proyek dipisah ke **4200** (bukan 4100). Test: `ProjectBillingTest`.
- ✅ **B1** — dashboard owner `getRecentActivity` status `'PAID'`. Test: `OwnerDashboardTest`.
- ✅ **B5** — POS menolak jual produk tanpa harga jual. Test: `PosSellingPriceGuardTest`.
- ✅ **B6** — material proyek ditagih di **harga jual** (margin), COGS tetap HPP. Test: `ProjectBillingTest`.
- ✅ **F1** — **diskon per nota**: kolom `pos_sales.discount` (migrasi `2026_06_28_000001`), pendapatan diakui netto. Test: `PosDiscountTest`.
- ✅ **B2** — anti-oversell: cek+kurang stok di dalam transaksi dengan `lockForUpdate` + guard non-negatif (lokasi jual). Test: `PosOversellTest`.
- ✅ **Atomicity kas** — `CashController::storeOut`/`storeAdjust` dibungkus transaksi (mutasi kas batal bila jurnal gagal). Test: `CashExpenseAtomicityTest`.
- ✅ **Atomicity pembelian** — `DirectPurchaseController::store` jurnal pembelian dipindah ke dalam transaksi.
- ✅ **A7-UI** — halaman konfirmasi retur server-rendered (`kasir.history.refund.confirm` + `kasir/refund_confirm.blade.php`) + tombol "Retur" di panel detail riwayat. Test: `RefundConfirmPageTest`.
- ✅ **A6** — laporan **Rekonsiliasi Kas** owner (GL Kas 1100 vs kas operasional per cabang) + tertaut di menu. Test: `CashReconciliationTest`.
- ✅ **A5** — `hpp` & `selling_price` wajib saat buat produk (cegah COGS=0 / laba overstated). Test: `ProductHppRequiredTest`. (Catatan: `ProductImportController` belum disamakan.)
- ➕ `journalCollectPayment` kini sadar-metode (TRANSFER→Bank 1110, lainnya→Kas 1100).

> ⚠️ **DEPLOY:** jalankan `php artisan migrate` di produksi **sebelum** kode aktif — tanpa kolom `pos_sales.discount`, finalisasi POS akan error. (Sudah ada di `final_pvc_test`.)

**SELESAI ronde terakhir:**
- ✅ **A4** moving-average persediaan — form pembelian kini menangkap **harga beli** (`items.*.price`, field form `unit_price`→`price`), HPP di-update **rata-rata tertimbang** tiap penerimaan. Test: `ProductMovingAverageTest`.
- ✅ **H2** — unique index `entry_no` **sudah ada** di DB asli (terverifikasi audit); ditambah **retry** di `createEntry` agar tabrakan konkuren tak bikin finalize crash.
- ✅ **B3** — guard anti double-submit (`Cache::lock` per user, driver prod `database`) di POS & project finalize. Test: `PosDoubleSubmitTest`.
- ✅ **ProductImport** — produk baru wajib HPP & harga jual > 0 (konsisten A5).

**Audit DB asli (read-only):** tak ada duplikat `entry_no`, stok negatif, atau jurnal tak-balance. ⚠️ **5 produk aktif tanpa HPP & 5 tanpa harga jual** → perlu **bersih-bersih master data** (isi nilai sebenarnya) sebelum/sesudah go-live; tak bisa ditebak otomatis.

**Sengaja TIDAK dikerjakan (keputusan/irrelevan):**
- **F2** tax-ready — PPN belum dipakai (keputusan D1); ditunda sampai dibutuhkan.
- **B4** presisi meter — POS hanya qty integer; tak relevan.
- **CHECK `qty≥0`** di DB — ditunda: perlu audit alur transfer/adjust dulu agar tak memunculkan error di prod (POS sudah di-guard di kode via B2).

---

## A. Kebenaran Akuntansi & Integritas Data

| ID | Prio | Lokasi | Masalah | Solusi |
|----|------|--------|---------|--------|
| A1 | P0 | PosController::finalize:547, DirectPurchase::store:124, ProjectController::finalize:1384 | Jurnal otomatis dipanggil **di luar** `DB::transaction`. Bila jurnal gagal (mis. COA hilang → `accountId` throw), transaksi bisnis sudah commit tapi pembukuan tidak → GL tidak balance dengan operasional. | Bungkus operasi bisnis **dan** jurnal dalam satu transaksi atomik; atau commit jurnal di dalam transaksi yang sama. |
| A2 | P0 | ProjectController::billPayAdd:999–1027 | `$appliedAmount` dideklarasi **di dalam** closure `DB::transaction(use($sale,$v))`, dibaca **di luar** closure untuk syarat `journalCollectPayment`. PHP closure tidak membocorkan scope → variabel undefined → **jurnal penerimaan kas cicilan proyek tidak pernah terbentuk**. Pembayaran tercatat di `pos_payments`, tapi Kas/Piutang GL salah. | Kembalikan `appliedAmount` dari closure (atau hitung di luar), lalu jurnal. Terkait erat dengan A3. |
| A3 | P0 | ProjectController::finalize:1388 | `journalPosSale` dipanggil **tanpa array payments** + dengan **total penuh** → fallback men-debit Kas/Piutang sebesar total penuh memakai satu metode. Saat bayar parsial dgn metode CASH, seluruh total masuk **Kas** (kas overstate); revenue diakui penuh walau belum lunas. | Pakai model akrual konsisten: finalize → DR Piutang (penuh), CR Penjualan + CR Jasa; tiap pembayaran (finalize awal & billPayAdd) → DR Kas, CR Piutang. Hindari double-count dengan A2. |
| A4 | P1 | AccountingService (journalPurchase vs journalPosCogs) | Persediaan (1300) di-**debit** saat beli sebesar nilai beli aktual, di-**kredit** saat jual sebesar `hpp` statis produk. Karena beli ≠ hpp statis, saldo GL Persediaan menyimpang dari nilai fisik. Tidak ada moving-average/FIFO. | Terapkan moving-average cost yang di-update saat GRN dan dipakai sebagai COGS; minimal sediakan rekonsiliasi persediaan periodik. |
| A5 | P1 | AccountingService::calculateCogs, DirectPurchase, ProjectController | Sumber HPP berlapis & rapuh: kolom `hpp` → regex parse `notes` (`hpp:xxx`). Bila hpp=0 & notes tak berpola → COGS 0 → laba overstate. | Jadikan kolom `hpp` satu-satunya sumber; migrasi data dari `notes`; validasi `hpp>0` saat input produk. |
| A6 | P1 | CashController::summary vs GL Kas 1100 | Dua sistem kas paralel tidak terrekonsiliasi. `cash_movements` (fisik) dipakai untuk saldo, sedang GL Kas dijurnal terpisah. POS CASH tidak masuk `cash_movements`. Tidak ada cash-up harian. | Tambah laporan rekonsiliasi (GL Kas vs kas fisik) + fitur tutup kas harian. |
| A7 | P1 | AccountingService (refund/service/paySupplier) | `journalPosRefund`, `journalServiceRevenue`, `journalPaySupplier` **tidak pernah dipanggil**. Tidak ada flow retur/refund sama sekali (tak ada route/UI). | **IN-SCOPE (D3):** bangun flow retur penjualan — route + UI kasir/owner + jurnal retur (DR 4900 Retur Penjualan, CR Kas/Piutang) + balikkan stok. Gunakan model `PosRefund` yang sudah ada. |
| A8 | P1 | ProjectController::finalize (SRV-GEN) | Pendapatan **jasa instalasi** masuk via produk SRV-GEN → di-kredit ke **4100 Penjualan Barang**, bukan **4200 Pendapatan Jasa**. Salah klasifikasi pendapatan. | Pisahkan revenue jasa ke 4200 (gunakan `journalServiceRevenue` yang sudah ada). |

---

## B. Bug Kode (non-akuntansi)

| ID | Prio | Lokasi | Masalah | Solusi |
|----|------|--------|---------|--------|
| B1 | P0 | OwnerDashboardController::getRecentActivity | Query `where('status','completed')` padahal status tersimpan `'PAID'` → daftar aktivitas penjualan **selalu kosong**. | Ganti ke `'PAID'`. |
| B2 | P1 | PosController::finalize:474–531 | Cek stok di **luar** transaksi tanpa lock; `decrement` `stock_quants` tanpa `lockForUpdate` → **oversell race** saat 2 kasir bersamaan; stok bisa minus. (Project finalize sudah benar pakai lockForUpdate.) | Pindahkan cek + decrement ke dalam transaksi dengan `lockForUpdate` dan guard `qty >= 0`. |
| B3 | P1 | PosController::finalize, ProjectController::finalize | Tidak ada guard **double-submit** (retry/klik ganda) → duplikasi sale + stock move + jurnal. | Idempotency token per submit + nonaktifkan tombol di klien. |
| B4 | P2 | PosController::availableStock:611 | `floor()` ke int → produk per-meter (desimal) kehilangan presisi (2.5m → 2). | Jangan floor untuk produk `track_by_meter`. |
| B5 | P2 | PosController::resolveSellingPrice / ProductApiController | Fallback `selling_price` kosong → jual seharga `hpp` (margin 0) secara diam-diam. | Wajibkan `selling_price`; tolak jual bila harga jual belum diset. |

---

## C. Keputusan Bisnis — TERKUNCI (2026-06-28)

| ID | Keputusan | Dampak scope |
|----|-----------|--------------|
| D1 | **PPN**: belum ada PPN sekarang, tapi **desain dibuat fleksibel** agar bisa ditambah kemudian. | Tidak ada perhitungan PPN sekarang. Arsitektur totals/jurnal disiapkan tax-ready (titik sisip pajak), tanpa hardcode menutup kemungkinan. → F2 (P2). |
| D2 | **Diskon per nota** (level transaksi, bukan per item). | Tambah fitur diskon total + dampak akuntansi. → F1 (P1). |
| D3 | **Perlu retur penjualan**. | A7 naik jadi in-scope: route + UI + jurnal retur (4900) untuk kasir/owner. → A7 (P1). |
| D4 | **Material proyek pakai harga jual** (ada margin), bukan HPP. | Perbaiki ProjectController::finalize. → B6 (P1). |
| D5 | Multi-cabang: 1 kasir = 1 `default_branch_id` dianggap cukup. | Tidak ada perubahan. |

### Fitur baru / perubahan akibat keputusan

| ID | Prio | Item | Catatan akuntansi |
|----|------|------|-------------------|
| F1 | P1 | **Diskon per nota** di POS (input diskon total saat checkout, simpan di header `pos_sales`). | Revenue (4100) di-kredit **neto** setelah diskon, **atau** pakai akun kontra "Potongan Penjualan". Pilih satu pendekatan & konsisten di laporan. |
| F2 | P2 | **Desain tax-ready** (tanpa PPN sekarang). | Struktur totals memisah `subtotal`/`tax`/`grand_total` dengan `tax=0`; sediakan akun PPN Keluaran (siap pakai, belum dipakai). Tidak mengubah perilaku sekarang. |
| B6 | P1 | ProjectController::finalize:1336 — material proyek pakai `selling_price`, bukan `productHpp`. | Material di-billing seharga jual (revenue), COGS tetap di HPP → muncul margin material. Periksa juga harga leftover. |

---

## D. Hardening Produksi (P2)

| ID | Item |
|----|------|
| H1 | Logging/observability untuk kegagalan jurnal & transaksi. |
| H2 | Constraint DB: unique `entry_no`, FK, CHECK `qty>=0`, index pada kolom filter. |
| H3 | Test otomatis: alur finalize POS & proyek + assert jurnal balance (debit=credit). |
| H4 | Review backup & migrasi sebelum rilis. |

---

## Urutan Eksekusi yang Disarankan (decisions terkunci)

**Fase 1 — P0 Blocker (integritas akuntansi & data):** A1, A2, A3, B1.
**Fase 2 — P1 Akuntansi:** A4 (moving-average), A5 (HPP), A6 (rekonsiliasi kas), A8 (klasifikasi jasa 4200).
**Fase 3 — P1 Fitur akibat keputusan:** A7 (retur penjualan), F1 (diskon per nota), B6 (material proyek harga jual).
**Fase 4 — P1 Concurrency:** B2 (lock oversell), B3 (idempotency).
**Fase 5 — P2 / hardening:** B4, B5, F2 (tax-ready), H1–H4.

Catatan: A2 + A3 sebaiknya dikerjakan bersamaan (desain pengakuan pendapatan proyek yang
konsisten: finalize → DR Piutang penuh, CR Penjualan/Jasa; pembayaran → DR Kas, CR Piutang).
