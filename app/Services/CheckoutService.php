<?php

namespace App\Services;

use App\Models\DetailTransaksi;
use App\Models\Keranjang;
use App\Models\LogAktivitas;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Pengaturan;
use App\Models\Produk;
use App\Models\StokCabang;
use App\Models\Transaksi;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(
        protected StockService $stockService,
        protected MidtransService $midtransService
    ) {}

    /**
     * Memproses checkout pesanan toko online.
     *
     * @param array{
     *     nama_penerima?: string,
     *     no_hp?: string,
     *     alamat_pengiriman: string,
     *     kota?: string,
     *     kode_pos?: string,
     *     catatan?: string|null
     * } $checkoutData
     */
    public function prosesCheckout(User $user, array $checkoutData): array
    {
        if (! $user->id_pelanggan) {
            throw new Exception('Akun pengguna tidak terhubung dengan profil pelanggan.');
        }

        $pelanggan = Pelanggan::find($user->id_pelanggan);
        if (! $pelanggan) {
            throw new Exception('Profil pelanggan tidak ditemukan.');
        }

        // Ambil keranjang aktif
        $keranjang = Keranjang::where('id_pelanggan', $pelanggan->id_pelanggan)
            ->where('status', 'AKTIF')
            ->with('details.produk')
            ->first();

        if (! $keranjang || $keranjang->details->isEmpty()) {
            throw new Exception('Keranjang belanja Anda masih kosong.');
        }

        // Cabang pemenuhan online default (Cabang Utama, ID: 1)
        $idCabangDefault = (int) Pengaturan::get('cabang_online_default', '1');
        if ($idCabangDefault <= 0) {
            $idCabangDefault = 1;
        }

        return DB::transaction(function () use ($user, $pelanggan, $keranjang, $idCabangDefault, $checkoutData) {
            // Update data profil pelanggan jika ada perubahan alamat/nomor HP
            if (! empty($checkoutData['alamat_pengiriman'])) {
                $pelanggan->alamat = $checkoutData['alamat_pengiriman'];
            }
            if (! empty($checkoutData['no_hp'])) {
                $pelanggan->no_hp = $checkoutData['no_hp'];
            }
            if (! empty($checkoutData['kota'])) {
                $pelanggan->kota = $checkoutData['kota'];
            }
            if (! empty($checkoutData['kode_pos'])) {
                $pelanggan->kode_pos = $checkoutData['kode_pos'];
            }
            $pelanggan->save();

            $pajakPersen = (float) Pengaturan::get('pajak_simulasi_persen', '11');
            $ongkir = 15000.00; // Flat standard shipping untuk online
            $diskon = 0.00;

            // Validasi harga dan stok dari database
            $subtotal = 0;
            $itemsData = [];

            foreach ($keranjang->details as $item) {
                $idProduk = $item->id_produk;
                $jumlah = $item->jumlah;

                if ($jumlah <= 0) {
                    continue;
                }

                $produk = Produk::where('id_produk', $idProduk)
                    ->where('status', 'AKTIF')
                    ->first();

                if (! $produk) {
                    throw new Exception("Produk '{$item->produk?->nama_produk}' saat ini tidak aktif atau tidak ditemukan.");
                }

                // Kunci dan cek stok cabang pemenuhan
                $stokCabang = StokCabang::where('id_cabang', $idCabangDefault)
                    ->where('id_produk', $idProduk)
                    ->lockForUpdate()
                    ->first();

                if (! $stokCabang || $stokCabang->stok < $jumlah) {
                    $sisa = $stokCabang ? $stokCabang->stok : 0;
                    throw new Exception("Stok untuk '{$produk->nama_produk}' tidak mencukupi (Tersedia: {$sisa}, Diminta: {$jumlah}).");
                }

                $itemSubtotal = $produk->harga * $jumlah;
                $subtotal += $itemSubtotal;

                $itemsData[] = [
                    'produk' => $produk,
                    'jumlah' => $jumlah,
                    'harga_satuan' => $produk->harga,
                    'diskon_item' => 0,
                    'subtotal' => $itemSubtotal,
                ];
            }

            if (empty($itemsData)) {
                throw new Exception('Tidak ada barang belanja yang valid.');
            }

            // Hitung pajak & total belanja
            $taxable = max(0, $subtotal - $diskon);
            $pajak = round(($taxable * $pajakPersen) / 100, 2);
            $totalBelanja = round($taxable + $pajak + $ongkir, 2);

            // Buat kode transaksi unik WEB-YYYYMMDD-XXXX
            $datePrefix = now()->format('Ymd');
            $countToday = Transaksi::whereDate('tanggal', now()->toDateString())
                ->where('jenis_transaksi', 'ONLINE')
                ->count() + 1;
            $kodeTransaksi = sprintf('WEB-%s-%04d', $datePrefix, $countToday);

            // Buat header transaksi
            $transaksi = Transaksi::create([
                'kode_transaksi' => $kodeTransaksi,
                'tanggal' => now()->toDateString(),
                'waktu_transaksi' => now(),
                'id_pelanggan' => $pelanggan->id_pelanggan,
                'id_kasir' => null,
                'id_cabang' => $idCabangDefault,
                'jenis_transaksi' => 'ONLINE',
                'status_transaksi' => 'MENUNGGU_PEMBAYARAN',
                'status_pembayaran' => 'PENDING',
                'status_stok' => 'DIRESERVASI',
                'subtotal' => $subtotal,
                'diskon' => $diskon,
                'pajak' => $pajak,
                'ongkir' => $ongkir,
                'total_belanja' => $totalBelanja,
                'alamat_pengiriman' => $checkoutData['alamat_pengiriman'] ?? $pelanggan->alamat,
                'catatan' => $checkoutData['catatan'] ?? null,
            ]);

            // Simpan detail transaksi dan reservasi stok
            foreach ($itemsData as $data) {
                $produk = $data['produk'];
                DetailTransaksi::create([
                    'id_transaksi' => $transaksi->id_transaksi,
                    'id_produk' => $produk->id_produk,
                    'nama_produk_snapshot' => $produk->nama_produk,
                    'sku_snapshot' => $produk->sku,
                    'harga_satuan' => $data['harga_satuan'],
                    'jumlah' => $data['jumlah'],
                    'diskon_item' => $data['diskon_item'],
                    'subtotal' => $data['subtotal'],
                    'created_at' => now(),
                ]);

                // Reservasi stok cabang
                $this->stockService->reservasiStokOnline($idCabangDefault, $produk->id_produk, $data['jumlah'], $transaksi, $user->id_user);
            }

            // Buat record pembayaran awal
            $pembayaran = Pembayaran::create([
                'id_transaksi' => $transaksi->id_transaksi,
                'metode' => 'MIDTRANS',
                'status' => 'PENDING',
                'jumlah_bayar' => $totalBelanja,
                'provider_order_id' => $kodeTransaksi,
            ]);

            // Buat Snap Token melalui MidtransService
            $transaksi->load(['detailTransaksi', 'pelanggan']);
            $snapResult = $this->midtransService->createSnapTransaction($transaksi, $user);

            $pembayaran->snap_token = $snapResult['snap_token'];
            $pembayaran->redirect_url = $snapResult['redirect_url'];
            $pembayaran->save();

            // Kosongkan keranjang pelanggan
            $keranjang->details()->delete();
            $keranjang->status = 'CHECKOUT';
            $keranjang->save();

            // Buat keranjang baru yang AKTIF untuk belanja selanjutnya
            Keranjang::create([
                'id_pelanggan' => $pelanggan->id_pelanggan,
                'id_cabang' => $idCabangDefault,
                'status' => 'AKTIF',
            ]);

            // Catat log aktivitas
            LogAktivitas::catat(
                $user->id_user,
                'Checkout pesanan online selesai',
                'tbl_transaksi',
                $transaksi->id_transaksi,
                "Kode: {$transaksi->kode_transaksi}, Total: {$totalBelanja}, Menunggu Midtrans Sandbox"
            );

            return [
                'transaksi' => $transaksi,
                'snap_token' => $snapResult['snap_token'],
                'redirect_url' => $snapResult['redirect_url'],
            ];
        });
    }
}
