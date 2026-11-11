<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransaksiController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Transaksi::with(['pelanggan', 'kasir', 'cabang', 'pembayaranTerbaru']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('kode_transaksi', 'like', "%{$search}%")
                    ->orWhereHas('pelanggan', fn ($qp) => $qp->where('nama_pelanggan', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('jenis')) {
            $query->where('jenis_transaksi', $request->jenis);
        }

        if ($request->filled('cabang')) {
            $query->where('id_cabang', $request->cabang);
        }

        if ($request->filled('status_bayar')) {
            $query->where('status_pembayaran', $request->status_bayar);
        }

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal', '>=', $request->tanggal_mulai);
        }

        if ($request->filled('tanggal_akhir')) {
            $query->whereDate('tanggal', '<=', $request->tanggal_akhir);
        }

        $transaksi = $query->latest('id_transaksi')->paginate(15)->withQueryString();
        $cabangList = Cabang::all();
        $kasirList = User::where('role', 'kasir')->get();

        return Inertia::render('Admin/Transaksi/Index', [
            'transaksi' => $transaksi,
            'cabangList' => $cabangList,
            'kasirList' => $kasirList,
            'filters' => $request->only(['search', 'jenis', 'cabang', 'status_bayar', 'tanggal_mulai', 'tanggal_akhir']),
        ]);
    }

    public function show(Transaksi $transaksi): Response
    {
        $transaksi->load([
            'pelanggan',
            'kasir',
            'cabang',
            'detailTransaksi.produk',
            'pembayaran',
            'mutasiStok',
        ]);

        return Inertia::render('Admin/Transaksi/Show', [
            'transaksi' => $transaksi,
        ]);
    }

    public function nota(Transaksi $transaksi): Response
    {
        $transaksi->load([
            'pelanggan',
            'kasir',
            'cabang',
            'detailTransaksi',
            'pembayaranTerbaru',
        ]);

        return Inertia::render('Admin/Transaksi/Nota', [
            'transaksi' => $transaksi,
        ]);
    }
}
