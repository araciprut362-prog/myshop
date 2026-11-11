<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Pengaturan;
use App\Models\Produk;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function home(): Response
    {
        $idCabangDefault = (int) Pengaturan::get('cabang_online_default', '1');

        $kategoriList = Kategori::where('status', 'AKTIF')
            ->withCount(['produk' => fn ($q) => $q->where('status', 'AKTIF')])
            ->get();

        $produkTerbaru = Produk::where('status', 'AKTIF')
            ->with(['kategori', 'stokCabang' => fn ($q) => $q->where('id_cabang', $idCabangDefault)])
            ->latest('id_produk')
            ->limit(8)
            ->get()
            ->map(function ($p) {
                $p->stok = (int) $p->stokCabang->first()?->stok;

                return $p;
            });

        $produkunggulan = Produk::where('status', 'AKTIF')
            ->with(['kategori', 'stokCabang' => fn ($q) => $q->where('id_cabang', $idCabangDefault)])
            ->inRandomOrder()
            ->limit(4)
            ->get()
            ->map(function ($p) {
                $p->stok = (int) $p->stokCabang->first()?->stok;

                return $p;
            });

        return Inertia::render('Store/Home', [
            'kategoriList' => $kategoriList,
            'produkTerbaru' => $produkTerbaru,
            'produkUnggulan' => $produkunggulan,
        ]);
    }

    public function katalog(Request $request): Response
    {
        $idCabangDefault = (int) Pengaturan::get('cabang_online_default', '1');

        $query = Produk::where('status', 'AKTIF')
            ->with(['kategori', 'stokCabang' => fn ($q) => $q->where('id_cabang', $idCabangDefault)]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_produk', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kategori')) {
            $query->where('id_kategori', $request->kategori);
        }

        // Urutkan
        $sort = $request->get('sort', 'terbaru');
        match ($sort) {
            'harga_terendah' => $query->orderBy('harga', 'asc'),
            'harga_tertinggi' => $query->orderBy('harga', 'desc'),
            'nama_az' => $query->orderBy('nama_produk', 'asc'),
            'nama_za' => $query->orderBy('nama_produk', 'desc'),
            default => $query->latest('id_produk'),
        };

        $produk = $query->paginate(12)->withQueryString();

        // Attach stok
        $produk->getCollection()->transform(function ($p) {
            $p->stok = (int) $p->stokCabang->first()?->stok;

            return $p;
        });

        $kategoriList = Kategori::where('status', 'AKTIF')->get();

        return Inertia::render('Store/Katalog', [
            'produk' => $produk,
            'kategoriList' => $kategoriList,
            'filters' => $request->only(['search', 'kategori', 'sort']),
        ]);
    }

    public function detail(string $slug): Response
    {
        $idCabangDefault = (int) Pengaturan::get('cabang_online_default', '1');

        $produk = Produk::where('slug', $slug)
            ->where('status', 'AKTIF')
            ->with(['kategori', 'stokCabang' => fn ($q) => $q->where('id_cabang', $idCabangDefault)])
            ->firstOrFail();

        $produk->stok = (int) $produk->stokCabang->first()?->stok;

        $produkTerkait = Produk::where('id_kategori', $produk->id_kategori)
            ->where('id_produk', '<>', $produk->id_produk)
            ->where('status', 'AKTIF')
            ->with(['kategori', 'stokCabang' => fn ($q) => $q->where('id_cabang', $idCabangDefault)])
            ->limit(4)
            ->get()
            ->map(function ($p) {
                $p->stok = (int) $p->stokCabang->first()?->stok;

                return $p;
            });

        return Inertia::render('Store/Detail', [
            'produk' => $produk,
            'produkTerkait' => $produkTerkait,
        ]);
    }
}
