<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\LogAktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class KategoriController extends Controller
{
    public function index(): Response
    {
        $kategori = Kategori::withCount('produk')->get();

        return Inertia::render('Admin/Kategori/Index', [
            'kategori' => $kategori,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_kategori' => ['required', 'string', 'max:100', 'unique:tbl_kategori,nama_kategori'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['required', 'in:AKTIF,NONAKTIF'],
        ]);

        $kategori = Kategori::create([
            'nama_kategori' => $validated['nama_kategori'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'status' => $validated['status'],
            'created_at' => now(),
        ]);

        LogAktivitas::catat(
            Auth::id(),
            'Tambah kategori baru',
            'tbl_kategori',
            $kategori->id_kategori,
            "Nama Kategori: {$kategori->nama_kategori}"
        );

        return redirect()->route('admin.kategori.index')->with('success', 'Kategori baru berhasil ditambahkan.');
    }

    public function update(Request $request, Kategori $kategori): RedirectResponse
    {
        $validated = $request->validate([
            'nama_kategori' => ['required', 'string', 'max:100', 'unique:tbl_kategori,nama_kategori,'.$kategori->id_kategori.',id_kategori'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['required', 'in:AKTIF,NONAKTIF'],
        ]);

        $kategori->update($validated);

        LogAktivitas::catat(
            Auth::id(),
            'Update kategori',
            'tbl_kategori',
            $kategori->id_kategori,
            "Nama Kategori: {$kategori->nama_kategori}"
        );

        return redirect()->route('admin.kategori.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function toggleStatus(Kategori $kategori): RedirectResponse
    {
        $newStatus = $kategori->status === 'AKTIF' ? 'NONAKTIF' : 'AKTIF';
        $kategori->status = $newStatus;
        $kategori->save();

        LogAktivitas::catat(
            Auth::id(),
            "Ubah status kategori menjadi {$newStatus}",
            'tbl_kategori',
            $kategori->id_kategori,
            "Kategori: {$kategori->nama_kategori}"
        );

        return redirect()->back()->with('success', "Status kategori berhasil diubah menjadi {$newStatus}.");
    }

    public function destroy(Kategori $kategori): RedirectResponse
    {
        if ($kategori->produk()->exists()) {
            return redirect()->back()->with('error', 'Kategori ini tidak dapat dihapus karena masih memiliki produk terkait.');
        }

        $kategori->delete();

        LogAktivitas::catat(
            Auth::id(),
            'Hapus kategori',
            'tbl_kategori',
            $kategori->id_kategori,
            "Kategori {$kategori->nama_kategori} dihapus."
        );

        return redirect()->route('admin.kategori.index')->with('success', 'Kategori berhasil dihapus.');
    }
}
