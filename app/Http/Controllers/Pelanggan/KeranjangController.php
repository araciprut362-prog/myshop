<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\DetailKeranjang;
use App\Models\Keranjang;
use App\Models\Pengaturan;
use App\Models\Produk;
use App\Models\StokCabang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class KeranjangController extends Controller
{
    protected function getOrCreateKeranjang(): Keranjang
    {
        $user = Auth::user();
        $idPelanggan = $user->id_pelanggan;

        $keranjang = Keranjang::where('id_pelanggan', $idPelanggan)
            ->where('status', 'AKTIF')
            ->first();

        if (! $keranjang) {
            $keranjang = Keranjang::create([
                'id_pelanggan' => $idPelanggan,
                'id_cabang' => 1,
                'status' => 'AKTIF',
            ]);
        }

        return $keranjang;
    }

    public function index(): Response
    {
        $keranjang = $this->getOrCreateKeranjang();
        $keranjang->load(['details.produk.kategori']);

        $idCabangDefault = (int) Pengaturan::get('cabang_online_default', '1');

        $items = $keranjang->details->map(function ($detail) use ($idCabangDefault) {
            $stok = StokCabang::where('id_cabang', $idCabangDefault)
                ->where('id_produk', $detail->id_produk)
                ->value('stok') ?? 0;

            return [
                'id_detail_keranjang' => $detail->id_detail_keranjang,
                'id_produk' => $detail->id_produk,
                'jumlah' => $detail->jumlah,
                'produk' => $detail->produk,
                'stok_tersedia' => $stok,
                'subtotal' => $detail->jumlah * $detail->produk->harga,
            ];
        });

        $subtotal = $items->sum('subtotal');
        $pajakPersen = (float) Pengaturan::get('pajak_simulasi_persen', '11');
        $pajak = round(($subtotal * $pajakPersen) / 100, 2);
        $ongkir = $items->isNotEmpty() ? 15000.00 : 0.00;
        $total = $subtotal + $pajak + $ongkir;

        return Inertia::render('Pelanggan/Keranjang', [
            'items' => $items,
            'ringkasan' => [
                'subtotal' => $subtotal,
                'pajak' => $pajak,
                'pajak_persen' => $pajakPersen,
                'ongkir' => $ongkir,
                'total' => $total,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_produk' => ['required', 'exists:tbl_produk,id_produk'],
            'jumlah' => ['nullable', 'integer', 'min:1'],
        ]);

        $idCabangDefault = (int) Pengaturan::get('cabang_online_default', '1');
        $produk = Produk::where('id_produk', $validated['id_produk'])
            ->where('status', 'AKTIF')
            ->firstOrFail();

        $stokTersedia = (int) StokCabang::where('id_cabang', $idCabangDefault)
            ->where('id_produk', $produk->id_produk)
            ->value('stok');

        if ($stokTersedia <= 0) {
            return redirect()->back()->with('error', "Stok untuk {$produk->nama_produk} sedang habis.");
        }

        $keranjang = $this->getOrCreateKeranjang();
        $detail = DetailKeranjang::where('id_keranjang', $keranjang->id_keranjang)
            ->where('id_produk', $produk->id_produk)
            ->first();

        $jumlahTambah = (int) ($validated['jumlah'] ?? 1);

        if ($detail) {
            $totalMinta = $detail->jumlah + $jumlahTambah;
            if ($totalMinta > $stokTersedia) {
                return redirect()->back()->with('error', "Tidak dapat menambah. Stok tersisa hanya {$stokTersedia}.");
            }
            $detail->jumlah = $totalMinta;
            $detail->save();
        } else {
            if ($jumlahTambah > $stokTersedia) {
                return redirect()->back()->with('error', "Stok tersisa hanya {$stokTersedia}.");
            }
            DetailKeranjang::create([
                'id_keranjang' => $keranjang->id_keranjang,
                'id_produk' => $produk->id_produk,
                'jumlah' => $jumlahTambah,
            ]);
        }

        return redirect()->back()->with('success', "{$produk->nama_produk} berhasil dimasukkan ke keranjang.");
    }

    public function update(Request $request, int $idDetail): RedirectResponse
    {
        $validated = $request->validate([
            'jumlah' => ['required', 'integer', 'min:1'],
        ]);

        $keranjang = $this->getOrCreateKeranjang();
        $detail = DetailKeranjang::where('id_keranjang', $keranjang->id_keranjang)
            ->where('id_detail_keranjang', $idDetail)
            ->firstOrFail();

        $idCabangDefault = (int) Pengaturan::get('cabang_online_default', '1');
        $stokTersedia = (int) StokCabang::where('id_cabang', $idCabangDefault)
            ->where('id_produk', $detail->id_produk)
            ->value('stok');

        if ($validated['jumlah'] > $stokTersedia) {
            return redirect()->back()->with('error', "Jumlah melebihi stok yang tersedia ({$stokTersedia}).");
        }

        $detail->jumlah = $validated['jumlah'];
        $detail->save();

        return redirect()->back()->with('success', 'Jumlah produk di keranjang diperbarui.');
    }

    public function destroy(int $idDetail): RedirectResponse
    {
        $keranjang = $this->getOrCreateKeranjang();
        DetailKeranjang::where('id_keranjang', $keranjang->id_keranjang)
            ->where('id_detail_keranjang', $idDetail)
            ->delete();

        return redirect()->back()->with('success', 'Produk dihapus dari keranjang.');
    }
}
