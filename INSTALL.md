# Panduan Instalasi — PVC POS

Aplikasi **Point of Sale + Akuntansi** berbasis Laravel 12 untuk role **Kasir** dan **Owner**.
Dokumen ini ditujukan untuk dipasang dari nol di laptop/server klien. Ikuti urut dari atas.

> **Untuk agent/AI yang memasang:** jalankan perintah persis seperti tertulis. Bagian
> **Database** itu wajib di-restore dari dump SQL — **jangan** mengandalkan `php artisan migrate`
> (lihat alasan di bagian Database).

---

## 1. Ringkasan Komponen

| Komponen | Wajib? | Keterangan |
|---|---|---|
| **Laravel app** (PHP) | Wajib | Aplikasi POS utama (kasir & owner). |
| **MariaDB / MySQL** | Wajib | Skema + data berasal dari `final_pvc.sql`. |
| **Vite / Node** | Wajib (build) | Membangun aset CSS/JS (Tailwind). |
| **MCP AI sidecar** (`MCP/`, FastAPI + Gemini) | Opsional | Fitur chat/asisten katalog. POS tetap jalan tanpa ini. |

---

## 2. Kebutuhan Sistem (Requirements)

### Wajib
- **PHP 8.2+** dengan ekstensi:
  `pdo_mysql`, `mbstring`, `bcmath`, `gd`, `zip`, `exif`, `pcntl`,
  `intl`, `ctype`, `curl`, `dom`, `fileinfo`, `openssl`, `tokenizer`, `xml`
  > `intl` **wajib** — dipakai untuk format angka/rupiah; tanpa ini sebagian haluman error.
- **Composer 2.x**
- **Node.js 20+** dan **npm** (build aset Vite)
- **MariaDB 10.6+** atau **MySQL 8.0+**

