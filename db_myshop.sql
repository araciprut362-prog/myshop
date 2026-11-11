-- ===============================================================
-- DATABASE MYSHOP
-- Sistem Informasi Penjualan Toko: Admin, Kasir, Pelanggan
-- Kompatibel: MySQL 8.0+ / MariaDB 10.5+
--
-- SUMBER DATA LAMA:
-- Mengadaptasi database db_toko1.sql yang diunggah:
-- - 12 pelanggan
-- - 4 cabang
-- - 20 transaksi historis
-- - log pelanggan dan log perubahan
-- - 5 Stored Procedure, 5 Stored Function, 3 Trigger, 2 View
--
-- PENTING:
-- 1. Script ini menghapus HANYA database db_myshop jika sudah ada,
--    lalu membuatnya kembali dari awal. Backup db_myshop lama dahulu.
-- 2. Database db_toko1 milikmu tidak dihapus/diubah.
-- 3. Semua akun dummy menggunakan password: password
-- 4. Password disimpan sebagai bcrypt hash, bukan teks biasa.
-- 5. Pembayaran Midtrans masih SANDBOX; Snap Token dibuat backend
--    aplikasi saat checkout, bukan di-seed sebagai token palsu.
-- ===============================================================

DROP DATABASE IF EXISTS db_myshop;
CREATE DATABASE db_myshop
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE db_myshop;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ===============================================================
-- 1. CABANG TOKO
-- Mempertahankan 4 cabang dari database db_toko1 lama.
-- ===============================================================
CREATE TABLE tbl_cabang (
  id_cabang INT AUTO_INCREMENT PRIMARY KEY,
  kode_cabang VARCHAR(15) NOT NULL UNIQUE,
  nama_cabang VARCHAR(100) NOT NULL,
  alamat VARCHAR(255) NULL,
  kota VARCHAR(80) NOT NULL,
  telepon VARCHAR(25) NULL,
  status ENUM('AKTIF','NONAKTIF') NOT NULL DEFAULT 'AKTIF',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===============================================================
-- 2. PELANGGAN
-- Struktur dan 12 data pelanggan lama dipertahankan.
-- ===============================================================
CREATE TABLE tbl_pelanggan (
  id_pelanggan INT AUTO_INCREMENT PRIMARY KEY,
  nama_pelanggan VARCHAR(100) NOT NULL,
  kota VARCHAR(50) NULL,
  no_hp VARCHAR(25) NULL,
  alamat VARCHAR(255) NULL,
  kode_pos VARCHAR(10) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_pelanggan_nama (nama_pelanggan),
  INDEX idx_pelanggan_kota (kota)
) ENGINE=InnoDB;

-- ===============================================================
-- 3. USER / AKUN LOGIN
-- Tiga role: admin, kasir, pelanggan.
-- id_pelanggan diisi hanya untuk akun dengan role pelanggan.
-- Kasir ditempatkan pada cabang tertentu; admin bisa mengelola semua.
-- ===============================================================
CREATE TABLE tbl_user (
  id_user INT AUTO_INCREMENT PRIMARY KEY,
  id_pelanggan INT NULL UNIQUE,
  id_cabang INT NULL,
  nama_lengkap VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','kasir','pelanggan') NOT NULL,
  no_hp VARCHAR(25) NULL,
  status ENUM('AKTIF','NONAKTIF') NOT NULL DEFAULT 'AKTIF',
  terakhir_login DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_user_pelanggan FOREIGN KEY (id_pelanggan)
    REFERENCES tbl_pelanggan(id_pelanggan)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_user_cabang FOREIGN KEY (id_cabang)
    REFERENCES tbl_cabang(id_cabang)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX idx_user_role_status (role,status)
) ENGINE=InnoDB;

-- ===============================================================
-- 4. KATEGORI PRODUK
-- ===============================================================
CREATE TABLE tbl_kategori (
  id_kategori INT AUTO_INCREMENT PRIMARY KEY,
  nama_kategori VARCHAR(100) NOT NULL UNIQUE,
  deskripsi TEXT NULL,
  status ENUM('AKTIF','NONAKTIF') NOT NULL DEFAULT 'AKTIF',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===============================================================
-- 5. PRODUK
-- Harga disimpan di database; backend harus mengambil harga dari sini
-- saat checkout, jangan mempercayai harga yang dikirim browser.
-- ===============================================================
CREATE TABLE tbl_produk (
  id_produk INT AUTO_INCREMENT PRIMARY KEY,
  id_kategori INT NOT NULL,
  sku VARCHAR(30) NOT NULL UNIQUE,
  nama_produk VARCHAR(150) NOT NULL,
  slug VARCHAR(180) NULL UNIQUE,
  deskripsi TEXT NULL,
  harga DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  gambar VARCHAR(255) NULL,
  status ENUM('AKTIF','NONAKTIF') NOT NULL DEFAULT 'AKTIF',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_produk_kategori FOREIGN KEY (id_kategori)
    REFERENCES tbl_kategori(id_kategori)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_produk_harga CHECK (harga >= 0),
  INDEX idx_produk_nama (nama_produk),
  INDEX idx_produk_status (status)
) ENGINE=InnoDB;

-- ===============================================================
-- 6. STOK PER CABANG
-- Model stok dipisah per cabang agar siap untuk multi-cabang.
-- ===============================================================
CREATE TABLE tbl_stok_cabang (
  id_stok BIGINT AUTO_INCREMENT PRIMARY KEY,
  id_cabang INT NOT NULL,
  id_produk INT NOT NULL,
  stok INT NOT NULL DEFAULT 0,
  stok_minimum INT NOT NULL DEFAULT 5,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_stok_cabang_produk UNIQUE (id_cabang,id_produk),
  CONSTRAINT fk_stok_cabang FOREIGN KEY (id_cabang)
    REFERENCES tbl_cabang(id_cabang)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_stok_produk FOREIGN KEY (id_produk)
    REFERENCES tbl_produk(id_produk)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_stok_nonnegatif CHECK (stok >= 0),
  CONSTRAINT chk_stok_minimum CHECK (stok_minimum >= 0)
) ENGINE=InnoDB;

-- ===============================================================
-- 7. TRANSAKSI / PESANAN
-- Satu tabel header untuk transaksi POS kasir dan pesanan online.
-- jenis_transaksi membedakan POS, ONLINE, dan data historis lama.
-- Data lama tidak memiliki informasi cabang/kasir, sehingga
-- id_cabang dan id_kasir dibiarkan NULL untuk 20 transaksi lama.
-- ===============================================================
CREATE TABLE tbl_transaksi (
  id_transaksi INT AUTO_INCREMENT PRIMARY KEY,
  kode_transaksi VARCHAR(40) NOT NULL UNIQUE,
  tanggal DATE NOT NULL,
  waktu_transaksi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_pelanggan INT NULL,
  id_kasir INT NULL,
  id_cabang INT NULL,
  jenis_transaksi ENUM('POS','ONLINE','LEGACY') NOT NULL DEFAULT 'POS',
  status_transaksi ENUM(
    'MENUNGGU_PEMBAYARAN','DIPROSES','DIKIRIM','SELESAI','DIBATALKAN'
  ) NOT NULL DEFAULT 'MENUNGGU_PEMBAYARAN',
  status_pembayaran ENUM(
    'PENDING','BERHASIL','GAGAL','KEDALUWARSA','DIBATALKAN',
    'DIKEMBALIKAN','TIDAK_DIKETAHUI'
  ) NOT NULL DEFAULT 'PENDING',
  status_stok ENUM(
    'BELUM_DIPROSES','DIRESERVASI','DIKURANGI','DIKEMBALIKAN'
  ) NOT NULL DEFAULT 'BELUM_DIPROSES',
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  diskon DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  pajak DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  ongkir DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total_belanja DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  alamat_pengiriman VARCHAR(255) NULL,
  catatan TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_transaksi_pelanggan FOREIGN KEY (id_pelanggan)
    REFERENCES tbl_pelanggan(id_pelanggan)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_transaksi_kasir FOREIGN KEY (id_kasir)
    REFERENCES tbl_user(id_user)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_transaksi_cabang FOREIGN KEY (id_cabang)
    REFERENCES tbl_cabang(id_cabang)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT chk_transaksi_nominal CHECK (
    subtotal >= 0 AND diskon >= 0 AND pajak >= 0 AND
    ongkir >= 0 AND total_belanja >= 0
  ),
  INDEX idx_transaksi_tanggal (tanggal),
  INDEX idx_transaksi_jenis (jenis_transaksi),
  INDEX idx_transaksi_status (status_transaksi,status_pembayaran),
  INDEX idx_transaksi_pelanggan (id_pelanggan),
  INDEX idx_transaksi_cabang (id_cabang)
) ENGINE=InnoDB;

-- ===============================================================
-- 8. DETAIL TRANSAKSI
-- Menyimpan snapshot nama dan harga ketika transaksi dibuat.
-- Data 20 transaksi historis lama tidak memiliki detail produk,
-- sehingga tabel ini hanya berisi detail transaksi baru/dummy.
-- ===============================================================
CREATE TABLE tbl_detail_transaksi (
  id_detail BIGINT AUTO_INCREMENT PRIMARY KEY,
  id_transaksi INT NOT NULL,
  id_produk INT NOT NULL,
  nama_produk_snapshot VARCHAR(150) NOT NULL,
  sku_snapshot VARCHAR(30) NULL,
  harga_satuan DECIMAL(12,2) NOT NULL,
  jumlah INT NOT NULL,
  diskon_item DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  subtotal DECIMAL(12,2) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_detail_transaksi_header FOREIGN KEY (id_transaksi)
    REFERENCES tbl_transaksi(id_transaksi)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_detail_transaksi_produk FOREIGN KEY (id_produk)
    REFERENCES tbl_produk(id_produk)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_detail_jumlah CHECK (jumlah > 0),
  CONSTRAINT chk_detail_nominal CHECK (
    harga_satuan >= 0 AND diskon_item >= 0 AND subtotal >= 0
  ),
  INDEX idx_detail_transaksi (id_transaksi)
) ENGINE=InnoDB;

-- ===============================================================
-- 9. KERANJANG DAN DETAIL KERANJANG
-- ===============================================================
CREATE TABLE tbl_keranjang (
  id_keranjang BIGINT AUTO_INCREMENT PRIMARY KEY,
  id_pelanggan INT NOT NULL,
  id_cabang INT NULL,
  status ENUM('AKTIF','CHECKOUT','KADALUWARSA') NOT NULL DEFAULT 'AKTIF',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_keranjang_pelanggan FOREIGN KEY (id_pelanggan)
    REFERENCES tbl_pelanggan(id_pelanggan)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_keranjang_cabang FOREIGN KEY (id_cabang)
    REFERENCES tbl_cabang(id_cabang)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX idx_keranjang_pelanggan_status (id_pelanggan,status)
) ENGINE=InnoDB;

CREATE TABLE tbl_detail_keranjang (
  id_detail_keranjang BIGINT AUTO_INCREMENT PRIMARY KEY,
  id_keranjang BIGINT NOT NULL,
  id_produk INT NOT NULL,
  jumlah INT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_detail_keranjang_produk UNIQUE (id_keranjang,id_produk),
  CONSTRAINT fk_detail_keranjang_header FOREIGN KEY (id_keranjang)
    REFERENCES tbl_keranjang(id_keranjang)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_detail_keranjang_produk FOREIGN KEY (id_produk)
    REFERENCES tbl_produk(id_produk)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_detail_keranjang_jumlah CHECK (jumlah > 0)
) ENGINE=InnoDB;

-- ===============================================================
-- 10. PEMBAYARAN
-- Menyimpan metode pembayaran dan metadata Midtrans.
-- Server Key tidak boleh disimpan di database atau dikirim ke browser.
-- Snap Token dibuat saat checkout melalui backend.
-- ===============================================================
CREATE TABLE tbl_pembayaran (
  id_pembayaran BIGINT AUTO_INCREMENT PRIMARY KEY,
  id_transaksi INT NOT NULL,
  metode ENUM('TUNAI','TRANSFER_BANK','QRIS','MIDTRANS') NOT NULL,
  status ENUM(
    'PENDING','BERHASIL','GAGAL','KEDALUWARSA',
    'DIBATALKAN','DIKEMBALIKAN'
  ) NOT NULL DEFAULT 'PENDING',
  jumlah_bayar DECIMAL(12,2) NOT NULL,
  provider_order_id VARCHAR(50) NULL UNIQUE,
  midtrans_transaction_id VARCHAR(100) NULL,
  midtrans_payment_type VARCHAR(60) NULL,
  midtrans_transaction_status VARCHAR(40) NULL,
  midtrans_fraud_status VARCHAR(40) NULL,
  snap_token VARCHAR(255) NULL,
  redirect_url VARCHAR(500) NULL,
  waktu_pembayaran DATETIME NULL,
  raw_notification LONGTEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pembayaran_transaksi FOREIGN KEY (id_transaksi)
    REFERENCES tbl_transaksi(id_transaksi)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_pembayaran_nominal CHECK (jumlah_bayar >= 0),
  INDEX idx_pembayaran_status (status),
  INDEX idx_midtrans_transaction_id (midtrans_transaction_id)
) ENGINE=InnoDB;

-- ===============================================================
-- 11. MUTASI STOK
-- Semua jumlah dicatat positif; jenis_mutasi menunjukkan arah
-- perubahan. Checkout harus memakai transaksi DB dan locking stok.
-- ===============================================================
CREATE TABLE tbl_mutasi_stok (
  id_mutasi BIGINT AUTO_INCREMENT PRIMARY KEY,
  id_cabang INT NOT NULL,
  id_produk INT NOT NULL,
  id_user INT NULL,
  id_transaksi INT NULL,
  jenis_mutasi ENUM(
    'MASUK','KELUAR','RESERVASI','BATAL_RESERVASI','PENYESUAIAN'
  ) NOT NULL,
  jumlah INT NOT NULL,
  keterangan VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mutasi_cabang FOREIGN KEY (id_cabang)
    REFERENCES tbl_cabang(id_cabang)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_mutasi_produk FOREIGN KEY (id_produk)
    REFERENCES tbl_produk(id_produk)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_mutasi_user FOREIGN KEY (id_user)
    REFERENCES tbl_user(id_user)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_mutasi_transaksi FOREIGN KEY (id_transaksi)
    REFERENCES tbl_transaksi(id_transaksi)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT chk_mutasi_jumlah CHECK (jumlah > 0),
  INDEX idx_mutasi_cabang_produk (id_cabang,id_produk),
  INDEX idx_mutasi_transaksi (id_transaksi)
) ENGINE=InnoDB;

-- ===============================================================
-- 12. LOG PELANGGAN LAMA DAN LOG AKTIVITAS BARU
-- ===============================================================
CREATE TABLE log_pelanggan (
  id_log INT AUTO_INCREMENT PRIMARY KEY,
  aktivitas VARCHAR(50) NULL,
  id_pelanggan INT NULL,
  waktu TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_log_pelanggan (id_pelanggan)
) ENGINE=InnoDB;

CREATE TABLE log_perubahan_pelanggan (
  id_log INT AUTO_INCREMENT PRIMARY KEY,
  id_pelanggan INT NULL,
  nama_lama VARCHAR(100) NULL,
  nama_baru VARCHAR(100) NULL,
  waktu TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_log_perubahan_pelanggan (id_pelanggan)
) ENGINE=InnoDB;

CREATE TABLE tbl_log_aktivitas (
  id_log BIGINT AUTO_INCREMENT PRIMARY KEY,
  id_user INT NULL,
  aktivitas VARCHAR(150) NOT NULL,
  tabel_terkait VARCHAR(60) NULL,
  id_referensi BIGINT NULL,
  keterangan TEXT NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_log_aktivitas_user FOREIGN KEY (id_user)
    REFERENCES tbl_user(id_user)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX idx_log_aktivitas_waktu (created_at),
  INDEX idx_log_aktivitas_user (id_user)
) ENGINE=InnoDB;

-- ===============================================================
-- 13. PENGATURAN TOKO / BRANDING
-- ===============================================================
CREATE TABLE tbl_pengaturan (
  id_pengaturan INT AUTO_INCREMENT PRIMARY KEY,
  nama_pengaturan VARCHAR(80) NOT NULL UNIQUE,
  nilai_pengaturan TEXT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ===============================================================
-- DATA DUMMY: CABANG (empat cabang lama dipertahankan)
-- ===============================================================
INSERT INTO tbl_cabang
(id_cabang,kode_cabang,nama_cabang,alamat,kota,telepon,status) VALUES
(1,'CBG001','Cabang Utama','Jl. Diponegoro No. 10','Ponorogo','0352-123456','AKTIF'),
(2,'CBG002','Cabang Madiun','Jl. Pahlawan No. 21','Madiun','0351-654321','AKTIF'),
(3,'CBG003','Cabang Jakarta','Jl. Sudirman No. 15','Jakarta','021-5551234','AKTIF'),
(4,'CBG004','Cabang Bandung','Jl. Asia Afrika No. 8','Bandung','022-7771234','AKTIF');

-- ===============================================================
-- DATA DUMMY: PELANGGAN LAMA (12 data dipertahankan)
-- ===============================================================
INSERT INTO tbl_pelanggan
(id_pelanggan,nama_pelanggan,kota,no_hp,alamat,kode_pos) VALUES
(1,'Andi','Ponorogo','081200000001','Jl. Mawar No. 1, Ponorogo','63411'),
(2,'Budi','Madiun','081200000002','Jl. Melati No. 2, Madiun','63121'),
(3,'Citra','Surabaya','081200000003','Jl. Kenanga No. 3, Surabaya','60111'),
(4,'Rina Wijaya','Malang','081200000004','Jl. Anggrek No. 4, Malang','65111'),
(5,'Eko','Kediri','081200000005','Jl. Wijaya Kusuma No. 5, Kediri','64111'),
(6,'Fajar','Ponorogo','081200000006','Jl. Diponegoro No. 6, Ponorogo','63412'),
(7,'Gita','Madiun','081200000007','Jl. Cempaka No. 7, Madiun','63122'),
(8,'Hendra','Surabaya','081200000008','Jl. Merpati No. 8, Surabaya','60112'),
(9,'Indah','Malang','081200000009','Jl. Melur No. 9, Malang','65112'),
(10,'Joko','Kediri','081200000010','Jl. Flamboyan No. 10, Kediri','64112'),
(11,'Dewi','Ponorogo','081200000011','Jl. Dr. Sutomo No. 11, Ponorogo','63413'),
(12,'Pelanggan Uji Trigger','Sumenep','081200000012','Jl. Trunojoyo No. 12, Sumenep','69411');

-- ===============================================================
-- DATA DUMMY: AKUN LOGIN
-- Password semua akun demo: password
-- Hash bcrypt telah dibuat dengan password_hash-compatible format.
-- Ganti password demo sebelum aplikasi digunakan secara publik.
-- ===============================================================
INSERT INTO tbl_user
(id_user,id_pelanggan,id_cabang,nama_lengkap,email,password_hash,role,no_hp,status) VALUES
(1,NULL,NULL,'Administrator MyShop','admin@myshop.test',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin','081299990001','AKTIF'),
(2,NULL,1,'Kasir Cabang Utama','kasir.utama@myshop.test',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','kasir','081299990002','AKTIF'),
(3,NULL,2,'Kasir Cabang Madiun','kasir.madiun@myshop.test',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','kasir','081299990003','AKTIF'),
(4,1,NULL,'Andi','andi@myshop.test',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','pelanggan','081200000001','AKTIF'),
(5,2,NULL,'Budi','budi@myshop.test',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','pelanggan','081200000002','AKTIF'),
(6,3,NULL,'Citra','citra@myshop.test',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','pelanggan','081200000003','AKTIF'),
(7,4,NULL,'Rina Wijaya','rina@myshop.test',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','pelanggan','081200000004','AKTIF'),
(8,5,NULL,'Eko','eko@myshop.test',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','pelanggan','081200000005','AKTIF'),
(9,6,NULL,'Fajar','fajar@myshop.test',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','pelanggan','081200000006','AKTIF'),
(10,7,NULL,'Gita','gita@myshop.test',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','pelanggan','081200000007','AKTIF'),
(11,8,NULL,'Hendra','hendra@myshop.test',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','pelanggan','081200000008','AKTIF'),
(12,9,NULL,'Indah','indah@myshop.test',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','pelanggan','081200000009','AKTIF'),
(13,10,NULL,'Joko','joko@myshop.test',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','pelanggan','081200000010','AKTIF'),
(14,11,NULL,'Dewi','dewi@myshop.test',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','pelanggan','081200000011','AKTIF'),
(15,12,NULL,'Pelanggan Uji Trigger','uji.trigger@myshop.test',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','pelanggan','081200000012','AKTIF');

-- ===============================================================
-- DATA DUMMY: KATEGORI
-- ===============================================================
INSERT INTO tbl_kategori (id_kategori,nama_kategori,deskripsi) VALUES
(1,'Minuman','Kopi, teh, susu, dan minuman lainnya'),
(2,'Sembako','Kebutuhan pokok sehari-hari'),
(3,'Makanan Ringan','Camilan dan makanan ringan'),
(4,'Makanan','Bahan makanan dan produk konsumsi'),
(5,'Perawatan Diri','Produk kebersihan dan perawatan tubuh'),
(6,'Rumah Tangga','Kebutuhan kebersihan rumah tangga');

-- ===============================================================
-- DATA DUMMY: 15 PRODUK
-- ===============================================================
INSERT INTO tbl_produk
(id_produk,id_kategori,sku,nama_produk,slug,deskripsi,harga,gambar,status) VALUES
(1,1,'MKS001','Kopi Arabika 200g','kopi-arabika-200g','Kopi arabika kemasan 200 gram',65000,'uploads/produk/kopi-arabika.jpg', 'AKTIF'),
(2,1,'MKS002','Teh Melati 25 Sachet','teh-melati-25-sachet','Teh melati isi 25 sachet',28000,'uploads/produk/teh-melati.jpg','AKTIF'),
(3,2,'SMB001','Gula Aren 500g','gula-aren-500g','Gula aren kemasan 500 gram',32000,'uploads/produk/gula-aren.jpg','AKTIF'),
(4,3,'MKR001','Biskuit Cokelat 250g','biskuit-cokelat-250g','Biskuit rasa cokelat 250 gram',22000,'uploads/produk/biskuit-cokelat.jpg','AKTIF'),
(5,3,'MKR002','Keripik Pisang 150g','keripik-pisang-150g','Keripik pisang renyah 150 gram',18000,'uploads/produk/keripik-pisang.jpg','AKTIF'),
(6,4,'MKN001','Madu Hutan 350ml','madu-hutan-350ml','Madu hutan kemasan 350 ml',85000,'uploads/produk/madu-hutan.jpg','AKTIF'),
(7,2,'SMB002','Minyak Goreng 1 Liter','minyak-goreng-1-liter','Minyak goreng kemasan 1 liter',19000,'uploads/produk/minyak-goreng.jpg','AKTIF'),
(8,2,'SMB003','Beras Premium 5kg','beras-premium-5kg','Beras premium kemasan 5 kilogram',78000,'uploads/produk/beras-premium.jpg','AKTIF'),
(9,5,'PRD001','Sabun Mandi Herbal','sabun-mandi-herbal','Sabun mandi herbal',12000,'uploads/produk/sabun-herbal.jpg','AKTIF'),
(10,5,'PRD002','Shampoo 170ml','shampoo-170ml','Shampoo kemasan 170 ml',26000,'uploads/produk/shampoo.jpg','AKTIF'),
(11,6,'RTG001','Detergen 800g','detergen-800g','Detergen bubuk 800 gram',27000,'uploads/produk/detergen.jpg','AKTIF'),
(12,1,'MKS003','Air Mineral 600ml','air-mineral-600ml','Air mineral botol 600 ml',5000,'uploads/produk/air-mineral.jpg','AKTIF'),
(13,1,'MKS004','Susu UHT 1 Liter','susu-uht-1-liter','Susu UHT kemasan 1 liter',22000,'uploads/produk/susu-uht.jpg','AKTIF'),
(14,3,'MKR003','Cokelat Batang 100g','cokelat-batang-100g','Cokelat batang 100 gram',15000,'uploads/produk/cokelat.jpg','AKTIF'),
(15,2,'SMB004','Mi Instan 5 Bungkus','mi-instan-5-bungkus','Mi instan isi 5 bungkus',17000,'uploads/produk/mi-instan.jpg','AKTIF');

-- ===============================================================
-- DATA DUMMY: STOK SETIAP PRODUK DI SETIAP CABANG
-- Stok Cabang Utama telah dikurangi untuk POS dan reservasi online
-- dummy pada transaksi 21 dan 22.
-- ===============================================================
INSERT INTO tbl_stok_cabang (id_cabang,id_produk,stok,stok_minimum) VALUES
(1,1,29,5),(1,2,29,5),(1,3,30,5),(1,4,30,5),(1,5,30,5),
(1,6,30,5),(1,7,28,5),(1,8,29,5),(1,9,30,5),(1,10,30,5),
(1,11,30,5),(1,12,28,5),(1,13,30,5),(1,14,30,5),(1,15,30,5),
(2,1,25,5),(2,2,25,5),(2,3,25,5),(2,4,25,5),(2,5,25,5),
(2,6,25,5),(2,7,25,5),(2,8,25,5),(2,9,25,5),(2,10,25,5),
(2,11,25,5),(2,12,25,5),(2,13,25,5),(2,14,25,5),(2,15,25,5),
(3,1,20,5),(3,2,20,5),(3,3,20,5),(3,4,20,5),(3,5,20,5),
(3,6,20,5),(3,7,20,5),(3,8,20,5),(3,9,20,5),(3,10,20,5),
(3,11,20,5),(3,12,20,5),(3,13,20,5),(3,14,20,5),(3,15,20,5),
(4,1,18,5),(4,2,18,5),(4,3,18,5),(4,4,18,5),(4,5,18,5),
(4,6,18,5),(4,7,18,5),(4,8,18,5),(4,9,18,5),(4,10,18,5),
(4,11,18,5),(4,12,18,5),(4,13,18,5),(4,14,18,5),(4,15,18,5);

-- ===============================================================
-- DATA DUMMY: 20 TRANSAKSI HISTORIS DARI db_toko1
-- Karena sumber lama tidak menyimpan cabang, kasir, atau detail
-- produk, kolom tersebut sengaja NULL/tidak dibuat-buat.
-- ===============================================================
INSERT INTO tbl_transaksi
(id_transaksi,kode_transaksi,tanggal,waktu_transaksi,id_pelanggan,id_kasir,id_cabang,
 jenis_transaksi,status_transaksi,status_pembayaran,status_stok,
 subtotal,diskon,pajak,ongkir,total_belanja,catatan) VALUES
(1,'LEGACY-2026-0001','2026-10-01','2026-10-01 00:00:00',1,NULL,NULL,'LEGACY','SELESAI','TIDAK_DIKETAHUI','BELUM_DIPROSES',250000,0,0,0,250000,'Migrasi transaksi lama; rincian produk tidak tersedia'),
(2,'LEGACY-2026-0002','2026-10-02','2026-10-02 00:00:00',2,NULL,NULL,'LEGACY','SELESAI','TIDAK_DIKETAHUI','BELUM_DIPROSES',350000,0,0,0,350000,'Migrasi transaksi lama; rincian produk tidak tersedia'),
(3,'LEGACY-2026-0003','2026-10-03','2026-10-03 00:00:00',1,NULL,NULL,'LEGACY','SELESAI','TIDAK_DIKETAHUI','BELUM_DIPROSES',150000,0,0,0,150000,'Migrasi transaksi lama; rincian produk tidak tersedia'),
(4,'LEGACY-2026-0004','2026-10-04','2026-10-04 00:00:00',3,NULL,NULL,'LEGACY','SELESAI','TIDAK_DIKETAHUI','BELUM_DIPROSES',500000,0,0,0,500000,'Migrasi transaksi lama; rincian produk tidak tersedia'),
(5,'LEGACY-2026-0005','2026-10-05','2026-10-05 00:00:00',4,NULL,NULL,'LEGACY','SELESAI','TIDAK_DIKETAHUI','BELUM_DIPROSES',200000,0,0,0,200000,'Migrasi transaksi lama; rincian produk tidak tersedia'),
(6,'LEGACY-2026-0006','2026-10-06','2026-10-06 00:00:00',5,NULL,NULL,'LEGACY','SELESAI','TIDAK_DIKETAHUI','BELUM_DIPROSES',450000,0,0,0,450000,'Migrasi transaksi lama; rincian produk tidak tersedia'),
(7,'LEGACY-2026-0007','2026-10-07','2026-10-07 00:00:00',6,NULL,NULL,'LEGACY','SELESAI','TIDAK_DIKETAHUI','BELUM_DIPROSES',300000,0,0,0,300000,'Migrasi transaksi lama; rincian produk tidak tersedia'),
(8,'LEGACY-2026-0008','2026-10-08','2026-10-08 00:00:00',7,NULL,NULL,'LEGACY','SELESAI','TIDAK_DIKETAHUI','BELUM_DIPROSES',150000,0,0,0,150000,'Migrasi transaksi lama; rincian produk tidak tersedia'),
(9,'LEGACY-2026-0009','2026-10-09','2026-10-09 00:00:00',8,NULL,NULL,'LEGACY','SELESAI','TIDAK_DIKETAHUI','BELUM_DIPROSES',600000,0,0,0,600000,'Migrasi transaksi lama; rincian produk tidak tersedia'),
(10,'LEGACY-2026-0010','2026-10-10','2026-10-10 00:00:00',9,NULL,NULL,'LEGACY','SELESAI','TIDAK_DIKETAHUI','BELUM_DIPROSES',250000,0,0,0,250000,'Migrasi transaksi lama; rincian produk tidak tersedia'),
(11,'LEGACY-2026-0011','2026-10-11','2026-10-11 00:00:00',10,NULL,NULL,'LEGACY','SELESAI','TIDAK_DIKETAHUI','BELUM_DIPROSES',700000,0,0,0,700000,'Migrasi transaksi lama; rincian produk tidak tersedia'),
(12,'LEGACY-2026-0012','2026-10-12','2026-10-12 00:00:00',2,NULL,NULL,'LEGACY','SELESAI','TIDAK_DIKETAHUI','BELUM_DIPROSES',100000,0,0,0,100000,'Migrasi transaksi lama; rincian produk tidak tersedia'),
(13,'LEGACY-2026-0013','2026-10-13','2026-10-13 00:00:00',3,NULL,NULL,'LEGACY','SELESAI','TIDAK_DIKETAHUI','BELUM_DIPROSES',350000,0,0,0,350000,'Migrasi transaksi lama; rincian produk tidak tersedia'),
(14,'LEGACY-2026-0014','2026-10-14','2026-10-14 00:00:00',4,NULL,NULL,'LEGACY','SELESAI','TIDAK_DIKETAHUI','BELUM_DIPROSES',400000,0,0,0,400000,'Migrasi transaksi lama; rincian produk tidak tersedia'),
(15,'LEGACY-2026-0015','2026-10-15','2026-10-15 00:00:00',5,NULL,NULL,'LEGACY','SELESAI','TIDAK_DIKETAHUI','BELUM_DIPROSES',200000,0,0,0,200000,'Migrasi transaksi lama; rincian produk tidak tersedia'),
(16,'LEGACY-2026-0016','2026-10-16','2026-10-16 00:00:00',6,NULL,NULL,'LEGACY','SELESAI','TIDAK_DIKETAHUI','BELUM_DIPROSES',550000,0,0,0,550000,'Migrasi transaksi lama; rincian produk tidak tersedia'),
(17,'LEGACY-2026-0017','2026-10-17','2026-10-17 00:00:00',7,NULL,NULL,'LEGACY','SELESAI','TIDAK_DIKETAHUI','BELUM_DIPROSES',300000,0,0,0,300000,'Migrasi transaksi lama; rincian produk tidak tersedia'),
(18,'LEGACY-2026-0018','2026-10-18','2026-10-18 00:00:00',8,NULL,NULL,'LEGACY','SELESAI','TIDAK_DIKETAHUI','BELUM_DIPROSES',250000,0,0,0,250000,'Migrasi transaksi lama; rincian produk tidak tersedia'),
(19,'LEGACY-2026-0019','2026-10-19','2026-10-19 00:00:00',9,NULL,NULL,'LEGACY','SELESAI','TIDAK_DIKETAHUI','BELUM_DIPROSES',450000,0,0,0,450000,'Migrasi transaksi lama; rincian produk tidak tersedia'),
(20,'LEGACY-2026-0020','2026-10-20','2026-10-20 00:00:00',10,NULL,NULL,'LEGACY','SELESAI','TIDAK_DIKETAHUI','BELUM_DIPROSES',500000,0,0,0,500000,'Migrasi transaksi lama; rincian produk tidak tersedia');

-- ===============================================================
-- DATA DUMMY: TRANSAKSI BARU UNTUK DEMO POS DAN ONLINE
-- Pajak 11% dipakai sebagai simulasi pembelajaran sesuai function lama.
-- ===============================================================
INSERT INTO tbl_transaksi
(id_transaksi,kode_transaksi,tanggal,waktu_transaksi,id_pelanggan,id_kasir,id_cabang,
 jenis_transaksi,status_transaksi,status_pembayaran,status_stok,
 subtotal,diskon,pajak,ongkir,total_belanja,alamat_pengiriman,catatan) VALUES
(21,'POS-20261110-0001','2026-11-10','2026-11-10 10:15:00',1,2,1,
 'POS','SELESAI','BERHASIL','DIKURANGI',
 126000,0,13860,0,139860,NULL,'Contoh transaksi langsung di kasir MyShop'),
(22,'WEB-20261110-0001','2026-11-10','2026-11-10 13:20:00',2,NULL,1,
 'ONLINE','MENUNGGU_PEMBAYARAN','PENDING','DIRESERVASI',
 93000,0,10230,15000,118230,'Jl. Melati No. 2, Madiun','Contoh pesanan online; menunggu pembayaran Midtrans Sandbox');

-- ===============================================================
-- DATA DUMMY: DETAIL TRANSAKSI POS DAN ONLINE
-- ===============================================================
INSERT INTO tbl_detail_transaksi
(id_transaksi,id_produk,nama_produk_snapshot,sku_snapshot,harga_satuan,jumlah,diskon_item,subtotal) VALUES
(21,8,'Beras Premium 5kg','SMB003',78000,1,0,78000),
(21,7,'Minyak Goreng 1 Liter','SMB002',19000,2,0,38000),
(21,12,'Air Mineral 600ml','MKS003',5000,2,0,10000),
(22,1,'Kopi Arabika 200g','MKS001',65000,1,0,65000),
(22,2,'Teh Melati 25 Sachet','MKS002',28000,1,0,28000);

-- ===============================================================
-- DATA DUMMY: KERANJANG PELANGGAN
-- ===============================================================
INSERT INTO tbl_keranjang (id_keranjang,id_pelanggan,id_cabang,status) VALUES
(1,1,1,'AKTIF'),
(2,3,1,'AKTIF');

INSERT INTO tbl_detail_keranjang (id_keranjang,id_produk,jumlah) VALUES
(1,6,1),
(1,14,2),
(2,3,1),
(2,5,2);

-- ===============================================================
-- DATA DUMMY: PEMBAYARAN
-- Transaksi 21: tunai berhasil.
-- Transaksi 22: Midtrans pending. Snap Token akan dibuat backend.
-- ===============================================================
INSERT INTO tbl_pembayaran
(id_pembayaran,id_transaksi,metode,status,jumlah_bayar,provider_order_id,
 midtrans_transaction_id,midtrans_payment_type,midtrans_transaction_status,
 midtrans_fraud_status,snap_token,redirect_url,waktu_pembayaran,raw_notification) VALUES
(1,21,'TUNAI','BERHASIL',139860,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-10 10:17:00',NULL),
(2,22,'MIDTRANS','PENDING',118230,'WEB-20261110-0001',NULL,NULL,'pending',NULL,NULL,NULL,NULL,NULL);

-- ===============================================================
-- DATA DUMMY: MUTASI STOK
-- Catat stok awal masuk 30/unit tiap cabang, lalu transaksi 21/22.
-- Stok akhir Cabang Utama sudah disesuaikan dengan transaksi dummy.
-- ===============================================================
INSERT INTO tbl_mutasi_stok (id_cabang,id_produk,id_user,id_transaksi,jenis_mutasi,jumlah,keterangan) VALUES
(1,1,1,NULL,'MASUK',30,'Stok awal dummy MyShop'),
(1,2,1,NULL,'MASUK',30,'Stok awal dummy MyShop'),
(1,3,1,NULL,'MASUK',30,'Stok awal dummy MyShop'),
(1,4,1,NULL,'MASUK',30,'Stok awal dummy MyShop'),
(1,5,1,NULL,'MASUK',30,'Stok awal dummy MyShop'),
(1,6,1,NULL,'MASUK',30,'Stok awal dummy MyShop'),
(1,7,1,NULL,'MASUK',30,'Stok awal dummy MyShop'),
(1,8,1,NULL,'MASUK',30,'Stok awal dummy MyShop'),
(1,9,1,NULL,'MASUK',30,'Stok awal dummy MyShop'),
(1,10,1,NULL,'MASUK',30,'Stok awal dummy MyShop'),
(1,11,1,NULL,'MASUK',30,'Stok awal dummy MyShop'),
(1,12,1,NULL,'MASUK',30,'Stok awal dummy MyShop'),
(1,13,1,NULL,'MASUK',30,'Stok awal dummy MyShop'),
(1,14,1,NULL,'MASUK',30,'Stok awal dummy MyShop'),
(1,15,1,NULL,'MASUK',30,'Stok awal dummy MyShop'),
(2,1,1,NULL,'MASUK',25,'Stok awal dummy MyShop'),
(2,2,1,NULL,'MASUK',25,'Stok awal dummy MyShop'),
(2,3,1,NULL,'MASUK',25,'Stok awal dummy MyShop'),
(2,4,1,NULL,'MASUK',25,'Stok awal dummy MyShop'),
(2,5,1,NULL,'MASUK',25,'Stok awal dummy MyShop'),
(2,6,1,NULL,'MASUK',25,'Stok awal dummy MyShop'),
(2,7,1,NULL,'MASUK',25,'Stok awal dummy MyShop'),
(2,8,1,NULL,'MASUK',25,'Stok awal dummy MyShop'),
(2,9,1,NULL,'MASUK',25,'Stok awal dummy MyShop'),
(2,10,1,NULL,'MASUK',25,'Stok awal dummy MyShop'),
(2,11,1,NULL,'MASUK',25,'Stok awal dummy MyShop'),
(2,12,1,NULL,'MASUK',25,'Stok awal dummy MyShop'),
(2,13,1,NULL,'MASUK',25,'Stok awal dummy MyShop'),
(2,14,1,NULL,'MASUK',25,'Stok awal dummy MyShop'),
(2,15,1,NULL,'MASUK',25,'Stok awal dummy MyShop'),
(3,1,1,NULL,'MASUK',20,'Stok awal dummy MyShop'),
(3,2,1,NULL,'MASUK',20,'Stok awal dummy MyShop'),
(3,3,1,NULL,'MASUK',20,'Stok awal dummy MyShop'),
(3,4,1,NULL,'MASUK',20,'Stok awal dummy MyShop'),
(3,5,1,NULL,'MASUK',20,'Stok awal dummy MyShop'),
(3,6,1,NULL,'MASUK',20,'Stok awal dummy MyShop'),
(3,7,1,NULL,'MASUK',20,'Stok awal dummy MyShop'),
(3,8,1,NULL,'MASUK',20,'Stok awal dummy MyShop'),
(3,9,1,NULL,'MASUK',20,'Stok awal dummy MyShop'),
(3,10,1,NULL,'MASUK',20,'Stok awal dummy MyShop'),
(3,11,1,NULL,'MASUK',20,'Stok awal dummy MyShop'),
(3,12,1,NULL,'MASUK',20,'Stok awal dummy MyShop'),
(3,13,1,NULL,'MASUK',20,'Stok awal dummy MyShop'),
(3,14,1,NULL,'MASUK',20,'Stok awal dummy MyShop'),
(3,15,1,NULL,'MASUK',20,'Stok awal dummy MyShop'),
(4,1,1,NULL,'MASUK',18,'Stok awal dummy MyShop'),
(4,2,1,NULL,'MASUK',18,'Stok awal dummy MyShop'),
(4,3,1,NULL,'MASUK',18,'Stok awal dummy MyShop'),
(4,4,1,NULL,'MASUK',18,'Stok awal dummy MyShop'),
(4,5,1,NULL,'MASUK',18,'Stok awal dummy MyShop'),
(4,6,1,NULL,'MASUK',18,'Stok awal dummy MyShop'),
(4,7,1,NULL,'MASUK',18,'Stok awal dummy MyShop'),
(4,8,1,NULL,'MASUK',18,'Stok awal dummy MyShop'),
(4,9,1,NULL,'MASUK',18,'Stok awal dummy MyShop'),
(4,10,1,NULL,'MASUK',18,'Stok awal dummy MyShop'),
(4,11,1,NULL,'MASUK',18,'Stok awal dummy MyShop'),
(4,12,1,NULL,'MASUK',18,'Stok awal dummy MyShop'),
(4,13,1,NULL,'MASUK',18,'Stok awal dummy MyShop'),
(4,14,1,NULL,'MASUK',18,'Stok awal dummy MyShop'),
(4,15,1,NULL,'MASUK',18,'Stok awal dummy MyShop'),
(1,8,2,21,'KELUAR',1,'Penjualan POS POS-20261110-0001'),
(1,7,2,21,'KELUAR',2,'Penjualan POS POS-20261110-0001'),
(1,12,2,21,'KELUAR',2,'Penjualan POS POS-20261110-0001'),
(1,1,NULL,22,'RESERVASI',1,'Reservasi stok pesanan online WEB-20261110-0001'),
(1,2,NULL,22,'RESERVASI',1,'Reservasi stok pesanan online WEB-20261110-0001');


-- ===============================================================
-- DATA DUMMY: LOG DARI DATABASE db_toko1 LAMA
-- ===============================================================
INSERT INTO log_pelanggan (id_log,aktivitas,id_pelanggan,waktu) VALUES
(1,'INSERT PELANGGAN',12,'2026-11-09 04:51:27');

INSERT INTO log_perubahan_pelanggan
(id_log,id_pelanggan,nama_lama,nama_baru,waktu) VALUES
(1,4,'Dewi','Rina Wijaya','2026-11-09 04:51:53');

INSERT INTO tbl_log_aktivitas
(id_user,aktivitas,tabel_terkait,id_referensi,keterangan) VALUES
(1,'Inisialisasi database MyShop','database',NULL,'Database dibuat dari dump db_toko1 dan diperluas untuk tiga role'),
(1,'Memuat data dummy produk','tbl_produk',15,'15 produk dummy dimasukkan'),
(2,'Membuat transaksi POS','tbl_transaksi',21,'Transaksi kasir contoh POS-20261110-0001'),
(NULL,'Membuat pesanan online','tbl_transaksi',22,'Pesanan contoh WEB-20261110-0001 menunggu Midtrans Sandbox');

INSERT INTO tbl_pengaturan (nama_pengaturan,nilai_pengaturan) VALUES
('nama_website','MyShop'),
('nama_toko','MyShop'),
('tagline','Belanja mudah, cepat, dan nyaman'),
('mata_uang','IDR'),
('zona_waktu','Asia/Jakarta'),
('mode_pembayaran','MIDTRANS_SANDBOX'),
('pajak_simulasi_persen','11');

-- ===============================================================
-- VIEW LAMA: dipertahankan dengan nama yang sama.
-- ===============================================================
CREATE VIEW view_laporan_transaksi AS
SELECT
  t.id_transaksi,
  t.kode_transaksi,
  t.tanggal,
  p.nama_pelanggan,
  p.kota,
  t.jenis_transaksi,
  t.status_transaksi,
  t.status_pembayaran,
  t.total_belanja
FROM tbl_transaksi t
LEFT JOIN tbl_pelanggan p ON p.id_pelanggan=t.id_pelanggan;

CREATE VIEW view_total_belanja AS
SELECT
  p.id_pelanggan,
  p.nama_pelanggan,
  COUNT(t.id_transaksi) AS total_transaksi,
  COALESCE(SUM(
    CASE
      WHEN t.status_pembayaran IN ('BERHASIL','TIDAK_DIKETAHUI')
       AND t.status_transaksi <> 'DIBATALKAN'
      THEN t.total_belanja ELSE 0
    END
  ),0) AS total_belanja
FROM tbl_pelanggan p
LEFT JOIN tbl_transaksi t ON t.id_pelanggan=p.id_pelanggan
GROUP BY p.id_pelanggan,p.nama_pelanggan;

-- VIEW BARU: produk, kategori, stok dan cabang.
CREATE VIEW view_produk_stok AS
SELECT
  pr.id_produk,pr.sku,pr.nama_produk,pr.harga,pr.gambar,pr.status AS status_produk,
  k.id_kategori,k.nama_kategori,
  c.id_cabang,c.nama_cabang,c.kota,
  COALESCE(s.stok,0) AS stok,
  COALESCE(s.stok_minimum,0) AS stok_minimum
FROM tbl_produk pr
JOIN tbl_kategori k ON k.id_kategori=pr.id_kategori
CROSS JOIN tbl_cabang c
LEFT JOIN tbl_stok_cabang s
  ON s.id_produk=pr.id_produk AND s.id_cabang=c.id_cabang;

-- VIEW BARU: ringkasan penjualan per cabang.
CREATE VIEW view_laporan_penjualan_cabang AS
SELECT
  c.id_cabang,c.nama_cabang,c.kota,
  COUNT(t.id_transaksi) AS jumlah_transaksi,
  COALESCE(SUM(
    CASE WHEN t.status_pembayaran='BERHASIL'
          AND t.status_transaksi <> 'DIBATALKAN'
         THEN t.total_belanja ELSE 0 END
  ),0) AS total_penjualan
FROM tbl_cabang c
LEFT JOIN tbl_transaksi t ON t.id_cabang=c.id_cabang
GROUP BY c.id_cabang,c.nama_cabang,c.kota;

-- VIEW BARU: ringkasan pelanggan.
CREATE VIEW view_ringkasan_pelanggan AS
SELECT
  p.id_pelanggan,p.nama_pelanggan,p.kota,p.no_hp,
  u.email,u.status AS status_akun,
  COUNT(t.id_transaksi) AS jumlah_transaksi,
  COALESCE(SUM(
    CASE WHEN t.status_pembayaran IN ('BERHASIL','TIDAK_DIKETAHUI')
          AND t.status_transaksi <> 'DIBATALKAN'
         THEN t.total_belanja ELSE 0 END
  ),0) AS total_belanja
FROM tbl_pelanggan p
LEFT JOIN tbl_user u
  ON u.id_pelanggan=p.id_pelanggan AND u.role='pelanggan'
LEFT JOIN tbl_transaksi t ON t.id_pelanggan=p.id_pelanggan
GROUP BY p.id_pelanggan,p.nama_pelanggan,p.kota,p.no_hp,u.email,u.status;

-- ===============================================================
-- STORED PROCEDURE LAMA: dipertahankan dari db_toko1.
-- ===============================================================
DELIMITER $$

CREATE PROCEDURE TampilPelanggan()
BEGIN
  SELECT * FROM tbl_pelanggan ORDER BY id_pelanggan;
END$$

CREATE PROCEDURE CariPelanggan(IN p_id INT)
BEGIN
  SELECT * FROM tbl_pelanggan WHERE id_pelanggan=p_id;
END$$

CREATE PROCEDURE TambahPelanggan(
  IN p_nama VARCHAR(100),
  IN p_kota VARCHAR(50)
)
BEGIN
  INSERT INTO tbl_pelanggan(nama_pelanggan,kota)
  VALUES(p_nama,p_kota);
END$$

CREATE PROCEDURE HitungPelanggan(OUT jumlah INT)
BEGIN
  SELECT COUNT(*) INTO jumlah FROM tbl_pelanggan;
END$$

CREATE PROCEDURE LaporanTransaksi(IN p_id_pelanggan INT)
BEGIN
  SELECT
    t.id_transaksi,t.tanggal,p.id_pelanggan,p.nama_pelanggan,p.kota,
    t.total_belanja,t.jenis_transaksi,t.status_transaksi,t.status_pembayaran
  FROM tbl_transaksi t
  JOIN tbl_pelanggan p ON p.id_pelanggan=t.id_pelanggan
  WHERE p.id_pelanggan=p_id_pelanggan
  ORDER BY t.tanggal DESC,t.id_transaksi DESC;
END$$

-- ===============================================================
-- STORED FUNCTION LAMA: dipertahankan dari db_toko1.
-- Pajak 11% adalah simulasi pembelajaran yang ada di modul.
-- ===============================================================
CREATE FUNCTION HitungDiskon(
  harga DECIMAL(12,2),
  diskon DECIMAL(5,2)
)
RETURNS DECIMAL(12,2)
DETERMINISTIC
NO SQL
BEGIN
  RETURN harga * diskon / 100;
END$$

CREATE FUNCTION HitungPajak(harga DECIMAL(12,2))
RETURNS DECIMAL(12,2)
DETERMINISTIC
NO SQL
BEGIN
  RETURN harga * 0.11;
END$$

CREATE FUNCTION HitungTotal(
  harga DECIMAL(12,2),
  jumlah INT
)
RETURNS DECIMAL(12,2)
DETERMINISTIC
NO SQL
BEGIN
  RETURN harga * jumlah;
END$$

CREATE FUNCTION KategoriBelanja(total DECIMAL(12,2))
RETURNS VARCHAR(30)
CHARACTER SET utf8mb4
DETERMINISTIC
NO SQL
BEGIN
  RETURN CASE
    WHEN total >= 1000000 THEN 'Pelanggan Gold'
    WHEN total >= 500000 THEN 'Pelanggan Silver'
    ELSE 'Pelanggan Reguler'
  END;
END$$

-- Return diperbesar menjadi VARCHAR(20) agar teks 'Tidak Valid'
-- tidak terpotong; rentang nilai mengikuti Jobsheet 4 yang dibuat.
CREATE FUNCTION KonversiNilai(nilai DECIMAL(5,2))
RETURNS VARCHAR(20)
CHARACTER SET utf8mb4
DETERMINISTIC
NO SQL
BEGIN
  RETURN CASE
    WHEN nilai >= 85 AND nilai <= 100 THEN 'A'
    WHEN nilai >= 75 AND nilai < 85 THEN 'B'
    WHEN nilai >= 65 AND nilai < 75 THEN 'C'
    WHEN nilai >= 50 AND nilai < 65 THEN 'D'
    WHEN nilai >= 0 AND nilai < 50 THEN 'E'
    ELSE 'Tidak Valid'
  END;
END$$

-- ===============================================================
-- TRIGGER LAMA: tiga trigger dari Jobsheet 5 dipertahankan.
-- ===============================================================
CREATE TRIGGER after_insert_pelanggan
AFTER INSERT ON tbl_pelanggan
FOR EACH ROW
BEGIN
  INSERT INTO log_pelanggan(aktivitas,id_pelanggan)
  VALUES('INSERT PELANGGAN',NEW.id_pelanggan);
END$$

CREATE TRIGGER after_update_pelanggan
AFTER UPDATE ON tbl_pelanggan
FOR EACH ROW
BEGIN
  INSERT INTO log_perubahan_pelanggan
    (id_pelanggan,nama_lama,nama_baru)
  VALUES
    (OLD.id_pelanggan,OLD.nama_pelanggan,NEW.nama_pelanggan);
END$$

CREATE TRIGGER before_insert_transaksi
BEFORE INSERT ON tbl_transaksi
FOR EACH ROW
BEGIN
  IF NEW.total_belanja < 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT='Total belanja tidak boleh negatif';
  END IF;
END$$

-- Trigger tambahan untuk log produk baru.
CREATE TRIGGER after_insert_produk
AFTER INSERT ON tbl_produk
FOR EACH ROW
BEGIN
  INSERT INTO tbl_log_aktivitas
    (id_user,aktivitas,tabel_terkait,id_referensi,keterangan)
  VALUES
    (NULL,'Produk baru ditambahkan','tbl_produk',NEW.id_produk,
     CONCAT('SKU: ',NEW.sku,' | Produk: ',NEW.nama_produk));
END$$

DELIMITER ;

-- ===============================================================
-- PENGUJIAN SETELAH IMPORT
-- Jalankan SELECT/CALL berikut satu per satu di phpMyAdmin.
-- ===============================================================
-- SELECT * FROM tbl_user ORDER BY role,id_user;
-- SELECT * FROM tbl_cabang;
-- SELECT * FROM tbl_kategori;
-- SELECT * FROM tbl_produk;
-- SELECT * FROM view_produk_stok;
-- SELECT * FROM tbl_transaksi ORDER BY id_transaksi;
-- SELECT * FROM view_laporan_transaksi;
-- SELECT * FROM view_total_belanja;
-- SELECT * FROM view_laporan_penjualan_cabang;
-- SELECT * FROM view_ringkasan_pelanggan;
-- SELECT * FROM tbl_pembayaran;
-- SHOW PROCEDURE STATUS WHERE Db='db_myshop';
-- SHOW FUNCTION STATUS WHERE Db='db_myshop';
-- SHOW TRIGGERS FROM db_myshop;
-- CALL TampilPelanggan();
-- CALL CariPelanggan(1);
-- CALL HitungPelanggan(@jumlah); SELECT @jumlah;
-- SELECT HitungDiskon(100000,10) AS diskon;
-- SELECT HitungTotal(50000,5) AS total;
-- SELECT HitungPajak(1000000) AS pajak;
-- SELECT KategoriBelanja(1500000) AS kategori;
-- SELECT KonversiNilai(90) AS nilai_huruf;

-- ===============================================================
-- INFORMASI PENTING UNTUK DEVELOPER APLIKASI
-- 1. Website: MyShop. Database: db_myshop.
-- 2. Role pengguna: admin, kasir, pelanggan.
-- 3. Transaksi POS dan online sama-sama tersimpan di tbl_transaksi;
--    pembeda ada di kolom jenis_transaksi.
-- 4. Stok per cabang ada di tbl_stok_cabang.
-- 5. Checkout harus memakai DB transaction + SELECT ... FOR UPDATE
--    pada baris stok, lalu update stok dan catat tbl_mutasi_stok.
-- 6. Untuk pesanan online, reservasi stok saat checkout; kembalikan
--    stok satu kali bila pembayaran kedaluwarsa/dibatalkan.
-- 7. Untuk POS, stok dikurangi ketika transaksi kasir diselesaikan.
-- 8. Midtrans Snap Token dibuat di backend. Simpan token/URL pada
--    tbl_pembayaran. Verifikasi signature webhook di backend.
-- 9. Jangan pernah menyimpan Server Key Midtrans di database/browser.
-- 10. Riwayat legacy tidak memiliki rincian produk/metode bayar/cabang;
--     data tersebut sengaja tidak direka dan ditandai LEGACY.
-- ===============================================================
