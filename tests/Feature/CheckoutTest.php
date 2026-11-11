<?php

namespace Tests\Feature;

use App\Models\DetailKeranjang;
use App\Models\Keranjang;
use App\Models\Produk;
use App\Models\StokCabang;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use DatabaseTransactions;

    protected User $pelanggan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pelanggan = User::where('email', 'andi@myshop.test')->first();
    }

    public function test_pelanggan_can_view_checkout_page(): void
    {
        $idPelanggan = $this->pelanggan->id_pelanggan;
        $keranjang = Keranjang::firstOrCreate(
            ['id_pelanggan' => $idPelanggan, 'status' => 'AKTIF']
        );

        $produk = Produk::where('status', 'AKTIF')->first();
        DetailKeranjang::firstOrCreate(
            ['id_keranjang' => $keranjang->id_keranjang, 'id_produk' => $produk->id_produk],
            ['jumlah' => 1]
        );

        $response = $this->actingAs($this->pelanggan)->get(route('pelanggan.checkout.index'));
        $response->assertStatus(200);
    }

    public function test_checkout_creates_pending_transaction_with_reserved_stock(): void
    {
        // Find a product with stock available in branch 1 (default online branch)
        $stokCabang = StokCabang::where('id_cabang', 1)->where('stok', '>=', 2)->first();
        $this->assertNotNull($stokCabang, 'Stok cabang default harus tersedia');

        $produk = Produk::where('id_produk', $stokCabang->id_produk)
            ->where('status', 'AKTIF')
            ->first();
        $this->assertNotNull($produk);

        $idPelanggan = $this->pelanggan->id_pelanggan;

        // Setup active cart with 1 item
        $keranjang = Keranjang::where('id_pelanggan', $idPelanggan)
            ->where('status', 'AKTIF')
            ->first();

        if (! $keranjang) {
            $keranjang = Keranjang::create([
                'id_pelanggan' => $idPelanggan,
                'status' => 'AKTIF',
            ]);
        }

        // Clear existing cart items and add test product
        DetailKeranjang::where('id_keranjang', $keranjang->id_keranjang)->delete();
        DetailKeranjang::create([
            'id_keranjang' => $keranjang->id_keranjang,
            'id_produk' => $produk->id_produk,
            'jumlah' => 1,
        ]);

        $initialStock = $stokCabang->stok;

        $response = $this->actingAs($this->pelanggan)
            ->post(route('pelanggan.checkout.store'), [
                'nama_penerima' => 'Andi Santoso',
                'no_hp' => '081234567890',
                'alamat_pengiriman' => 'Jl. Test No. 1, Surabaya',
                'kota' => 'Surabaya',
                'kode_pos' => '60111',
                'catatan' => 'Mohon dikemas dengan aman',
            ]);

        $response->assertStatus(302); // Redirect after success

        // Find the created transaction
        $transaksi = Transaksi::where('id_pelanggan', $idPelanggan)
            ->where('jenis_transaksi', 'ONLINE')
            ->where('status_transaksi', 'MENUNGGU_PEMBAYARAN')
            ->latest('id_transaksi')
            ->first();

        $this->assertNotNull($transaksi, 'Transaksi harus dibuat setelah checkout');
        $this->assertEquals('PENDING', $transaksi->status_pembayaran);
        $this->assertEquals('DIRESERVASI', $transaksi->status_stok);
        $this->assertEquals($idPelanggan, $transaksi->id_pelanggan);

        // Verify stock was reserved (reduced from physical stock)
        $stokCabang->refresh();
        $this->assertEquals($initialStock - 1, $stokCabang->stok);

        // Verify stock mutation record (RESERVASI)
        $this->assertDatabaseHas('tbl_mutasi_stok', [
            'id_produk' => $produk->id_produk,
            'id_cabang' => 1,
            'id_transaksi' => $transaksi->id_transaksi,
            'jenis_mutasi' => 'RESERVASI',
            'jumlah' => 1,
        ]);

        // Verify payment record was created
        $this->assertDatabaseHas('tbl_pembayaran', [
            'id_transaksi' => $transaksi->id_transaksi,
            'status' => 'PENDING',
        ]);
    }
}
