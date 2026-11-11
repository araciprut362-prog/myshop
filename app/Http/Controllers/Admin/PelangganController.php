<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\Pelanggan;
use App\Models\Transaksi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PelangganController extends Controller
{
    public function index(Request $request): Response
    {
        $query = DB::table('view_ringkasan_pelanggan');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_pelanggan', 'like', "%{$search}%")
                    ->orWhere('kota', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kota')) {
            $query->where('kota', $request->kota);
        }

        // Ambil data pelanggan dengan kategori belanja dari Stored Function KategoriBelanja()
        $pelangganRaw = $query->orderBy('id_pelanggan')->paginate(10)->withQueryString();

        // Tambahkan kategori belanja dari function KategoriBelanja()
        $pelangganItems = collect($pelangganRaw->items())->map(function ($p) {
            $kat = DB::selectOne('SELECT KategoriBelanja(?) as kategori', [$p->total_belanja ?? 0]);
            $p->kategori_belanja = $kat ? $kat->kategori : 'Pelanggan Reguler';

            return $p;
        });

        // Set items kembali ke paginator
        $pelanggan = new LengthAwarePaginator(
            $pelangganItems,
            $pelangganRaw->total(),
            $pelangganRaw->perPage(),
            $pelangganRaw->currentPage(),
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $daftarKota = Pelanggan::whereNotNull('kota')->distinct()->pluck('kota');

        return Inertia::render('Admin/Pelanggan/Index', [
            'pelanggan' => $pelanggan,
            'daftarKota' => $daftarKota,
            'filters' => $request->only(['search', 'kota']),
        ]);
    }

    public function show(int $id): Response
    {
        $pelanggan = Pelanggan::with('user')->findOrFail($id);

        $kat = DB::selectOne('SELECT KategoriBelanja(?) as kategori', [
            Transaksi::where('id_pelanggan', $id)
                ->where('status_pembayaran', 'BERHASIL')
                ->where('status_transaksi', '<>', 'DIBATALKAN')
                ->sum('total_belanja'),
        ]);
        $kategoriBelanja = $kat ? $kat->kategori : 'Pelanggan Reguler';

        $riwayatTransaksi = Transaksi::where('id_pelanggan', $id)
            ->with(['cabang', 'kasir'])
            ->latest('id_transaksi')
            ->paginate(10);

        return Inertia::render('Admin/Pelanggan/Show', [
            'pelanggan' => $pelanggan,
            'kategoriBelanja' => $kategoriBelanja,
            'riwayatTransaksi' => $riwayatTransaksi,
        ]);
    }

    public function update(Request $request, Pelanggan $pelanggan): RedirectResponse
    {
        $validated = $request->validate([
            'nama_pelanggan' => ['required', 'string', 'max:100'],
            'kota' => ['nullable', 'string', 'max:50'],
            'no_hp' => ['nullable', 'string', 'max:25'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'kode_pos' => ['nullable', 'string', 'max:10'],
        ]);

        // after_update_pelanggan trigger otomatis mencatat ke log_perubahan_pelanggan jika nama berubah!
        $pelanggan->update($validated);

        // Update juga di tbl_user jika terhubung
        if ($pelanggan->user) {
            $pelanggan->user->nama_lengkap = $validated['nama_pelanggan'];
            $pelanggan->user->no_hp = $validated['no_hp'];
            $pelanggan->user->save();
        }

        LogAktivitas::catat(
            Auth::id(),
            'Update profil pelanggan',
            'tbl_pelanggan',
            $pelanggan->id_pelanggan,
            "Pelanggan: {$pelanggan->nama_pelanggan}"
        );

        return redirect()->back()->with('success', 'Data pelanggan berhasil diperbarui.');
    }
}
