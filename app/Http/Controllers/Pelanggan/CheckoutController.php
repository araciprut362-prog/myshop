<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Keranjang;
use App\Models\Pelanggan;
use App\Models\Pengaturan;
use App\Services\CheckoutService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService
    ) {}

    public function index(): Response|RedirectResponse
    {
        $user = Auth::user();
        $pelanggan = Pelanggan::find($user->id_pelanggan);

        $keranjang = Keranjang::where('id_pelanggan', $user->id_pelanggan)
            ->where('status', 'AKTIF')
            ->with('details.produk')
            ->first();

        if (! $keranjang || $keranjang->details->isEmpty()) {
            return redirect()->route('pelanggan.keranjang.index')
                ->with('error', 'Keranjang belanja Anda masih kosong.');
        }

        $items = $keranjang->details->map(function ($detail) {
            return [
                'id_produk' => $detail->id_produk,
                'nama_produk' => $detail->produk->nama_produk,
                'sku' => $detail->produk->sku,
                'harga' => $detail->produk->harga,
                'jumlah' => $detail->jumlah,
                'subtotal' => $detail->jumlah * $detail->produk->harga,
            ];
        });

        $subtotal = $items->sum('subtotal');
        $pajakPersen = (float) Pengaturan::get('pajak_simulasi_persen', '11');
        $pajak = round(($subtotal * $pajakPersen) / 100, 2);
        $ongkir = 15000.00;
        $total = $subtotal + $pajak + $ongkir;

        return Inertia::render('Pelanggan/Checkout', [
            'pelanggan' => $pelanggan,
            'items' => $items,
            'ringkasan' => [
                'subtotal' => $subtotal,
                'pajak' => $pajak,
                'pajak_persen' => $pajakPersen,
                'ongkir' => $ongkir,
                'total' => $total,
            ],
            'clientKey' => config('services.midtrans.client_key'),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'nama_penerima' => ['required', 'string', 'max:100'],
            'no_hp' => ['required', 'string', 'max:25'],
            'alamat_pengiriman' => ['required', 'string', 'max:255'],
            'kota' => ['required', 'string', 'max:50'],
            'kode_pos' => ['nullable', 'string', 'max:10'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $hasil = $this->checkoutService->prosesCheckout($user, $validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pesanan berhasil dibuat.',
                    'snap_token' => $hasil['snap_token'],
                    'redirect_url' => $hasil['redirect_url'],
                    'id_transaksi' => $hasil['transaksi']->id_transaksi,
                    'kode_transaksi' => $hasil['transaksi']->kode_transaksi,
                ]);
            }

            return redirect()->route('pelanggan.pesanan.bayar', $hasil['transaksi']->id_transaksi)
                ->with('success', 'Pesanan Anda berhasil dibuat! Silakan selesaikan pembayaran.');
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
