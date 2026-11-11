<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class KasirDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $idCabang = $user->id_cabang;

        if (! $idCabang) {
            abort(403, 'Akun kasir Anda belum memiliki penempatan cabang.');
        }

        $cabang = $user->cabang;
        $today = now()->toDateString();

        $transaksiHariIni = Transaksi::where('id_kasir', $user->id_user)
            ->whereDate('tanggal', $today)
            ->where('status_pembayaran', 'BERHASIL')
            ->count();

        $pendapatanHariIni = (float) Transaksi::where('id_kasir', $user->id_user)
            ->whereDate('tanggal', $today)
            ->where('status_pembayaran', 'BERHASIL')
            ->sum('total_belanja');

        $transaksiBulanIni = Transaksi::where('id_kasir', $user->id_user)
            ->whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)
            ->where('status_pembayaran', 'BERHASIL')
            ->count();

        $pendapatanBulanIni = (float) Transaksi::where('id_kasir', $user->id_user)
            ->whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)
            ->where('status_pembayaran', 'BERHASIL')
            ->sum('total_belanja');

        $transaksiTerbaru = Transaksi::where('id_kasir', $user->id_user)
            ->with(['pelanggan', 'pembayaranTerbaru'])
            ->latest('id_transaksi')
            ->limit(5)
            ->get();

        return Inertia::render('Kasir/Dashboard', [
            'cabang' => $cabang,
            'stats' => [
                'transaksi_hari_ini' => $transaksiHariIni,
                'pendapatan_hari_ini' => $pendapatanHariIni,
                'transaksi_bulan_ini' => $transaksiBulanIni,
                'pendapatan_bulan_ini' => $pendapatanBulanIni,
            ],
            'transaksiTerbaru' => $transaksiTerbaru,
        ]);
    }
}
