# Desain — Input Diskon Manual di Checkout Kasir

Status: Approved
Tanggal: 2026-07-12

## Latar Belakang

Backend diskon per nota (fitur F1, lihat `docs/PRODUCTION_READINESS_MAPPING.md`) sudah lengkap &
teruji: `PosController::performFinalize()` membaca `discount` dari body request `kasir.finalize`,
meng-clamp ke `[0, subtotal]`, menyimpan netto ke `pos_sales.discount`/`pos_sales.total`, dan
`AccountingService::journalPosSale()` membukukan pendapatan (4100) **netto** setelah diskon —
dibuktikan oleh `tests/Feature/PosDiscountTest.php` yang lulus. Yang belum ada: UI kasir untuk
benar-benar mengisi nilai diskon ini. Tidak ada satupun input field diskon di `resources/views/kasir/`.

Ada dua halaman kasir dengan arsitektur berbeda:
- **`/kasir/pos`** (single-page, dipakai sehari-hari): modal pembayaran 100% client-side
  (`public/js/pos-new.js`), tidak refresh dari server sampai finalize diklik.
- **`/kasir/checkout`** (legacy): panel ringkasan server-rendered (`_summary.blade.php`), di-refresh
  via AJAX (`refreshCheckoutPanels()`) setiap cart/pembayaran berubah.

## Cakupan

Tambahkan input diskon manual di kedua halaman. **Tidak mengubah** kontrak backend
`performFinalize()` — tetap membaca `discount` dari body request finalize, tidak diubah.

## Desain per Halaman

### `/kasir/pos` — client-side only

- `public/js/pos-new.js`: tambah `let discount = 0;` di scope modal pembayaran (sejajar dengan
  `let payments = [];`). Reset ke 0 saat `openPaymentModal()`.
- `renderPaymentModal(total, paid)`: hitung `net = Math.max(0, total - discount)`, gunakan `net`
  (bukan `total`) untuk hitung `due`/`change`. Tambah field input "Diskon Nota" (opsional) setelah
  blok "Total Belanja", dengan `oninput="updateDiscount()"`. Tambah baris "Diskon" di ringkasan
  bawah modal jika `discount > 0`, dan baris "Total setelah diskon".
- Fungsi baru `updateDiscount()`: baca input, clamp ke `[0, total]`, set variabel `discount`,
  render ulang modal (pola sama seperti `updatePaymentPreview()` yang sudah ada — re-render penuh,
  termasuk potensi hilang fokus input saat mengetik, konsisten dengan perilaku field "Jumlah Bayar"
  yang sudah ada, tidak diperbaiki di sini karena di luar cakupan).
- `processPayment()`: kirim `{ discount }` di body `postJSON(ROUTES.finalize, ...)`.
- **Tidak ada route/endpoint baru** — semuanya di dalam modal, dikirim sekali saat finalize.

### `/kasir/checkout` — session-backed (mengikuti pola cart/payments/customer yang sudah ada)

Karena panel ringkasan halaman ini di-refresh dari server pada setiap mutasi cart/pembayaran,
nilai diskon harus disimpan di session, kalau tidak akan hilang setiap refresh.

- Route baru (grup `role:3` yang sudah ada): `POST /kasir/discount/set` → `kasir.discount.set`.
- `PosController::discountSet(Request $request)`: ambil cart+payments session, hitung
  `$grossTotal` via `totals()` (tidak diubah), clamp `discount = min(max(0,input),$grossTotal)`,
  simpan `session(['pos.discount' => $discount])`, kembalikan `renderCheckoutPartials()`
  (pola sama persis dengan `cartAdd`/`paymentAdd`).
- Helper baru `sessionDiscount(): float` — `(float) session('pos.discount', 0)`.
- `renderCheckoutPartials()` **dan** `checkout()` (GET awal): setelah `totals()` (tidak diubah),
  hitung tambahan `$discount = min(max(0,$this->sessionDiscount()),$grossTotal)`,
  `$netTotal = round($grossTotal-$discount,2)`, `$dueNet = max(0,round($netTotal-$paid,2))`.
  Kirim `discount`, `netTotal` (variabel baru), dan `due` (kini net) ke view
  `kasir.partials._summary`. `performFinalize`/`totals()` sendiri **tidak disentuh** (mencegah
  double-subtract, menjaga test `PosDiscountTest` tetap lulus apa adanya).
- `_summary.blade.php`: tambah baris "Diskon" (dengan input number + tombol kecil/on-blur) dan
  baris "Total setelah diskon", "Sisa Bayar" pakai due net. Tombol Finalisasi tetap pakai due net
  untuk `$canFinalize`.
- `checkout.js`: fungsi baru `checkoutSetDiscount()` — baca input, `postJSON(ROUTES.discountSet,
  {discount})`, refresh panel via `refreshCheckoutPanels(res.html)`. `checkoutFinalize()` diubah
  untuk mengirim nilai diskon saat ini (dibaca dari input field di summary panel) di body
  `postJSON(ROUTES.finalize, {discount})`.

## Verifikasi

- Test otomatis: tidak ada test baru (fitur backend sudah punya `PosDiscountTest`, jalankan ulang
  untuk pastikan tidak regresi). UI ini murni wiring, diverifikasi manual di browser (kedua
  halaman): tambah produk, isi diskon, bayar sesuai netto, finalize, cek `pos_sales.discount` &
  `pos_sales.total` di DB serta jurnal (4100 kredit netto, 5100 debit HPP penuh) — mengulang
  assersi yang sama seperti `PosDiscountTest` tapi lewat UI sungguhan.

## Tidak Dikerjakan

- Tidak mengubah `performFinalize()`/`totals()`/`AccountingService` — sudah benar & teruji.
- Tidak memperbaiki bug kecil pre-existing di mana ringkasan sidebar `/kasir/pos` tidak ikut
  ter-update dari `renderCheckoutPartials()` (id elemen tidak cocok) — di luar cakupan, modal
  pembayaran punya kalkulasi klien sendiri yang tidak bergantung pada sinkronisasi itu.
- Diskon per baris item — tetap per-nota (transaksi), sesuai keputusan bisnis D2 yang sudah terkunci.
