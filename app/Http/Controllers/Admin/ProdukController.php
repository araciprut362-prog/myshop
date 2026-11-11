<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\DetailTransaksi;
use App\Models\Kategori;
use App\Models\LogAktivitas;
use App\Models\Produk;
use App\Models\StokCabang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProdukController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Produk::with(['kategori', 'stokCabang.cabang']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_produk', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kategori')) {
            $query->where('id_kategori', $request->kategori);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $produk = $query->latest('id_produk')->paginate(10)->withQueryString();
        $kategori = Kategori::where('status', 'AKTIF')->get();
        $cabang = Cabang::where('status', 'AKTIF')->get();

        return Inertia::render('Admin/Produk/Index', [
            'produk' => $produk,
            'kategori' => $kategori,
            'cabang' => $cabang,
            'filters' => $request->only(['search', 'kategori', 'status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_kategori' => ['required', 'exists:tbl_kategori,id_kategori'],
            'sku' => ['required', 'string', 'max:30', 'unique:tbl_produk,sku'],
            'nama_produk' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string'],
            'harga' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:AKTIF,NONAKTIF'],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $gambarPath = null;
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $filename = time().'_'.Str::slug($validated['nama_produk']).'.'.$file->getClientOriginalExtension();
            $file->move(public_path('uploads/produk'), $filename);
            $gambarPath = 'uploads/produk/'.$filename;
        }

        DB::transaction(function () use ($validated, $gambarPath) {
            $slug = Str::slug($validated['nama_produk']);
            $count = Produk::where('slug', 'like', "{$slug}%")->count();
            if ($count > 0) {
                $slug .= '-'.($count + 1);
            }

            // after_insert_produk trigger will automatically log into tbl_log_aktivitas
            $produk = Produk::create([
                'id_kategori' => $validated['id_kategori'],
                'sku' => strtoupper($validated['sku']),
                'nama_produk' => $validated['nama_produk'],
                'slug' => $slug,
                'deskripsi' => $validated['deskripsi'] ?? null,
                'harga' => $validated['harga'],
                'gambar' => $gambarPath,
                'status' => $validated['status'],
            ]);

            // Inisialisasi stok 0 untuk semua cabang aktif
            $cabangList = Cabang::all();
            foreach ($cabangList as $cabang) {
                StokCabang::firstOrCreate([
                    'id_cabang' => $cabang->id_cabang,
                    'id_produk' => $produk->id_produk,
                ], [
                    'stok' => 0,
                    'stok_minimum' => 5,
                ]);
            }
        });

        return redirect()->route('admin.produk.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, Produk $produk): RedirectResponse
    {
        $validated = $request->validate([
            'id_kategori' => ['required', 'exists:tbl_kategori,id_kategori'],
            'sku' => ['required', 'string', 'max:30', 'unique:tbl_produk,sku,'.$produk->id_produk.',id_produk'],
            'nama_produk' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string'],
            'harga' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:AKTIF,NONAKTIF'],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $filename = time().'_'.Str::slug($validated['nama_produk']).'.'.$file->getClientOriginalExtension();
            $file->move(public_path('uploads/produk'), $filename);
            $validated['gambar'] = 'uploads/produk/'.$filename;
        }

        $validated['sku'] = strtoupper($validated['sku']);
        $produk->update($validated);

        LogAktivitas::catat(
            Auth::id(),
            'Update produk',
            'tbl_produk',
            $produk->id_produk,
            "SKU: {$produk->sku}, Nama: {$produk->nama_produk}"
        );

        return redirect()->route('admin.produk.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function toggleStatus(Produk $produk): RedirectResponse
    {
        $newStatus = $produk->status === 'AKTIF' ? 'NONAKTIF' : 'AKTIF';
        $produk->status = $newStatus;
        $produk->save();

        LogAktivitas::catat(
            Auth::id(),
            "Ubah status produk menjadi {$newStatus}",
            'tbl_produk',
            $produk->id_produk,
            "Produk: {$produk->nama_produk}"
        );

        return redirect()->back()->with('success', "Status produk berhasil diubah menjadi {$newStatus}.");
    }

    public function destroy(Produk $produk): RedirectResponse
    {
        // Cegah penghapusan jika sudah pernah digunakan dalam transaksi
        $hasTransactions = DetailTransaksi::where('id_produk', $produk->id_produk)->exists();

        if ($hasTransactions) {
            // Ubah menjadi NONAKTIF alih-alih menghapus
            $produk->status = 'NONAKTIF';
            $produk->save();

            return redirect()->back()->with('warning', 'Produk telah digunakan dalam transaksi riwayat dan tidak dapat dihapus permanen. Status produk telah dialihkan menjadi NONAKTIF.');
        }

        $produk->stokCabang()->delete();
        $produk->delete();

        LogAktivitas::catat(
            Auth::id(),
            'Hapus produk',
            'tbl_produk',
            $produk->id_produk,
            "Produk {$produk->nama_produk} berhasil dihapus permanen."
        );

        return redirect()->route('admin.produk.index')->with('success', 'Produk berhasil dihapus.');
    }
}
