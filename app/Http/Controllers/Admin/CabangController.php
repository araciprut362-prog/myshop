<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\LogAktivitas;
use App\Models\Produk;
use App\Models\StokCabang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CabangController extends Controller
{
    public function index(): Response
    {
        $penjualanCabang = DB::table('view_laporan_penjualan_cabang')->get()->keyBy('id_cabang');

        $cabang = Cabang::all()->map(function ($c) use ($penjualanCabang) {
            $laporan = $penjualanCabang->get($c->id_cabang);
            $c->jumlah_transaksi = $laporan ? $laporan->jumlah_transaksi : 0;
            $c->total_penjualan = $laporan ? $laporan->total_penjualan : 0;

            return $c;
        });

        return Inertia::render('Admin/Cabang/Index', [
            'cabang' => $cabang,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode_cabang' => ['required', 'string', 'max:15', 'unique:tbl_cabang,kode_cabang'],
            'nama_cabang' => ['required', 'string', 'max:100'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'kota' => ['required', 'string', 'max:80'],
            'telepon' => ['nullable', 'string', 'max:25'],
            'status' => ['required', 'in:AKTIF,NONAKTIF'],
        ]);

        $cabang = DB::transaction(function () use ($validated) {
            $cabang = Cabang::create([
                'kode_cabang' => strtoupper($validated['kode_cabang']),
                'nama_cabang' => $validated['nama_cabang'],
                'alamat' => $validated['alamat'] ?? null,
                'kota' => $validated['kota'],
                'telepon' => $validated['telepon'] ?? null,
                'status' => $validated['status'],
                'created_at' => now(),
            ]);

            // Inisialisasi stok 0 untuk seluruh produk yang sudah ada di sistem
            $produkList = Produk::all();
            foreach ($produkList as $produk) {
                StokCabang::firstOrCreate([
                    'id_cabang' => $cabang->id_cabang,
                    'id_produk' => $produk->id_produk,
                ], [
                    'stok' => 0,
                    'stok_minimum' => 5,
                ]);
            }

            return $cabang;
        });

        LogAktivitas::catat(
            Auth::id(),
            'Tambah cabang baru',
            'tbl_cabang',
            $cabang->id_cabang,
            "Kode: {$cabang->kode_cabang}, Nama: {$cabang->nama_cabang}, Kota: {$cabang->kota}"
        );

        return redirect()->route('admin.cabang.index')->with('success', 'Cabang baru berhasil ditambahkan.');
    }

    public function update(Request $request, Cabang $cabang): RedirectResponse
    {
        $validated = $request->validate([
            'kode_cabang' => ['required', 'string', 'max:15', 'unique:tbl_cabang,kode_cabang,'.$cabang->id_cabang.',id_cabang'],
            'nama_cabang' => ['required', 'string', 'max:100'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'kota' => ['required', 'string', 'max:80'],
            'telepon' => ['nullable', 'string', 'max:25'],
            'status' => ['required', 'in:AKTIF,NONAKTIF'],
        ]);

        $validated['kode_cabang'] = strtoupper($validated['kode_cabang']);
        $cabang->update($validated);

        LogAktivitas::catat(
            Auth::id(),
            'Update data cabang',
            'tbl_cabang',
            $cabang->id_cabang,
            "Nama: {$cabang->nama_cabang}"
        );

        return redirect()->route('admin.cabang.index')->with('success', 'Data cabang berhasil diperbarui.');
    }

    public function toggleStatus(Cabang $cabang): RedirectResponse
    {
        $newStatus = $cabang->status === 'AKTIF' ? 'NONAKTIF' : 'AKTIF';
        $cabang->status = $newStatus;
        $cabang->save();

        LogAktivitas::catat(
            Auth::id(),
            "Ubah status cabang menjadi {$newStatus}",
            'tbl_cabang',
            $cabang->id_cabang,
            "Cabang: {$cabang->nama_cabang}"
        );

        return redirect()->back()->with('success', "Status cabang berhasil diubah menjadi {$newStatus}.");
    }
}
