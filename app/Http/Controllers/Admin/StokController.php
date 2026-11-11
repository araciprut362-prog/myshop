<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\Kategori;
use App\Models\MutasiStok;
use App\Models\StokCabang;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class StokController extends Controller
{
    public function __construct(
        protected StockService $stockService
    ) {}

    public function index(Request $request): Response
    {
        $idCabang = $request->filled('cabang') ? (int) $request->cabang : 1;

        $query = StokCabang::with(['produk.kategori', 'cabang'])
            ->where('id_cabang', $idCabang);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('produk', function ($q) use ($search) {
                $q->where('nama_produk', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kategori')) {
            $query->whereHas('produk', function ($q) use ($request) {
                $q->where('id_kategori', $request->kategori);
            });
        }

        if ($request->boolean('menipis')) {
            $query->whereColumn('stok', '<=', 'stok_minimum');
        }

        $stokList = $query->paginate(15)->withQueryString();
        $cabangList = Cabang::all();
        $kategoriList = Kategori::all();

        return Inertia::render('Admin/Stok/Index', [
            'stokList' => $stokList,
            'cabangList' => $cabangList,
            'kategoriList' => $kategoriList,
            'selectedCabang' => $idCabang,
            'filters' => $request->only(['search', 'cabang', 'kategori', 'menipis']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_cabang' => ['required', 'exists:tbl_cabang,id_cabang'],
            'id_produk' => ['required', 'exists:tbl_produk,id_produk'],
            'stok_baru' => ['required', 'integer', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $this->stockService->sesuaikanStok(
            (int) $validated['id_cabang'],
            (int) $validated['id_produk'],
            (int) $validated['stok_baru'],
            (int) Auth::id(),
            $validated['keterangan'] ?? 'Penyesuaian stok manual oleh Admin'
        );

        return redirect()->back()->with('success', 'Stok produk berhasil diperbarui dan mutasi tercatat.');
    }

    public function mutasi(Request $request): Response
    {
        $query = MutasiStok::with(['cabang', 'produk', 'user', 'transaksi']);

        if ($request->filled('cabang')) {
            $query->where('id_cabang', $request->cabang);
        }

        if ($request->filled('jenis')) {
            $query->where('jenis_mutasi', $request->jenis);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('produk', function ($q) use ($search) {
                $q->where('nama_produk', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $mutasi = $query->latest('id_mutasi')->paginate(20)->withQueryString();
        $cabangList = Cabang::all();

        return Inertia::render('Admin/Stok/Mutasi', [
            'mutasi' => $mutasi,
            'cabangList' => $cabangList,
            'filters' => $request->only(['cabang', 'jenis', 'search']),
        ]);
    }
}
