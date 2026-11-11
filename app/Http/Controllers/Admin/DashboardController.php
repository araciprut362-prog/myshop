<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\Kategori;
use App\Models\LogAktivitas;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $today = now()->toDateString();
        $thisMonth = now()->month;
        $thisYear = now()->year;

        // Metrik Statistik Nyata dari Database
        $totalPelanggan = Pelanggan::count();
        $totalProdukAktif = Produk::where('status', 'AKTIF')->count();
        $totalKategori = Kategori::count();
        $totalCabang = Cabang::count();
        $totalTransaksi = Transaksi::count();

        // Pendapatan dihitung HANYA dari transaksi yang BERHASIL (tidak pending/gagal/batal)
        $totalPenjualanBerhasil = (float) Transaksi::where('status_pembayaran', 'BERHASIL')
            ->where('status_transaksi', '<>', 'DIBATALKAN')
            ->sum('total_belanja');

        $pendapatanHariIni = (float) Transaksi::where('status_pembayaran', 'BERHASIL')
            ->where('status_transaksi', '<>', 'DIBATALKAN')
            ->whereDate('tanggal', $today)
            ->sum('total_belanja');

        $pendapatanBulanIni = (float) Transaksi::where('status_pembayaran', 'BERHASIL')
            ->where('status_transaksi', '<>', 'DIBATALKAN')
            ->whereYear('tanggal', $thisYear)
            ->whereMonth('tanggal', $thisMonth)
            ->sum('total_belanja');

        $transaksiPos = Transaksi::where('jenis_transaksi', 'POS')->count();
        $pesananOnline = Transaksi::where('jenis_transaksi', 'ONLINE')->count();
        $pesananMenungguPembayaran = Transaksi::where('status_pembayaran', 'PENDING')->count();

        $produkStokMenipis = DB::table('tbl_stok_cabang')
            ->whereColumn('stok', '<=', 'stok_minimum')
            ->distinct('id_produk')
            ->count('id_produk');

        // Tren Penjualan 7 Hari Terakhir
        $chartHarian = [];
        for ($i = 6; $i >= 0; $i--) {
            $tgl = now()->subDays($i)->toDateString();
            $label = Carbon::parse($tgl)->locale('id')->isoFormat('D MMM');
            $omzet = (float) Transaksi::where('status_pembayaran', 'BERHASIL')
                ->where('status_transaksi', '<>', 'DIBATALKAN')
                ->whereDate('tanggal', $tgl)
                ->sum('total_belanja');

            $chartHarian[] = [
                'tanggal' => $label,
                'total' => $omzet,
            ];
        }

        // Penjualan Per Cabang Menggunakan View view_laporan_penjualan_cabang
        $penjualanCabang = DB::table('view_laporan_penjualan_cabang')
            ->select('nama_cabang', 'total_penjualan', 'jumlah_transaksi')
            ->get();

        // 5 Produk Terlaris Berdasarkan Kuantitas Terjual
        $produkTerlaris = DB::table('tbl_detail_transaksi as d')
            ->join('tbl_transaksi as t', 't.id_transaksi', '=', 'd.id_transaksi')
            ->join('tbl_produk as p', 'p.id_produk', '=', 'd.id_produk')
            ->where('t.status_pembayaran', 'BERHASIL')
            ->select('p.nama_produk', 'p.sku', DB::raw('SUM(d.jumlah) as total_terjual'), DB::raw('SUM(d.subtotal) as total_omzet'))
            ->groupBy('p.id_produk', 'p.nama_produk', 'p.sku')
            ->orderByDesc('total_terjual')
            ->limit(5)
            ->get();

        // Transaksi Terbaru
        $transaksiTerbaru = Transaksi::with(['pelanggan', 'kasir', 'cabang'])
            ->latest('id_transaksi')
            ->limit(5)
            ->get();

        // Aktivitas Terbaru
        $aktivitasTerbaru = LogAktivitas::with('user')
            ->latest('id_log')
            ->limit(7)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_pelanggan' => $totalPelanggan,
                'total_produk_aktif' => $totalProdukAktif,
                'total_kategori' => $totalKategori,
                'total_cabang' => $totalCabang,
                'total_transaksi' => $totalTransaksi,
                'total_penjualan_berhasil' => $totalPenjualanBerhasil,
                'pendapatan_hari_ini' => $pendapatanHariIni,
                'pendapatan_bulan_ini' => $pendapatanBulanIni,
                'transaksi_pos' => $transaksiPos,
                'pesanan_online' => $pesananOnline,
                'pesanan_menunggu_pembayaran' => $pesananMenungguPembayaran,
                'produk_stok_menipis' => $produkStokMenipis,
            ],
            'charts' => [
                'harian' => $chartHarian,
                'cabang' => $penjualanCabang,
                'pos_count' => $transaksiPos,
                'online_count' => $pesananOnline,
            ],
            'produkTerlaris' => $produkTerlaris,
            'transaksiTerbaru' => $transaksiTerbaru,
            'aktivitasTerbaru' => $aktivitasTerbaru,
        ]);
    }
}
