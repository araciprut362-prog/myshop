<?php

use App\Http\Controllers\Admin\CabangController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\KasirController;
use App\Http\Controllers\Admin\KategoriController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\PelangganController as AdminPelangganController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\Admin\PesananOnlineController;
use App\Http\Controllers\Admin\ProdukController;
use App\Http\Controllers\Admin\StokController;
use App\Http\Controllers\Admin\TransaksiController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Kasir\KasirDashboardController;
use App\Http\Controllers\Kasir\PosController;
use App\Http\Controllers\Payment\SimulasiPaymentController;
use App\Http\Controllers\Pelanggan\CheckoutController;
use App\Http\Controllers\Pelanggan\KeranjangController;
use App\Http\Controllers\Pelanggan\PesananPelangganController;
use App\Http\Controllers\Store\StoreController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - MyShop E-Commerce & Point of Sale (POS)
|--------------------------------------------------------------------------
*/

// ==========================================
// 1. PUBLIC STOREFRONT
// ==========================================
Route::get('/', [StoreController::class, 'home'])->name('home');
Route::get('/katalog', [StoreController::class, 'katalog'])->name('store.katalog');
Route::get('/produk/{slug}', [StoreController::class, 'detail'])->name('store.detail');

// ==========================================
// 2. AUTHENTICATION (Guest Only)
// ==========================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// ==========================================
// 3. FITUR PELANGGAN (Role: pelanggan)
// ==========================================
Route::middleware(['auth', 'role:pelanggan'])->prefix('pelanggan')->name('pelanggan.')->group(function () {
    // Keranjang Belanja
    Route::get('/keranjang', [KeranjangController::class, 'index'])->name('keranjang.index');
    Route::post('/keranjang', [KeranjangController::class, 'store'])->name('keranjang.store');
    Route::patch('/keranjang/{id}', [KeranjangController::class, 'update'])->name('keranjang.update');
    Route::delete('/keranjang/{id}', [KeranjangController::class, 'destroy'])->name('keranjang.destroy');

    // Checkout
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    // Pesanan & Pembayaran Midtrans
    Route::get('/pesanan', [PesananPelangganController::class, 'index'])->name('pesanan.index');
    Route::get('/pesanan/{transaksi}', [PesananPelangganController::class, 'show'])->name('pesanan.show');
    Route::get('/pesanan/{transaksi}/bayar', [PesananPelangganController::class, 'bayar'])->name('pesanan.bayar');
    Route::post('/pesanan/{transaksi}/sync', [PesananPelangganController::class, 'syncStatus'])->name('pesanan.sync');
    Route::get('/pesanan/{transaksi}/nota', [PesananPelangganController::class, 'nota'])->name('pesanan.nota');
});

// ==========================================
// 4. FITUR KASIR — POINT OF SALE (Role: kasir)
// ==========================================
Route::middleware(['auth', 'role:kasir'])->prefix('kasir')->name('kasir.')->group(function () {
    Route::get('/dashboard', [KasirDashboardController::class, 'index'])->name('dashboard');

    // Point of Sale (POS)
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos', [PosController::class, 'store'])->name('pos.store');
    Route::get('/pos/nota/{transaksi}', [PosController::class, 'nota'])->name('pos.nota');
    Route::get('/riwayat', [PosController::class, 'riwayat'])->name('pos.riwayat');
});

// ==========================================
// 5. FITUR ADMIN (Role: admin)
// ==========================================
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Produk
    Route::get('/produk', [ProdukController::class, 'index'])->name('produk.index');
    Route::post('/produk', [ProdukController::class, 'store'])->name('produk.store');
    Route::post('/produk/{produk}', [ProdukController::class, 'update'])->name('produk.update');
    Route::patch('/produk/{produk}/toggle-status', [ProdukController::class, 'toggleStatus'])->name('produk.toggle-status');
    Route::delete('/produk/{produk}', [ProdukController::class, 'destroy'])->name('produk.destroy');

    // Kategori
    Route::get('/kategori', [KategoriController::class, 'index'])->name('kategori.index');
    Route::post('/kategori', [KategoriController::class, 'store'])->name('kategori.store');
    Route::put('/kategori/{kategori}', [KategoriController::class, 'update'])->name('kategori.update');
    Route::patch('/kategori/{kategori}/toggle-status', [KategoriController::class, 'toggleStatus'])->name('kategori.toggle-status');
    Route::delete('/kategori/{kategori}', [KategoriController::class, 'destroy'])->name('kategori.destroy');

    // Pelanggan
    Route::get('/pelanggan', [AdminPelangganController::class, 'index'])->name('pelanggan.index');
    Route::get('/pelanggan/{id}', [AdminPelangganController::class, 'show'])->name('pelanggan.show');
    Route::put('/pelanggan/{pelanggan}', [AdminPelangganController::class, 'update'])->name('pelanggan.update');

    // Kasir
    Route::get('/kasir', [KasirController::class, 'index'])->name('kasir.index');
    Route::post('/kasir', [KasirController::class, 'store'])->name('kasir.store');
    Route::put('/kasir/{kasir}', [KasirController::class, 'update'])->name('kasir.update');
    Route::patch('/kasir/{kasir}/toggle-status', [KasirController::class, 'toggleStatus'])->name('kasir.toggle-status');

    // Cabang
    Route::get('/cabang', [CabangController::class, 'index'])->name('cabang.index');
    Route::post('/cabang', [CabangController::class, 'store'])->name('cabang.store');
    Route::put('/cabang/{cabang}', [CabangController::class, 'update'])->name('cabang.update');
    Route::patch('/cabang/{cabang}/toggle-status', [CabangController::class, 'toggleStatus'])->name('cabang.toggle-status');

    // Stok & Mutasi
    Route::get('/stok', [StokController::class, 'index'])->name('stok.index');
    Route::post('/stok/sesuaikan', [StokController::class, 'update'])->name('stok.update');
    Route::get('/stok/mutasi', [StokController::class, 'mutasi'])->name('stok.mutasi');

    // Transaksi Semua Jenis
    Route::get('/transaksi', [TransaksiController::class, 'index'])->name('transaksi.index');
    Route::get('/transaksi/{transaksi}', [TransaksiController::class, 'show'])->name('transaksi.show');
    Route::get('/transaksi/{transaksi}/nota', [TransaksiController::class, 'nota'])->name('transaksi.nota');

    // Pesanan Online
    Route::get('/pesanan', [PesananOnlineController::class, 'index'])->name('pesanan.index');
    Route::get('/pesanan/{transaksi}', [PesananOnlineController::class, 'show'])->name('pesanan.show');
    Route::patch('/pesanan/{transaksi}/status', [PesananOnlineController::class, 'updateStatus'])->name('pesanan.update-status');

    // Laporan Penjualan
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/export-csv', [LaporanController::class, 'exportCsv'])->name('laporan.export');

    // Pengaturan Toko
    Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');
    Route::post('/pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');
});

// ==========================================
// 6. SIMULATOR PEMBAYARAN MIDTRANS SANDBOX (Testing & Dev)
// ==========================================
Route::post('/payment/midtrans/simulate', [SimulasiPaymentController::class, 'simulate'])
    ->name('payment.midtrans.simulate');
