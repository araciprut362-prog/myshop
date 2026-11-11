<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanController extends Controller
{
    public function index(Request $request): Response
    {
        $periode = $request->get('periode', 'bulan_ini');
        $idCabang = $request->get('cabang');
        $jenisTransaksi = $request->get('jenis');
        $idKasir = $request->get('kasir');

        // Tentukan rentang tanggal
        [$startDate, $endDate] = match ($periode) {
            'hari_ini' => [now()->toDateString(), now()->toDateString()],
            'kemarin' => [now()->subDay()->toDateString(), now()->subDay()->toDateString()],
            '7_hari' => [now()->subDays(6)->toDateString(), now()->toDateString()],
            '30_hari' => [now()->subDays(29)->toDateString(), now()->toDateString()],
            'bulan_ini' => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
            'tahun_ini' => [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
            'custom' => [
                $request->get('tanggal_mulai', now()->startOfMonth()->toDateString()),
                $request->get('tanggal_akhir', now()->toDateString()),
            ],
            default => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
        };

        $baseQuery = Transaksi::whereBetween('tanggal', [$startDate, $endDate])
            ->where('status_pembayaran', 'BERHASIL')
            ->where('status_transaksi', '<>', 'DIBATALKAN');

        if (! empty($idCabang)) {
            $baseQuery->where('id_cabang', $idCabang);
        }
        if (! empty($jenisTransaksi)) {
            $baseQuery->where('jenis_transaksi', $jenisTransaksi);
        }
        if (! empty($idKasir)) {
            $baseQuery->where('id_kasir', $idKasir);
        }

        $totalOmzet = (float) (clone $baseQuery)->sum('total_belanja');
        $jumlahTransaksi = (int) (clone $baseQuery)->count();
        $rataRataTransaksi = $jumlahTransaksi > 0 ? round($totalOmzet / $jumlahTransaksi, 2) : 0;
        $totalDiskon = (float) (clone $baseQuery)->sum('diskon');
        $totalPajak = (float) (clone $baseQuery)->sum('pajak');

        // Produk Terlaris
        $produkTerlaris = DB::table('tbl_detail_transaksi as d')
            ->join('tbl_transaksi as t', 't.id_transaksi', '=', 'd.id_transaksi')
            ->join('tbl_produk as p', 'p.id_produk', '=', 'd.id_produk')
            ->whereBetween('t.tanggal', [$startDate, $endDate])
            ->where('t.status_pembayaran', 'BERHASIL')
            ->where('t.status_transaksi', '<>', 'DIBATALKAN')
            ->when(! empty($idCabang), fn ($q) => $q->where('t.id_cabang', $idCabang))
            ->when(! empty($jenisTransaksi), fn ($q) => $q->where('t.jenis_transaksi', $jenisTransaksi))
            ->select('p.nama_produk', 'p.sku', DB::raw('SUM(d.jumlah) as total_qty'), DB::raw('SUM(d.subtotal) as total_omzet'))
            ->groupBy('p.id_produk', 'p.nama_produk', 'p.sku')
            ->orderByDesc('total_qty')
            ->limit(8)
            ->get();

        // Penjualan per Cabang
        $penjualanCabang = DB::table('tbl_cabang as c')
            ->leftJoin('tbl_transaksi as t', function ($join) use ($startDate, $endDate) {
                $join->on('t.id_cabang', '=', 'c.id_cabang')
                    ->whereBetween('t.tanggal', [$startDate, $endDate])
                    ->where('t.status_pembayaran', '=', 'BERHASIL')
                    ->where('t.status_transaksi', '<>', 'DIBATALKAN');
            })
            ->select('c.nama_cabang', 'c.kota', DB::raw('COUNT(t.id_transaksi) as total_trx'), DB::raw('COALESCE(SUM(t.total_belanja), 0) as total_omzet'))
            ->groupBy('c.id_cabang', 'c.nama_cabang', 'c.kota')
            ->get();

        // Penjualan per Kasir
        $penjualanKasir = DB::table('tbl_user as u')
            ->where('u.role', 'kasir')
            ->leftJoin('tbl_transaksi as t', function ($join) use ($startDate, $endDate) {
                $join->on('t.id_kasir', '=', 'u.id_user')
                    ->whereBetween('t.tanggal', [$startDate, $endDate])
                    ->where('t.status_pembayaran', '=', 'BERHASIL')
                    ->where('t.status_transaksi', '<>', 'DIBATALKAN');
            })
            ->leftJoin('tbl_cabang as c', 'c.id_cabang', '=', 'u.id_cabang')
            ->select('u.nama_lengkap as nama_kasir', 'c.nama_cabang', DB::raw('COUNT(t.id_transaksi) as total_trx'), DB::raw('COALESCE(SUM(t.total_belanja), 0) as total_omzet'))
            ->groupBy('u.id_user', 'u.nama_lengkap', 'c.nama_cabang')
            ->get();

        // Perbandingan POS dan ONLINE
        $perbandinganJenis = [
            'pos' => [
                'count' => (clone $baseQuery)->where('jenis_transaksi', 'POS')->count(),
                'total' => (float) (clone $baseQuery)->where('jenis_transaksi', 'POS')->sum('total_belanja'),
            ],
            'online' => [
                'count' => (clone $baseQuery)->where('jenis_transaksi', 'ONLINE')->count(),
                'total' => (float) (clone $baseQuery)->where('jenis_transaksi', 'ONLINE')->sum('total_belanja'),
            ],
        ];

        // Daftar Transaksi pada periode
        $transaksiList = (clone $baseQuery)
            ->with(['cabang', 'kasir', 'pelanggan', 'pembayaranTerbaru'])
            ->latest('id_transaksi')
            ->paginate(15)
            ->withQueryString();

        $cabangList = Cabang::all();
        $kasirList = User::where('role', 'kasir')->get();

        return Inertia::render('Admin/Laporan/Index', [
            'ringkasan' => [
                'total_omzet' => $totalOmzet,
                'jumlah_transaksi' => $jumlahTransaksi,
                'rata_rata' => $rataRataTransaksi,
                'total_diskon' => $totalDiskon,
                'total_pajak' => $totalPajak,
            ],
            'produkTerlaris' => $produkTerlaris,
            'penjualanCabang' => $penjualanCabang,
            'penjualanKasir' => $penjualanKasir,
            'perbandinganJenis' => $perbandinganJenis,
            'transaksiList' => $transaksiList,
            'cabangList' => $cabangList,
            'kasirList' => $kasirList,
            'filters' => [
                'periode' => $periode,
                'cabang' => $idCabang,
                'jenis' => $jenisTransaksi,
                'kasir' => $idKasir,
                'tanggal_mulai' => $startDate,
                'tanggal_akhir' => $endDate,
            ],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $startDate = $request->get('tanggal_mulai', now()->startOfMonth()->toDateString());
        $endDate = $request->get('tanggal_akhir', now()->toDateString());

        $query = Transaksi::with(['pelanggan', 'kasir', 'cabang'])
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->where('status_pembayaran', 'BERHASIL')
            ->where('status_transaksi', '<>', 'DIBATALKAN');

        if ($request->filled('cabang')) {
            $query->where('id_cabang', $request->cabang);
        }
        if ($request->filled('jenis')) {
            $query->where('jenis_transaksi', $request->jenis);
        }

        $filename = "laporan-penjualan-myshop-{$startDate}-sd-{$endDate}.csv";

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Kode Transaksi',
                'Tanggal',
                'Waktu',
                'Jenis Transaksi',
                'Cabang',
                'Kasir',
                'Pelanggan',
                'Subtotal',
                'Diskon',
                'Pajak',
                'Ongkir',
                'Total Belanja',
                'Status Pembayaran',
            ]);

            $query->chunk(100, function ($transactions) use ($handle) {
                foreach ($transactions as $t) {
                    fputcsv($handle, [
                        $t->kode_transaksi,
                        $t->tanggal,
                        $t->waktu_transaksi,
                        $t->jenis_transaksi,
                        $t->cabang?->nama_cabang ?? '-',
                        $t->kasir?->nama_lengkap ?? '-',
                        $t->pelanggan?->nama_pelanggan ?? 'Umum',
                        $t->subtotal,
                        $t->diskon,
                        $t->pajak,
                        $t->ongkir,
                        $t->total_belanja,
                        $t->status_pembayaran,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