### Opsional (hanya untuk MCP AI sidecar)
- **Python 3.12+** + `venv`
- **Gemini API key** (https://aistudio.google.com/app/apikey)

### Cek versi cepat
```bash
php -v            # >= 8.2
php -m | grep -E 'pdo_mysql|mbstring|intl|gd|zip|bcmath'
composer --version
node -v && npm -v
mysql --version
```

#### Memasang ekstensi PHP (contoh Ubuntu/Debian)
```bash
sudo apt update
sudo apt install -y php8.2-cli php8.2-mysql php8.2-mbstring php8.2-bcmath \
  php8.2-gd php8.2-zip php8.2-intl php8.2-curl php8.2-xml
```
> Windows/XAMPP/Laragon: aktifkan ekstensi di `php.ini` (hilangkan `;` pada `extension=intl`, `gd`, `zip`, `pdo_mysql`, `mbstring`, `bcmath`, `exif`, `curl`).

---

## 3. Ambil Kode

```bash
git clone https://github.com/riodarmawan/pvc_project.git pvc
cd pvc
git checkout prod          # branch siap-produksi
```

---

## 4. Setup Aplikasi Laravel

```bash
# 4.1 Dependency PHP
composer install

# 4.2 File environment
cp .env.example .env

# 4.3 Generate APP_KEY
php artisan key:generate
```

### 4.4 Sunting `.env`
Atur minimal bagian database (sesuaikan kredensial DB Anda):
```ini
APP_NAME="PVC POS"
APP_ENV=production            # gunakan "local" untuk pengembangan
APP_DEBUG=false               # true hanya saat debugging
APP_URL=http://localhost:8000

DB_CONNECTION=mariadb         # atau "mysql"
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=final_pvc
DB_USERNAME=root
DB_PASSWORD=                  # isi sesuai password DB Anda

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```
> `.env` **tidak** ikut di-commit (berisi rahasia). Setiap mesin punya `.env` sendiri.

---

## 5. Database (PENTING)

Skema lengkap (50 tabel: produk, penjualan POS, jurnal akuntansi, proyek, dll.)
**hanya tersimpan di dump SQL**, bukan di file migration. Hanya 7 migration dasar yang
ada di repo. **Karena itu: restore dari `final_pvc.sql`, JANGAN `php artisan migrate`.**
Menjalankan `migrate` di atas hasil restore akan error (tabel sudah ada).

### 5.1 Buat database & restore
```bash
# Buat database kosong
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS final_pvc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Restore skema + data
mysql -u root -p final_pvc < final_pvc.sql
```

`final_pvc.sql` sudah memuat **skema + data awal** termasuk:
- Bagan akun (chart of accounts), cabang, UoM, kategori
- Produk contoh, stok, jurnal contoh
- **Akun login** kasir & owner (lihat bagian 8)

> Jika user `root` MariaDB memakai autentikasi `unix_socket` (default di sebagian distro),
> jalankan perintah `mysql` dengan `sudo mysql ...`, atau buat user khusus:
> ```sql
> CREATE USER 'pvc'@'127.0.0.1' IDENTIFIED BY 'rahasia';
> GRANT ALL PRIVILEGES ON final_pvc.* TO 'pvc'@'127.0.0.1';
> FLUSH PRIVILEGES;
> ```
> lalu sesuaikan `DB_USERNAME`/`DB_PASSWORD` di `.env`.

### 5.2 Verifikasi koneksi
```bash
php artisan db:show          # harus menampilkan database "final_pvc" dan daftar tabel
```

---

## 6. Frontend (Aset Vite)

```bash
npm install

# Produksi: build sekali
npm run build

# ATAU saat pengembangan (hot reload), biarkan jalan di terminal terpisah:
npm run dev
```

---

## 7. Jalankan Aplikasi

### Cara cepat (pengembangan)
```bash
php artisan serve
# buka http://127.0.0.1:8000
```

### Produksi
Arahkan web server (Nginx/Apache) ke folder `public/`. Pastikan permission:
```bash
chmod -R 775 storage bootstrap/cache
# (sesuaikan owner ke user web server, mis. www-data)
```
Optimasi cache:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 8. Akun Login Default

| Role  | Username | Password |
|-------|----------|----------|
| Owner | `owner`  | `owner`  |
| Kasir | `kasir`  | `kasir`  |

> Login memakai **username** (bukan email). **Ganti password ini segera** di lingkungan produksi.
> Role `kepala_cabang` ada di data tetapi tidak dipakai.

---

## 9. (Opsional) MCP AI Sidecar

Service FastAPI (`MCP/main.py`) untuk fitur chat/asisten katalog berbasis Gemini.
POS utama tetap berfungsi tanpa ini.

```bash
cd MCP
python3 -m venv envbaru
source envbaru/bin/activate          # Windows: envbaru\Scripts\activate
pip install -r requirements.txt

cp .env.example .env                 # lalu isi GEMINI_API_KEY, INTERNAL_TOKEN, DB_*
uvicorn main:app --host 0.0.0.0 --port 8001
```
> `MCP/.env` dan virtualenv (`envbaru/`, `Lib/`, dll.) **tidak** di-commit.

---

## 10. (Alternatif) Docker

Tersedia `Dockerfile` + `docker-compose.yml` (app PHP-FPM + Nginx + MySQL 8).
```bash
cp .env.example .env        # isi DB_DATABASE & DB_PASSWORD
docker compose up -d --build
# DB perlu di-restore manual ke container mysql:
docker exec -i pvc_mysql mysql -uroot -p"$DB_PASSWORD" "$DB_DATABASE" < final_pvc.sql
```
App tersedia di `http://localhost:8080`. (Catatan: file Docker bawaan masih dasar — sesuaikan untuk produksi.)

---

## 11. Smoke Test

Setelah berjalan, ikuti skenario uji di **`docs/SMOKE_TEST_BROWSER_PLAN.md`**
(K1–K9 untuk kasir, O1–O5 untuk owner). Untuk unit/feature test:
```bash
# Butuh database test "final_pvc_test" (buat sekali):
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS final_pvc_test;"
php artisan test
```

---

## 12. Troubleshooting

| Gejala | Solusi |
|---|---|
| `The "intl" PHP extension is required` | Pasang & aktifkan ekstensi `intl`, lalu restart PHP. |
| `SQLSTATE... Access denied for user 'root'@'localhost'` | Root pakai `unix_socket`. Pakai `sudo mysql`, atau buat user DB khusus (lihat 5.1). |
| Halaman blank / 500 | `php artisan config:clear && php artisan cache:clear`; cek `storage/logs/laravel.log`. |
| Aset CSS/JS tidak muncul | Jalankan `npm run build` (produksi) atau `npm run dev` (dev). |
| `Table ... already exists` saat `migrate` | **Jangan** jalankan `migrate` — skema sudah ada dari `final_pvc.sql` (lihat bagian 5). |
| Permission `storage`/`bootstrap/cache` | `chmod -R 775 storage bootstrap/cache` & set owner web server. |

---

## 13. ⚠️ Catatan Keamanan (WAJIB dibaca)

Repo ini punya **riwayat git lama** (branch `main`, `v1.2`) yang **pernah memuat rahasia**
(`MCP/.env`, API key Gemini di `MCP/test.py`). Pada branch `prod` rahasia sudah dibersihkan,
tetapi karena sudah terlanjur ada di histori publik:

1. **Putar (rotate) sekarang** Gemini API key, `INTERNAL_TOKEN`, dan password DB yang lama.
2. Jangan pernah commit `.env`, `MCP/.env`, atau kredensial apa pun.
3. Untuk benar-benar menghapus dari histori, perlu `git filter-repo`/BFG + force-push (operasi terpisah).
