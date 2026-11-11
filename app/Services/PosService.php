<?php

namespace App\Services;

use App\Models\DetailTransaksi;
use App\Models\LogAktivitas;
use App\Models\Pembayaran;
use App\Models\Pengaturan;
use App\Models\Produk;
use App\Models\StokCabang;
use App\Models\Transaksi;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class PosService
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Memproses transaksi POS Kasir.
     *
     * @param  array<int, array{id_produk: int, jumlah: int}>  $items
     * @param array{
     *     id_pelanggan?: int|null,
     *     metode_pembayaran: string,
     *     uang_diterima?: float|null,
     *     diskon?: float|null,
     *     catatan?: string|null
     * } $paymentData
     */
    public function prosesTransaksiPos(User $kasir, array $items, array $paymentData): array
    {
        if (! $kasir->id_cabang) {
            throw new Exception('Akun kasir tidak terdaftar pada cabang manapun.');
        }

        if (empty($items)) {
            throw new Exception('Keranjang transaksi kasir tidak boleh kosong.');
        }

        $idCabang = $kasir->id_cabang;

        return DB::transaction(function () use ($kasir, $idCabang, $items, $paymentData) {
            $pajakPersen = (float) Pengaturan::get('pajak_simulasi_persen', '11');
            $diskon = max(0, (float) ($paymentData['diskon'] ?? 0));
            $metode = strtoupper($paymentData['metode_pembayaran'] ?? 'TUNAI');

            if (! in_array($metode, ['TUNAI', 'TRANSFER_BANK', 'QRIS'], true)) {
                $metode = 'TUNAI';
            }

            // Hitung subtotal dan validasi produk dari database
            $subtotal = 0;
            $itemsData = [];

            foreach ($items as $item) {
                $idProduk = (int) $item['id_produk'];
                $jumlah = (int) $item['jumlah'];

                if ($jumlah <= 0) {
                    continue;
                }

                $produk = Produk::where('id_produk', $idProduk)
                    ->where('status', 'AKTIF')
                    ->first();

                if (! $produk) {
                    throw new Exception("Produk ID {$idProduk} tidak ditemukan atau nonaktif.");
                }

                // Kunci stok cabang
                $stokCabang = StokCabang::where('id_cabang', $idCabang)
                    ->where('id_produk', $idProduk)
                    ->lockForUpdate()
                    ->first();

                if (! $stokCabang || $stokCabang->stok < $jumlah) {
                    $sisa = $stokCabang ? $stokCabang->stok : 0;
                    throw new Exception("Stok produk '{$produk->nama_produk}' tidak mencukupi (Tersedia: {$sisa}, Diminta: {$jumlah}).");
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
                throw new Exception('Tidak ada barang yang valid dalam transaksi.');
            }

            // Hitung pajak
            $taxable = max(0, $subtotal - $diskon);
            $pajak = round(($taxable * $pajakPersen) / 100, 2);
            $totalBelanja = round($taxable + $pajak, 2);

            $uangDiterima = (float) ($paymentData['uang_diterima'] ?? $totalBelanja);
            if ($metode === 'TUNAI' && $uangDiterima < $totalBelanja) {
                throw new Exception("Uang yang diterima kurang dari total belanja (Total: {$totalBelanja}, Diterima: {$uangDiterima}).");
            }
            $kembalian = max(0, $uangDiterima - $totalBelanja);

            // Buat kode transaksi unik POS-YYYYMMDD-XXXX
            $datePrefix = now()->format('Ymd');
            $countToday = Transaksi::whereDate('tanggal', now()->toDateString())
                ->where('jenis_transaksi', 'POS')
                ->count() + 1;
            $kodeTransaksi = sprintf('POS-%s-%04d', $datePrefix, $countToday);

            // Buat header transaksi
            $transaksi = Transaksi::create([
                'kode_transaksi' => $kodeTransaksi,
                'tanggal' => now()->toDateString(),
                'waktu_transaksi' => now(),
                'id_pelanggan' => $paymentData['id_pelanggan'] ?? null,
                'id_kasir' => $kasir->id_user,
                'id_cabang' => $idCabang,
                'jenis_transaksi' => 'POS',
                'status_transaksi' => 'SELESAI',
                'status_pembayaran' => 'BERHASIL',
                'status_stok' => 'DIKURANGI',
                'subtotal' => $subtotal,
                'diskon' => $diskon,
                'pajak' => $pajak,
                'ongkir' => 0,
                'total_belanja' => $totalBelanja,
                'alamat_pengiriman' => null,
                'catatan' => $paymentData['catatan'] ?? null,
            ]);

            // Simpan detail transaksi dan kurangi stok
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

                // Kurangi stok cabang dan catat mutasi
                $this->stockService->kurangiStokPos($idCabang, $produk->id_produk, $data['jumlah'], $kasir->id_user, $transaksi);
            }

            // Buat pembayaran
            Pembayaran::create([
                'id_transaksi' => $transaksi->id_transaksi,
                'metode' => $metode,
                'status' => 'BERHASIL',
                'jumlah_bayar' => $totalBelanja,
                'waktu_pembayaran' => now(),
            ]);

            // Catat log aktivitas
            LogAktivitas::catat(
                $kasir->id_user,
                'Transaksi POS kasir selesai',
                'tbl_transaksi',
                $transaksi->id_transaksi,
                "Kode: {$transaksi->kode_transaksi}, Total: {$totalBelanja}, Metode: {$metode}"
            );

            return [
                'transaksi' => $transaksi->load(['detailTransaksi.produk', 'kasir', 'cabang', 'pelanggan']),
                'uang_diterima' => $uangDiterima,
                'kembalian' => $kembalian,
            ];
        });
    }
}
