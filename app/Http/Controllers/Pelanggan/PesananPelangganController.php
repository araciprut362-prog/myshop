<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Services\MidtransService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PesananPelangganController extends Controller
{
    public function __construct(
        protected MidtransService $midtransService
    ) {}

    public function index(Request $request): Response
    {
        $user = Auth::user();

        $query = Transaksi::where('id_pelanggan', $user->id_pelanggan)
            ->with(['pembayaranTerbaru', 'detailTransaksi.produk']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('kode_transaksi', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('status_transaksi', $request->status);
        }

        $pesanan = $query->latest('id_transaksi')->paginate(10)->withQueryString();

        return Inertia::render('Pelanggan/Pesanan/Index', [
            'pesanan' => $pesanan,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function show(Transaksi $transaksi): Response
    {
        $user = Auth::user();

        // Otorisasi ketat: Cegah melihat pesanan orang lain
        if ($transaksi->id_pelanggan !== $user->id_pelanggan) {
            abort(403, 'Anda tidak berhak melihat pesanan milik pelanggan lain.');
        }

        // Sinkronkan status otomatis jika masih pending
        if ($transaksi->status_pembayaran === 'PENDING') {
            $this->midtransService->syncStatusFromMidtrans($transaksi);
            $transaksi->refresh();
        }

        $transaksi->load([
            'pelanggan',
            'detailTransaksi.produk',
            'pembayaran',
            'cabang',
        ]);

        return Inertia::render('Pelanggan/Pesanan/Show', [
            'pesanan' => $transaksi,
        ]);
    }

    public function bayar(Transaksi $transaksi): Response
    {
        $user = Auth::user();

        if ($transaksi->id_pelanggan !== $user->id_pelanggan) {
            abort(403, 'Anda tidak berhak mengakses halaman pembayaran pesanan ini.');
        }

        // Sinkronkan status otomatis dari Midtrans API jika masih pending
        if ($transaksi->status_pembayaran === 'PENDING') {
            $this->midtransService->syncStatusFromMidtrans($transaksi);
            $transaksi->refresh();
        }

        $pembayaran = $transaksi->pembayaranTerbaru;

        // Jika snap_token belum ada atau sudah kedaluwarsa, generate ulang token (hanya jika masih pending)
        if ($transaksi->status_pembayaran === 'PENDING' && (! $pembayaran || empty($pembayaran->snap_token))) {
            $snapResult = $this->midtransService->createSnapTransaction($transaksi, $user);
            if ($pembayaran) {
                $pembayaran->snap_token = $snapResult['snap_token'];
                $pembayaran->redirect_url = $snapResult['redirect_url'];
                $pembayaran->save();
            }
        }

        $transaksi->load(['pelanggan', 'detailTransaksi', 'pembayaranTerbaru']);

        return Inertia::render('Pelanggan/Pesanan/Bayar', [
            'pesanan' => $transaksi,
            'clientKey' => config('services.midtrans.client_key'),
        ]);
    }

    public function syncStatus(Transaksi $transaksi): RedirectResponse
    {
        $user = Auth::user();

        if ($transaksi->id_pelanggan !== $user->id_pelanggan) {
            abort(403, 'Anda tidak berhak mengakses pesanan ini.');
        }

        $res = $this->midtransService->syncStatusFromMidtrans($transaksi);

        return redirect()->back()->with($res['success'] ? 'success' : 'info', $res['message']);
    }

    public function nota(Transaksi $transaksi): Response
    {
        $user = Auth::user();

        if ($transaksi->id_pelanggan !== $user->id_pelanggan) {
            abort(403, 'Anda tidak berhak mengakses nota pembelian pelanggan lain.');
        }

        $transaksi->load([
            'pelanggan',
            'cabang',
            'detailTransaksi',
            'pembayaranTerbaru',
        ]);

        return Inertia::render('Pelanggan/Pesanan/Nota', [
            'transaksi' => $transaksi,
        ]);
    }
}
