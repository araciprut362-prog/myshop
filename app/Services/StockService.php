<?php

namespace App\Services;

use App\Models\MutasiStok;
use App\Models\StokCabang;
use App\Models\Transaksi;
use Exception;

class StockService
{
    /**
     * Kurangi stok untuk penjualan POS langsung.
     */
    public function kurangiStokPos(int $idCabang, int $idProduk, int $jumlah, int $idUser, Transaksi $transaksi): void
    {
        if ($jumlah <= 0) {
            throw new Exception('Jumlah barang harus lebih dari 0.');
        }

        $stokCabang = StokCabang::where('id_cabang', $idCabang)
            ->where('id_produk', $idProduk)
            ->lockForUpdate()
            ->first();

        if (! $stokCabang) {
            throw new Exception('Produk tidak ditemukan pada cabang ini.');
        }

        if ($stokCabang->stok < $jumlah) {
            throw new Exception("Stok tidak mencukupi untuk produk ID {$idProduk}. Stok tersedia: {$stokCabang->stok}, diminta: {$jumlah}.");
        }

        $stokCabang->stok -= $jumlah;
        $stokCabang->save();

        MutasiStok::create([
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'id_user' => $idUser,
            'id_transaksi' => $transaksi->id_transaksi,
            'jenis_mutasi' => 'KELUAR',
            'jumlah' => $jumlah,
            'keterangan' => "Penjualan POS {$transaksi->kode_transaksi}",
            'created_at' => now(),
        ]);
    }

    /**
     * Reservasi stok saat checkout online.
     */
    public function reservasiStokOnline(int $idCabang, int $idProduk, int $jumlah, Transaksi $transaksi, ?int $idUser = null): void
    {
        if ($jumlah <= 0) {
            throw new Exception('Jumlah barang harus lebih dari 0.');
        }

        $stokCabang = StokCabang::where('id_cabang', $idCabang)
            ->where('id_produk', $idProduk)
            ->lockForUpdate()
            ->first();

        if (! $stokCabang) {
            throw new Exception('Produk tidak ditemukan pada cabang pemenuhan ini.');
        }

        if ($stokCabang->stok < $jumlah) {
            throw new Exception("Stok produk tidak mencukupi. Stok tersisa: {$stokCabang->stok}.");
        }

        // Kurangi stok fisik untuk reservasi pesanan online
        $stokCabang->stok -= $jumlah;
        $stokCabang->save();

        MutasiStok::create([
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'id_user' => $idUser,
            'id_transaksi' => $transaksi->id_transaksi,
            'jenis_mutasi' => 'RESERVASI',
            'jumlah' => $jumlah,
            'keterangan' => "Reservasi stok pesanan online {$transaksi->kode_transaksi}",
            'created_at' => now(),
        ]);
    }

    /**
     * Kembalikan stok jika pesanan dibatalkan atau kedaluwarsa.
     * IDEMPOTENT: hanya dikembalikan jika status_stok saat ini adalah DIRESERVASI.
     */
    public function kembalikanStokReservasi(Transaksi $transaksi, ?int $idUser = null, string $alasan = 'Kedaluwarsa/Batal'): bool
    {
        // Kunci baris transaksi untuk mencegah race condition
        $trx = Transaksi::where('id_transaksi', $transaksi->id_transaksi)
            ->lockForUpdate()
            ->first();

        if (! $trx || $trx->status_stok !== 'DIRESERVASI') {
            return false; // Sudah dikembalikan atau bukan status reservasi
        }

        $idCabang = $trx->id_cabang ?? 1;

        foreach ($trx->detailTransaksi as $detail) {
            $stokCabang = StokCabang::where('id_cabang', $idCabang)
                ->where('id_produk', $detail->id_produk)
                ->lockForUpdate()
                ->first();

            if ($stokCabang) {
                $stokCabang->stok += $detail->jumlah;
                $stokCabang->save();

                MutasiStok::create([
                    'id_cabang' => $idCabang,
                    'id_produk' => $detail->id_produk,
                    'id_user' => $idUser,
                    'id_transaksi' => $trx->id_transaksi,
                    'jenis_mutasi' => 'BATAL_RESERVASI',
                    'jumlah' => $detail->jumlah,
                    'keterangan' => "Pengembalian stok pesanan online {$trx->kode_transaksi} ({$alasan})",
                    'created_at' => now(),
                ]);
            }
        }

        $trx->status_stok = 'DIKEMBALIKAN';
        $trx->save();

        return true;
    }

    /**
     * Konfirmasi pembayaran online: ubah status_stok menjadi DIKURANGI tanpa mengurangi fisik lagi.
     */
    public function konfirmasiStokTerbayar(Transaksi $transaksi): bool
    {
        $trx = Transaksi::where('id_transaksi', $transaksi->id_transaksi)
            ->lockForUpdate()
            ->first();

        if (! $trx) {
            return false;
        }

        if ($trx->status_stok === 'DIRESERVASI') {
            $trx->status_stok = 'DIKURANGI';
            $trx->save();

            return true;
        }

        return false;
    }

    /**
     * Penyesuaian stok manual oleh Admin.
     */
    public function sesuaikanStok(int $idCabang, int $idProduk, int $stokBaru, int $idUser, string $keterangan = 'Penyesuaian stok'): void
    {
        if ($stokBaru < 0) {
            throw new Exception('Stok tidak boleh bernilai negatif.');
        }

        $stokCabang = StokCabang::where('id_cabang', $idCabang)
            ->where('id_produk', $idProduk)
            ->lockForUpdate()
            ->first();

        if (! $stokCabang) {
            $stokCabang = StokCabang::create([
                'id_cabang' => $idCabang,
                'id_produk' => $idProduk,
                'stok' => 0,
                'stok_minimum' => 5,
            ]);
        }

        $selisih = $stokBaru - $stokCabang->stok;
        if ($selisih === 0) {
            return;
        }

        $jenisMutasi = $selisih > 0 ? 'MASUK' : 'KELUAR';
        $stokCabang->stok = $stokBaru;
        $stokCabang->save();

        MutasiStok::create([
            'id_cabang' => $idCabang,
            'id_produk' => $idProduk,
            'id_user' => $idUser,
            'id_transaksi' => null,
            'jenis_mutasi' => $jenisMutasi,
            'jumlah' => abs($selisih),
            'keterangan' => $keterangan,
            'created_at' => now(),
        ]);
    }
}
