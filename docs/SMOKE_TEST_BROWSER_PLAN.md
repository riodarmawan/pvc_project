# Rencana Smoke Test Browser — Kasir & Owner

Tujuan: memastikan alur prod kasir & owner berjalan benar di UI nyata sebelum go-live.
**Tidak otomatis** — diklik manual di browser. Centang tiap langkah.

---

## 0. Prasyarat (lakukan sekali sebelum mulai)

- [ ] Aplikasi berjalan (`php artisan serve` atau web server) dan bisa dibuka di browser.
- [ ] **Migrasi sudah jalan**: `php artisan migrate` (memastikan kolom `pos_sales.discount` ada). Tanpa ini, finalisasi POS akan error.
- [ ] Akun tersedia:
  - Kasir — username `kasir`, password `kasir` (role_id = 3, punya `default_branch_id`).
  - Owner — username `owner`, password `owner` (role_id = 1).
- [ ] Data minimal di cabang kasir:
  - 1 cabang aktif + lokasi stok bertipe `AVAILABLE`.
  - **Produk A**: HPP 6.000, harga jual 10.000, stok AVAILABLE = 5.
  - **Produk B (uji guard)**: harga jual 0 / kosong (untuk skenario K4).
  - 1 supplier (untuk skenario pembelian).
- [ ] Catat saldo/angka awal dashboard owner agar bisa dibandingkan setelah transaksi.

> Catatan: bila akun/data di atas belum ada, siapkan dulu (lewat seeder atau input manual).
> Disarankan smoke test di **staging / DB salinan**, bukan langsung di data produksi.

---

## A. Skenario KASIR (login: `kasir` / `kasir`)

### K1 — Login & redirect
- [ ] Buka `/login`, masuk sebagai `kasir`.
- **Harapan:** diarahkan ke halaman kasir (`/kasir`). Menu kasir tampil.

### K2 — Penjualan tunai (happy path)
- [ ] Buka POS, tambah **Produk A** qty 2 ke keranjang.
- [ ] Tambah pembayaran **CASH** 20.000, lalu **Finalize**.
- **Harapan:** transaksi sukses, invoice/struk tampil, kembalian 0. Stok Produk A jadi **3**.
- *Memvalidasi: alur POS, jurnal atomik (A1), pengurangan stok aman (B2).*

### K3 — Penjualan dengan diskon per nota
- [ ] Tambah **Produk A** qty 2 (bruto 20.000). Isi **diskon nota** 2.000.
- [ ] Bayar CASH 18.000, **Finalize**.
- **Harapan:** transaksi sukses, total tercatat **18.000** (netto). Stok berkurang 2.
- *Memvalidasi: F1 diskon per nota.*

### K4 — Guard harga jual kosong
- [ ] Coba tambahkan **Produk B** (tanpa harga jual) ke keranjang.
- **Harapan:** ditolak dengan pesan "Harga jual ... belum diset". Item tidak masuk keranjang.
- *Memvalidasi: B5.*

### K5 — Guard stok tidak cukup (anti-oversell)
- [ ] Tambah **Produk A** dengan qty melebihi stok tersedia.
- **Harapan:** ditolak dengan pesan stok tidak cukup. Tidak ada transaksi terbentuk.
- *Memvalidasi: B2.*

### K6 — Kas kecil keluar
- [ ] Buka menu **Kas**, catat pengeluaran (mis. kategori BBM, 50.000).
- **Harapan:** mutasi kas OUT tercatat; saldo kas berkurang sesuai.
- *Memvalidasi: pencatatan kas + jurnal beban atomik.*

### K7 — Retur penjualan (fitur baru)
- [ ] Buka **Riwayat**, klik detail transaksi K2 (status PAID).
- [ ] Klik tombol **Retur** → halaman konfirmasi muncul (rincian item + total).
- [ ] Isi alasan (mis. "barang rusak"), klik **Proses Retur**, konfirmasi dialog.
- **Harapan:** kembali ke riwayat dengan notifikasi sukses. Status transaksi jadi **REFUND**. Stok Produk A **kembali +2**. Tombol Retur tidak lagi muncul untuk transaksi itu.
- *Memvalidasi: A7 (retur + UI).*

