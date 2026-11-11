<?php

namespace Tests\Feature;

use App\Models\Produk;
use App\Models\StokCabang;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PosTest extends TestCase
{
    use DatabaseTransactions;

    protected User $kasir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kasir = User::where('email', 'kasir.utama@myshop.test')->first();
    }

    public function test_kasir_can_render_pos_interface(): void
    {
        $response = $this->actingAs($this->kasir)->get(route('kasir.pos.index'));
        $response->assertStatus(200);
    }

    public function test_pos_transaction_succeeds_and_deducts_branch_stock(): void
    {
        $branchId = $this->kasir->id_cabang;

        // Find an active product with stock in this branch
        $stokCabang = StokCabang::where('id_cabang', $branchId)
            ->where('stok', '>=', 5)
            ->first();

        $this->assertNotNull($stokCabang, 'Stok cabang harus tersedia untuk testing');
        $initialStock = $stokCabang->stok;
        $qtyToBuy = 2;
        $product = Produk::find($stokCabang->id_produk);

        $totalHarga = $product->harga * $qtyToBuy;
        $uangDiterima = $totalHarga + 50000;

        $response = $this->actingAs($this->kasir)
            ->postJson(route('kasir.pos.store'), [
                'items' => [
                    [
                        'id_produk' => $product->id_produk,
                        'jumlah' => $qtyToBuy,
                    ],
                ],
                'metode_pembayaran' => 'TUNAI',
                'uang_diterima' => $uangDiterima,
                'diskon' => 0,
                'catatan' => 'Uji coba transaksi kasir POS otomatis',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $transaksiId = $response->json('data.transaksi.id_transaksi');
        $transaksi = Transaksi::find($transaksiId);

        $this->assertNotNull($transaksi);
        $this->assertEquals('POS', $transaksi->jenis_transaksi);
        $this->assertEquals('BERHASIL', $transaksi->status_pembayaran);
        $this->assertEquals('SELESAI', $transaksi->status_transaksi);
        $this->assertEquals($this->kasir->id_user, $transaksi->id_kasir);
        $this->assertEquals($branchId, $transaksi->id_cabang);

        // Verify branch stock is deducted
        $stokCabang->refresh();
        $this->assertEquals($initialStock - $qtyToBuy, $stokCabang->stok);

        // Verify stock mutation record
        $this->assertDatabaseHas('tbl_mutasi_stok', [
            'id_produk' => $product->id_produk,
            'id_cabang' => $branchId,
            'id_transaksi' => $transaksi->id_transaksi,
            'jenis_mutasi' => 'KELUAR',
            'jumlah' => $qtyToBuy,
        ]);

        // Verify receipt is accessible
        $receiptResponse = $this->actingAs($this->kasir)
            ->get(route('kasir.pos.nota', $transaksi->id_transaksi));
        $receiptResponse->assertStatus(200);
    }

    public function test_pos_fails_when_stock_is_insufficient(): void
    {
        $branchId = $this->kasir->id_cabang;

        $stokCabang = StokCabang::where('id_cabang', $branchId)->first();
        $this->assertNotNull($stokCabang);

        // Request an excessive amount of stock
        $excessiveQty = $stokCabang->stok + 9999;

        $response = $this->actingAs($this->kasir)
            ->post(route('kasir.pos.store'), [
                'items' => [
                    [
                        'id_produk' => $stokCabang->id_produk,
                        'jumlah' => $excessiveQty,
                    ],
                ],
                'metode_pembayaran' => 'TUNAI',
                'uang_diterima' => 99999999,
            ]);

        // Should return back with session error or validation failure
        $response->assertSessionHas('error');
    }

    public function test_pos_fails_when_cash_payment_is_insufficient(): void
    {
        $branchId = $this->kasir->id_cabang;

        $stokCabang = StokCabang::where('id_cabang', $branchId)
            ->where('stok', '>=', 2)
            ->first();

        $product = Produk::find($stokCabang->id_produk);

        $response = $this->actingAs($this->kasir)
            ->post(route('kasir.pos.store'), [
                'items' => [
                    [
                        'id_produk' => $product->id_produk,
                        'jumlah' => 1,
                    ],
                ],
                'metode_pembayaran' => 'TUNAI',
                'uang_diterima' => 100, // Insufficient cash
            ]);

        $response->assertSessionHas('error');
    }
}
