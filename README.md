# MyShop — Sistem Informasi Penjualan Toko

> Laravel 13 · Vue 3 · Inertia.js · Tailwind CSS 4 · MySQL · Midtrans Sandbox

**MyShop** adalah sistem informasi penjualan toko lengkap yang menggabungkan **E-Commerce** (toko online) dan **Point of Sale (POS)** dalam satu aplikasi. Dibangun di atas tumpukan modern Laravel + Vue + Inertia dengan dukungan pembayaran Midtrans Snap.

---

## Daftar Isi

1. [Teknologi](#teknologi)
2. [Persyaratan Sistem](#persyaratan-sistem)
3. [Instalasi & Setup](#instalasi--setup)
4. [Database](#database)
5. [Akun Demo](#akun-demo)
6. [Panduan Penggunaan](#panduan-penggunaan)
7. [Pembayaran Midtrans Sandbox](#pembayaran-midtrans-sandbox)
8. [Webhook Midtrans (Lokal)](#webhook-midtrans-lokal)
9. [Cetak Nota Thermal](#cetak-nota-thermal)
10. [Menjalankan Test](#menjalankan-test)
11. [Struktur Proyek](#struktur-proyek)
12. [Troubleshooting](#troubleshooting)

---

## Teknologi

| Layer | Stack |
|---|---|
| Backend | Laravel 13.x, PHP 8.3 |
| Database | MySQL 8.4 / MariaDB |
| ORM | Eloquent |
| Frontend | Vue 3 (Composition API + TypeScript) |
| Routing SPA | Inertia.js |
| Styling | Tailwind CSS 4 |
| UI Components | shadcn-vue, Radix-Vue, Lucide Icons |
| Build Tool | Vite |
| Pembayaran | Midtrans Snap SDK (Sandbox) |

---

## Persyaratan Sistem

| Software | Versi Minimum |
|---|---|
| PHP | 8.3 |
| MySQL / MariaDB | 8.0 / 10.6 |
| Composer | 2.x |
| Node.js | 20.x LTS |
| NPM | 10.x |

> **Laragon (Windows):** Gunakan `C:\laragon\bin\php\php-8.3.x-Win32-vs16-x64` untuk CLI PHP. Tambahkan ke `PATH` atau prefix setiap perintah dengan `$env:PATH = "C:\laragon\bin\php\php-8.3.x-...;$env:PATH"` di PowerShell.

---

## Instalasi & Setup

### 1. Clone & masuk ke folder proyek

```bash
git clone <repo-url> myshop
cd myshop
```

### 2. Install dependensi PHP

```bash
composer install
```

### 3. Install dependensi JavaScript

```bash
npm install
```

### 4. Konfigurasi environment

```bash
cp .env.example .env
php artisan key:generate
```

Buka `.env` dan sesuaikan nilai berikut:

```dotenv
APP_NAME=MyShop
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_myshop
DB_USERNAME=root
DB_PASSWORD=

MIDTRANS_IS_PRODUCTION=false
MIDTRANS_SERVER_KEY=SB-Mid-server-XXXX   # Ganti dengan Server Key sandbox Anda
MIDTRANS_CLIENT_KEY=SB-Mid-client-XXXX  # Ganti dengan Client Key sandbox Anda
MIDTRANS_MERCHANT_ID=GXXXXXXXX
```

### 5. Import database

```bash
# Buat database jika belum ada
mysql -u root -e "CREATE DATABASE IF NOT EXISTS db_myshop;"

# Import SQL (Windows PowerShell)
Get-Content db_myshop.sql | mysql -u root db_myshop

# Atau menggunakan mysql langsung
mysql -u root db_myshop < db_myshop.sql
```

### 6. Jalankan migrasi Laravel (aman, tidak menimpa tabel yang ada)

```bash
php artisan migrate
```

> Migrasi hanya membuat tabel `password_reset_tokens` dan `sessions` yang dibutuhkan Laravel. Semua 16 tabel aplikasi sudah ada di SQL.

### 7. Buat symlink storage

```bash
php artisan storage:link
```

### 8. Build aset frontend

```bash
# Untuk production
npm run build

# Atau untuk development (hot reload)
npm run dev
```

### 9. Jalankan server

```bash
php artisan serve
```

Buka di browser: **http://localhost:8000**

---

## Database

Database `db_myshop` berisi **16 tabel** aplikasi:

| Tabel | Fungsi |
|---|---|
| `tbl_user` | Akun Admin, Kasir, dan Pelanggan (PK: `id_user`) |
| `tbl_pelanggan` | Profil pelanggan |
| `tbl_cabang` | Data cabang toko (4 cabang) |
| `tbl_kategori` | Kategori produk |
| `tbl_produk` | Data produk (15 produk) |
| `tbl_stok_cabang` | Stok produk per cabang |
| `tbl_transaksi` | Header transaksi POS dan online |
| `tbl_detail_transaksi` | Detail item dalam transaksi |
| `tbl_keranjang` | Keranjang belanja pelanggan |
| `tbl_detail_keranjang` | Item dalam keranjang |
| `tbl_pembayaran` | Data pembayaran Midtrans |
| `tbl_mutasi_stok` | Riwayat mutasi stok |
| `log_pelanggan` | Log penambahan pelanggan (via Trigger) |
| `log_perubahan_pelanggan` | Log perubahan data pelanggan (via Trigger) |
| `tbl_log_aktivitas` | Log aktivitas sistem |
| `tbl_pengaturan` | Pengaturan website |

### Views yang tersedia

| View | Digunakan di |
|---|---|
| `view_laporan_transaksi` | Halaman Laporan Admin |
| `view_total_belanja` | Detail pelanggan Admin |
| `view_produk_stok` | Manajemen Stok Admin |
| `view_laporan_penjualan_cabang` | Dashboard & Laporan Cabang |
| `view_ringkasan_pelanggan` | Daftar pelanggan Admin |

> **JANGAN** jalankan `php artisan migrate:fresh`, `db:wipe`, atau `DROP DATABASE`. Database sudah berisi data produksi dan dummy yang berharga.

---

## Akun Demo

Semua akun menggunakan password: **`password`**

| Role | Email | Cabang |
|---|---|---|
| **Admin** | `admin@myshop.test` | Semua cabang |
| **Kasir Utama** | `kasir.utama@myshop.test` | Cabang Utama (ID: 1) |
| **Kasir Madiun** | `kasir.madiun@myshop.test` | Cabang Madiun (ID: 2) |
| **Pelanggan** | `andi@myshop.test` | — |

---

## Panduan Penggunaan

### Fitur Admin

Login sebagai Admin → diarahkan ke `/admin/dashboard`

| Halaman | URL | Fitur |
|---|---|---|
| Dashboard | `/admin/dashboard` | Statistik real-time, grafik penjualan, produk terlaris |
| Produk | `/admin/produk` | CRUD produk, upload gambar, aktif/nonaktif |
| Kategori | `/admin/kategori` | CRUD kategori |
| Pelanggan | `/admin/pelanggan` | Daftar, profil, riwayat belanja, kategori belanja |
| Kasir | `/admin/kasir` | Buat/edit/nonaktifkan akun kasir, penempatan cabang |
| Cabang | `/admin/cabang` | CRUD cabang, stok & pendapatan per cabang |
| Stok | `/admin/stok` | Lihat stok semua cabang, sesuaikan stok |
| Mutasi Stok | `/admin/stok/mutasi` | Riwayat semua perubahan stok |
| Transaksi | `/admin/transaksi` | Semua transaksi POS dan online, cetak nota |
| Pesanan Online | `/admin/pesanan` | Kelola pesanan online, update status pengiriman |
| Laporan | `/admin/laporan` | Laporan penjualan, export CSV |
| Pengaturan | `/admin/pengaturan` | Nama toko, pajak, cabang default online |

### Fitur Kasir / POS

Login sebagai Kasir → diarahkan ke `/kasir/dashboard`

**Alur transaksi POS:**
1. Buka `/kasir/pos`
2. Cari produk menggunakan search bar atau filter kategori
3. Klik produk untuk menambahkan ke keranjang POS
4. Atur jumlah, tambahkan catatan (opsional)
5. Pilih pelanggan (opsional, untuk member)
6. Pilih metode pembayaran: **Tunai / Transfer Bank / QRIS**
7. Untuk tunai: masukkan jumlah uang diterima → sistem otomatis menghitung kembalian
8. Klik **"Proses Transaksi"**
9. Nota thermal 80mm otomatis tampil dan bisa dicetak

**Pembatasan kasir:**
- Kasir hanya dapat melihat dan menjual produk dengan stok di **cabangnya sendiri**
- Stok otomatis berkurang saat transaksi berhasil
- Riwayat transaksi tersedia di `/kasir/riwayat`

### Fitur Pelanggan / E-Commerce

Login atau daftar sebagai Pelanggan → diarahkan ke halaman utama toko

**Alur belanja online:**
1. Jelajahi produk di halaman utama atau `/katalog`
2. Filter berdasarkan kategori, cari produk
3. Buka detail produk di `/produk/{slug}`
4. Klik **"Tambah ke Keranjang"**
5. Buka keranjang di `/pelanggan/keranjang`
6. Atur jumlah, hapus item jika perlu
7. Klik **"Lanjut ke Checkout"**
8. Isi data penerima dan alamat pengiriman
9. Klik **"Buat Pesanan"** → stok otomatis **direservasi**
10. Bayar menggunakan Midtrans Snap di `/pelanggan/pesanan/{id}/bayar`
11. Lihat status pesanan di `/pelanggan/pesanan`
12. Unduh nota pembelian di `/pelanggan/pesanan/{id}/nota`

**Registrasi pelanggan:**
- Buka `/register`
- Isi data: nama, email, password, nomor HP, kota, alamat
- Akun langsung aktif; role **selalu `pelanggan`** (tidak bisa memilih admin)

---

## Pembayaran Midtrans Sandbox

### Konfigurasi

1. Daftar di [https://dashboard.sandbox.midtrans.com](https://dashboard.sandbox.midtrans.com)
2. Dapatkan **Server Key** dan **Client Key** dari Settings → Access Keys
3. Isi di `.env`:

```dotenv
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_SERVER_KEY=SB-Mid-server-XXXXXXXXXXXXXXXX
MIDTRANS_CLIENT_KEY=SB-Mid-client-XXXXXXXXXXXXXXXX
```

### Metode Pembayaran Test

Gunakan data berikut saat popup Midtrans Snap muncul:

| Metode | Detail Test |
|---|---|
| **Kartu Kredit** | `4811 1111 1111 1114` / Exp: `01/39` / CVV: `123` / OTP: `112233` |
| **BCA Virtual Account** | Buat VA, bayar via simulator Midtrans |
| **GoPay / QRIS** | Scan QR atau gunakan simulator Midtrans |
| **Alfamart** | Gunakan kode pembayaran dari response |

> Semua transaksi di sandbox tidak melibatkan uang nyata.

### Simulator Pembayaran Lokal (tanpa Midtrans)

Jika tidak memiliki akun Midtrans, gunakan endpoint simulator internal:

```bash
POST /payment/midtrans/simulate
Content-Type: application/json

{
  "kode_transaksi": "WEB-20261111-0001",
  "action": "settlement"
}
```

Action yang tersedia: `settlement`, `expire`, `cancel`

---

## Webhook Midtrans (Lokal)

Midtrans webhook memerlukan URL publik. Untuk development lokal gunakan **ngrok** atau **Cloudflare Tunnel**:

### Menggunakan ngrok

```bash
# Install ngrok: https://ngrok.com/download
ngrok http 8000
```

Salin URL tunnel (mis. `https://xxxx.ngrok-free.app`) dan daftarkan di:
- Midtrans Dashboard → Settings → Configuration
- **Payment Notification URL:** `https://xxxx.ngrok-free.app/api/payment/midtrans/notification`

### Menggunakan Cloudflare Tunnel

```bash
cloudflared tunnel --url http://localhost:8000
```

### Endpoint Webhook

```
POST /api/payment/midtrans/notification
```

Webhook ini:
- Memverifikasi signature SHA-512 Midtrans
- Idempotent (aman jika dipanggil berulang kali)
- Mengonfirmasi/membatalkan stok secara otomatis
- Memperbarui status transaksi dan pembayaran

---

## Cetak Nota Thermal

### Nota Kasir POS (80mm Thermal)

Tersedia di `/kasir/pos/nota/{id_transaksi}` setelah transaksi berhasil.

**Cara mencetak:**
1. Hubungkan printer thermal 80mm (mis. Epson TM-T82, Cashino, dll.) ke komputer
2. Set printer sebagai printer default Windows
3. Buka nota setelah transaksi
4. Tekan `Ctrl + P` atau klik tombol **"Cetak Nota"**
5. Pilih printer thermal, paper size `80mm`, margins `none`

### Nota Pelanggan Online

Tersedia di `/pelanggan/pesanan/{id}/nota` setelah pesanan selesai.

---

## Menjalankan Test

```bash
# Jalankan seluruh test suite (23 test cases)
php artisan test --compact

# Jalankan test spesifik
php artisan test --filter=AuthTest
php artisan test --filter=PosTest
php artisan test --filter=CheckoutTest
php artisan test --filter=MidtransWebhookTest
```

### Cakupan Test (23 Test Cases)

| File | Jumlah Test | Cakupan |
|---|---|---|
| `AuthTest.php` | 10 | Login 3 role, registrasi, blokir nonaktif, privilege escalation, role guard |
| `PosTest.php` | 4 | Render POS, transaksi sukses + deduct stok, stok kurang, uang kurang |
| `CheckoutTest.php` | 2 | Render checkout, buat transaksi + reservasi stok + record mutasi |
| `MidtransWebhookTest.php` | 5 | Settlement, expire, cancel, idempotency, order tidak ditemukan |

> **Catatan:** Test suite menggunakan `DatabaseTransactions` — semua data test di-rollback setelah setiap test, aman dijalankan terhadap database yang sudah ada.

---

## Struktur Proyek

```
myshop/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/          # Dashboard, Produk, Kategori, Pelanggan, Kasir,
│   │   │   │                   # Cabang, Stok, Transaksi, Pesanan, Laporan, Pengaturan
│   │   │   ├── Auth/           # Login, Logout, Register
│   │   │   ├── Kasir/          # Dashboard Kasir, POS
│   │   │   ├── Payment/        # Webhook Midtrans, Simulator
│   │   │   ├── Pelanggan/      # Keranjang, Checkout, Pesanan
│   │   │   └── Store/          # Home, Katalog, Detail Produk
│   │   └── Middleware/
│   │       ├── EnsureUserRole.php        # Role guard: admin | kasir | pelanggan
│   │       └── HandleInertiaRequests.php # Share auth, settings, cart count
│   ├── Models/                 # 16 Eloquent models sesuai tbl_* database
│   └── Services/
│       ├── StockService.php    # Reservasi, konfirmasi, kembalikan stok (thread-safe)
│       ├── MidtransService.php # Snap token, webhook handler, SHA-512 signature
│       ├── PosService.php      # Proses transaksi POS dengan stock locking
│       └── CheckoutService.php # Proses checkout online dengan stock reservation
├── resources/js/
│   ├── layouts/                # AdminLayout, KasirLayout, PelangganLayout
│   ├── components/             # Pagination, Modal, Badge, shadcn-vue components
│   └── pages/
│       ├── Admin/              # 16 halaman admin
│       ├── Auth/               # Login, Register
│       ├── Kasir/              # Dashboard, POS, Nota Thermal, Riwayat
│       ├── Pelanggan/          # Keranjang, Checkout, Pesanan, Nota
│       └── Store/              # Home, Katalog, Detail Produk
├── routes/
│   ├── web.php                 # 59 web routes
│   └── api.php                 # 1 API route (webhook Midtrans)
├── tests/Feature/
│   ├── AuthTest.php
│   ├── PosTest.php
│   ├── CheckoutTest.php
│   └── MidtransWebhookTest.php
├── phpunit.xml                 # Konfigurasi test dengan MySQL db_myshop
└── db_myshop.sql               # Database dump lengkap dengan data dummy
```

---

## Troubleshooting

### "Unable to locate file in Vite manifest"

```bash
npm run build
```

### "No such table: tbl_pengaturan"

Pastikan database `db_myshop` sudah diimport:
```bash
mysql -u root db_myshop < db_myshop.sql
```

### "SQLSTATE[HY000]: Access denied"

Periksa kredensial di `.env`:
```dotenv
DB_USERNAME=root
DB_PASSWORD=       # Kosong jika Laragon default
```

### PHP versi salah (Windows Laragon)

Tambahkan PHP 8.3 Laragon ke PATH di PowerShell:
```powershell
$env:PATH = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64;$env:PATH"
php -v
```

### Webhook Midtrans tidak diterima

1. Pastikan server dapat diakses publik (gunakan ngrok/cloudflare tunnel)
2. Pastikan URL di dashboard Midtrans benar: `https://xxxx.ngrok.app/api/payment/midtrans/notification`
3. Gunakan simulator lokal untuk testing tanpa internet publik: `POST /payment/midtrans/simulate`

### Stok tidak terpotong setelah transaksi POS

- Pastikan kasir memiliki `id_cabang` yang terkonfigurasi di `tbl_user`
- Periksa log di `storage/logs/laravel.log`
- Verifikasi stok ada di tabel `tbl_stok_cabang` untuk cabang kasir tersebut

---

## Lisensi

MIT License — Bebas digunakan untuk keperluan akademis dan komersial.
