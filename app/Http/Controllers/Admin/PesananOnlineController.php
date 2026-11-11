<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\Transaksi;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PesananOnlineController extends Controller
{
    public function __construct(
        protected StockService $stockService
    ) {}

    public function index(Request $request): Response
    {
        $query = Transaksi::where('jenis_transaksi', 'ONLINE')
            ->with(['pelanggan', 'pembayaranTerbaru', 'detailTransaksi']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('kode_transaksi', 'like', "%{$search}%")
                    ->orWhereHas('pelanggan', fn ($qp) => $qp->where('nama_pelanggan', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status_transaksi')) {
            $query->where('status_transaksi', $request->status_transaksi);
        }

        if ($request->filled('status_pembayaran')) {
            $query->where('status_pembayaran', $request->status_pembayaran);
        }

        $pesanan = $query->latest('id_transaksi')->paginate(10)->withQueryString();

        return Inertia::render('Admin/Pesanan/Index', [
            'pesanan' => $pesanan,
            'filters' => $request->only(['search', 'status_transaksi', 'status_pembayaran']),
        ]);
    }

    public function show(Transaksi $transaksi): Response
    {
        if ($transaksi->jenis_transaksi !== 'ONLINE') {
            abort(404);
        }

        $transaksi->load([
            'pelanggan',
            'detailTransaksi.produk',
            'pembayaran',
            'cabang',
        ]);

        return Inertia::render('Admin/Pesanan/Show', [
            'pesanan' => $transaksi,
        ]);
    }

    public function updateStatus(Request $request, Transaksi $transaksi): RedirectResponse
    {
        if ($transaksi->jenis_transaksi !== 'ONLINE') {
            abort(404);
        }

        $validated = $request->validate([
            'status_transaksi' => ['required', 'in:MENUNGGU_PEMBAYARAN,DIPROSES,DIKIRIM,SELESAI,DIBATALKAN'],
        ]);

        $newStatus = $validated['status_transaksi'];

        // Aturan: status DIPROSES, DIKIRIM, SELESAI hanya boleh jika pembayaran BERHASIL
        if (in_array($newStatus, ['DIPROSES', 'DIKIRIM', 'SELESAI'], true) && $transaksi->status_pembayaran !== 'BERHASIL') {
            return redirect()->back()->with('error', 'Pesanan online belum dibayar atau pembayaran belum terverifikasi.');
        }

        // Jika dibatalkan oleh admin, kembalikan stok tepat satu kali
        if ($newStatus === 'DIBATALKAN' && $transaksi->status_transaksi !== 'DIBATALKAN') {
            $transaksi->status_transaksi = 'DIBATALKAN';
            $transaksi->status_pembayaran = 'DIBATALKAN';
            $transaksi->save();

            $this->stockService->kembalikanStokReservasi($transaksi, Auth::id(), 'Dibatalkan oleh Admin');

            LogAktivitas::catat(
                Auth::id(),
                'Pesanan online dibatalkan oleh Admin',
                'tbl_transaksi',
                $transaksi->id_transaksi,
                "Pesanan {$transaksi->kode_transaksi} dibatalkan dan stok dikembalikan."
            );

            return redirect()->back()->with('success', 'Pesanan online berhasil dibatalkan dan stok telah dikembalikan.');
        }

        $transaksi->status_transaksi = $newStatus;
        $transaksi->save();

        LogAktivitas::catat(
            Auth::id(),
            "Ubah status pesanan menjadi {$newStatus}",
            'tbl_transaksi',
            $transaksi->id_transaksi,
            "Kode: {$transaksi->kode_transaksi}"
        );

        return redirect()->back()->with('success', "Status pesanan berhasil diubah menjadi {$newStatus}.");
    }
}
