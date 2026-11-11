<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Pelanggan;
use App\Models\Pengaturan;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Services\PosService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    public function __construct(
        protected PosService $posService
    ) {}

    public function index(): Response
    {
        $user = Auth::user();
        $idCabang = $user->id_cabang;

        if (! $idCabang) {
            abort(403, 'Akun kasir tidak terhubung ke cabang toko.');
        }

        // Ambil produk aktif beserta stok khusus di cabang kasir
        $produkList = Produk::where('status', 'AKTIF')
            ->with(['kategori', 'stokCabang' => function ($q) use ($idCabang) {
                $q->where('id_cabang', $idCabang);
            }])
            ->get()
            ->map(function ($p) {
                $stokEntry = $p->stokCabang->first();
                $p->stok = $stokEntry ? $stokEntry->stok : 0;
                $p->stok_minimum = $stokEntry ? $stokEntry->stok_minimum : 5;

                return $p;
            });

        $kategoriList = Kategori::where('status', 'AKTIF')->get();
        $pelangganList = Pelanggan::select('id_pelanggan', 'nama_pelanggan', 'no_hp', 'kota')
            ->orderBy('nama_pelanggan')
            ->get();

        $pajakPersen = (float) Pengaturan::get('pajak_simulasi_persen', '11');

        return Inertia::render('Kasir/Pos', [
            'cabang' => $user->cabang,
            'produkList' => $produkList,
            'kategoriList' => $kategoriList,
            'pelangganList' => $pelangganList,
            'pajakPersen' => $pajakPersen,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_produk' => ['required', 'exists:tbl_produk,id_produk'],
            'items.*.jumlah' => ['required', 'integer', 'min:1'],
            'metode_pembayaran' => ['required', 'in:TUNAI,TRANSFER_BANK,QRIS'],
            'id_pelanggan' => ['nullable', 'exists:tbl_pelanggan,id_pelanggan'],
            'uang_diterima' => ['nullable', 'numeric', 'min:0'],
            'diskon' => ['nullable', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $hasil = $this->posService->prosesTransaksiPos(
                $user,
                $validated['items'],
                [
                    'id_pelanggan' => $validated['id_pelanggan'] ?? null,
                    'metode_pembayaran' => $validated['metode_pembayaran'],
                    'uang_diterima' => $validated['uang_diterima'] ?? null,
                    'diskon' => $validated['diskon'] ?? 0,
                    'catatan' => $validated['catatan'] ?? null,
                ]
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transaksi POS berhasil diselesaikan!',
                    'data' => $hasil,
                ]);
            }

            return redirect()->route('kasir.pos.nota', $hasil['transaksi']->id_transaksi)
                ->with('success', 'Transaksi kasir berhasil diselesaikan!');
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

    public function riwayat(Request $request): Response
    {
        $user = Auth::user();

        $query = Transaksi::where('id_kasir', $user->id_user)
            ->with(['pelanggan', 'pembayaranTerbaru', 'detailTransaksi.produk']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('kode_transaksi', 'like', "%{$search}%");
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }

        $transaksi = $query->latest('id_transaksi')->paginate(12)->withQueryString();

        return Inertia::render('Kasir/Riwayat', [
            'transaksi' => $transaksi,
            'filters' => $request->only(['search', 'tanggal']),
        ]);
    }

    public function nota(Transaksi $transaksi): Response
    {
        $user = Auth::user();

        // Kasir hanya boleh mencetak nota dari cabangnya sendiri
        if ($transaksi->id_cabang !== $user->id_cabang) {
            abort(403, 'Anda tidak memiliki akses ke nota transaksi cabang lain.');
        }

        $transaksi->load([
            'pelanggan',
            'kasir',
            'cabang',
            'detailTransaksi',
            'pembayaranTerbaru',
        ]);

        return Inertia::render('Kasir/Nota', [
            'transaksi' => $transaksi,
        ]);
    }
}