### K8 — Proyek instalasi (jika dipakai)
- [ ] Buka **Projects → Buat**, tambah material (Produk A) + 1 jasa, isi customer & pembayaran penuh, **Finalize**.
- **Harapan:** proyek dibuat, tagihan lunas, stok material berkurang, halaman invoice/SJ bisa dibuka.
- *Memvalidasi: akrual proyek (A3), material harga jual (B6), jasa (A8).*

### K9 — Pembelian langsung (moving-average)
- [ ] Buka **Pembelian → Buat**, pilih supplier & cabang, tambah **Produk A** qty 10 dengan **Harga Satuan 8.000**, simpan.
- **Harapan:** form **mewajibkan harga satuan**; pembelian tersimpan; stok Produk A bertambah 10; **HPP Produk A naik** menuju rata-rata tertimbang antara HPP lama & 8.000.
- *Memvalidasi: A4 moving-average + field harga di form.*

---

## B. Skenario OWNER (login: `owner` / `owner`)

### O1 — Login & dashboard
- [ ] Logout kasir, login sebagai `owner`.
- **Harapan:** diarahkan ke `/owner`. Kartu ringkasan (Total Penjualan, Laba Bersih, dll.) tampil tanpa error.
- *Memvalidasi: dashboard owner.*

### O2 — Aktivitas terbaru
- [ ] Lihat panel **Aktivitas Terbaru** di dashboard.
- **Harapan:** penjualan tunai dari K2/K3 **muncul** di daftar aktivitas.
- *Memvalidasi: B1 (status 'PAID').*

### O3 — Laporan Laba Rugi
- [ ] Buka **Laporan → Laba Rugi**, set rentang tanggal mencakup hari ini.
- **Harapan:** Pendapatan, HPP, dan Laba Bersih tampil masuk akal (pendapatan ≈ penjualan netto K2+K3 dikurangi retur K7; HPP sesuai).
- *Memvalidasi: laporan berbasis jurnal.*

### O4 — Rekonsiliasi Kas (laporan baru)
- [ ] Buka **Laporan → Rekonsiliasi Kas**, set rentang tanggal mencakup hari ini.
- **Harapan:** baris per cabang menampilkan **Buku Besar Kas (1100)** dan **Kas Operasional**, dengan **Selisih = 0** (hijau) untuk cabang kasir. Bila ada selisih, tampil merah.
- *Memvalidasi: A6 + konsistensi jurnal kas vs operasional.*

### O5 — Akuntansi (Chart of Accounts & Jurnal)
- [ ] Buka **Akuntansi → Bagan Akun**: daftar akun (1100, 1200, 4100, 4200, 5100, dst.) tampil.
- [ ] Buka **Akuntansi → Jurnal**: entri otomatis dari transaksi K2–K9 muncul; tiap entri **seimbang** (total debit = kredit).
- **Harapan:** semua entri ada `entry_no` unik & seimbang; retur (K7) memunculkan jurnal balik (4900 / 1300-5100).
- *Memvalidasi: integritas jurnal (H2), retur (A7).*

---

## C. Verifikasi silang (akuntansi end-to-end)

Setelah K2, K3, K7, K9 dan login owner:
- [ ] **Laba Rugi** = (penjualan netto K2 + K3) − retur K7 − HPP terkait. Tidak ada angka aneh/negatif tak wajar.
- [ ] **Rekonsiliasi Kas** selisih 0 untuk cabang kasir.
- [ ] **Stok Produk A** akhir = 5 − 2 (K2) − 2 (K3) + 2 (retur K7) + 10 (beli K9) = **13**.

---

## D. Catatan hasil

| Skenario | Lulus? | Catatan / temuan |
|----------|--------|------------------|
| K1 Login kasir | ☐ | |
| K2 Penjualan tunai | ☐ | |
| K3 Diskon nota | ☐ | |
| K4 Guard harga jual | ☐ | |
| K5 Anti-oversell | ☐ | |
| K6 Kas keluar | ☐ | |
| K7 Retur | ☐ | |
| K8 Proyek | ☐ | |
| K9 Pembelian/MA | ☐ | |
| O1 Login owner | ☐ | |
| O2 Aktivitas | ☐ | |
| O3 Laba Rugi | ☐ | |
| O4 Rekonsiliasi Kas | ☐ | |
| O5 Akuntansi/Jurnal | ☐ | |
| C Verifikasi silang | ☐ | |

> Jika ada skenario gagal, catat langkah, input, dan pesan error — itu jadi backlog perbaikan sebelum go-live.
