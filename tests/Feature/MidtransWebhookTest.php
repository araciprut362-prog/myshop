<?php

namespace Tests\Feature;

use App\Models\DetailTransaksi;
use App\Models\Pembayaran;
use App\Models\Produk;
use App\Models\StokCabang;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MidtransWebhookTest extends TestCase
{
    use DatabaseTransactions;

    protected User $pelanggan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pelanggan = User::where('email', 'andi@myshop.test')->first();
    }

    /**
     * Create a pending ONLINE transaction with stock reserved for testing.
     */
    protected function createPendingOnlineTransaction(int $qty = 1): array
    {
        $stokCabang = StokCabang::where('id_cabang', 1)->where('stok', '>=', $qty + 1)->first();
        $this->assertNotNull($stokCabang, 'Stok cabang default harus tersedia');

        $produk = Produk::where('id_produk', $stokCabang->id_produk)
            ->where('status', 'AKTIF')
            ->first();
        $this->assertNotNull($produk);

        $idPelanggan = $this->pelanggan->id_pelanggan;
        $hargaSatuan = $produk->harga;
        $subtotal = $hargaSatuan * $qty;
        $pajak = round($subtotal * 0.11, 2);
        $ongkir = 15000.0;
        $totalBelanja = round($subtotal + $pajak + $ongkir, 2);

        $kodeTransaksi = 'WEB-TEST-'.now()->format('Ymd').'-'.mt_rand(1000, 9999);

        // Reserve stock first
        $stockService = app(StockService::class);

        $transaksi = Transaksi::create([
            'kode_transaksi' => $kodeTransaksi,
            'tanggal' => now()->toDateString(),
            'waktu_transaksi' => now(),
            'id_pelanggan' => $idPelanggan,
            'id_kasir' => null,
            'id_cabang' => 1,
            'jenis_transaksi' => 'ONLINE',
            'status_transaksi' => 'MENUNGGU_PEMBAYARAN',
            'status_pembayaran' => 'PENDING',
            'status_stok' => 'DIRESERVASI',
            'subtotal' => $subtotal,
            'diskon' => 0,
            'pajak' => $pajak,
            'ongkir' => $ongkir,
            'total_belanja' => $totalBelanja,
            'alamat_pengiriman' => 'Jl. Test No. 1',
            'catatan' => 'Webhook test',
        ]);

        DetailTransaksi::create([
            'id_transaksi' => $transaksi->id_transaksi,
            'id_produk' => $produk->id_produk,
            'nama_produk_snapshot' => $produk->nama_produk,
            'sku_snapshot' => $produk->sku,
            'harga_satuan' => $hargaSatuan,
            'jumlah' => $qty,
            'diskon_item' => 0,
            'subtotal' => $subtotal,
            'created_at' => now(),
        ]);

        $stockService->reservasiStokOnline(1, $produk->id_produk, $qty, $transaksi, $this->pelanggan->id_user);

        Pembayaran::create([
            'id_transaksi' => $transaksi->id_transaksi,
            'metode_pembayaran' => 'MIDTRANS',
            'jumlah_bayar' => $totalBelanja,
            'status' => 'PENDING',
            'snap_token' => 'fake-snap-token-'.$kodeTransaksi,
        ]);

        return [
            'transaksi' => $transaksi,
            'produk' => $produk,
            'stok_cabang' => $stokCabang,
            'total_belanja' => $totalBelanja,
        ];
    }

    protected function buildPayload(string $kodeTransaksi, float $grossAmount, string $transactionStatus, string $fraudStatus = 'accept'): array
    {
        // Use dummy server key for test (signature is bypassed when key is demo key)
        $serverKey = config('services.midtrans.server_key', 'SB-Mid-server-demo-key');
        $statusCode = '200';
        $signatureKey = hash('sha512', $kodeTransaksi.$statusCode.number_format($grossAmount, 2, '.', '').$serverKey);

        return [
            'order_id' => $kodeTransaksi,
            'status_code' => $statusCode,
            'gross_amount' => number_format($grossAmount, 2, '.', ''),
            'transaction_status' => $transactionStatus,
            'fraud_status' => $fraudStatus,
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'MT-'.mt_rand(100000, 999999),
            'signature_key' => $signatureKey,
        ];
    }

    public function test_webhook_settlement_marks_transaction_berhasil_and_confirms_stock(): void
    {
        $data = $this->createPendingOnlineTransaction(1);
        /** @var Transaksi $transaksi */
        $transaksi = $data['transaksi'];
        $stokCabang = $data['stok_cabang'];

        $stockAfterReservation = $stokCabang->fresh()->stok;

        $payload = $this->buildPayload(
            $transaksi->kode_transaksi,
            (float) $data['total_belanja'],
            'settlement'
        );

        $response = $this->postJson('/api/payment/midtrans/notification', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Status transaksi berubah
        $transaksi->refresh();
        $this->assertEquals('BERHASIL', $transaksi->status_pembayaran);
        $this->assertEquals('SELESAI', $transaksi->status_transaksi);
        $this->assertEquals('DIKURANGI', $transaksi->status_stok);

        // Stock stays same (already reserved = already reduced)
        $stokCabang->refresh();
        $this->assertEquals($stockAfterReservation, $stokCabang->stok);

        // Pembayaran updated
        $this->assertDatabaseHas('tbl_pembayaran', [
            'id_transaksi' => $transaksi->id_transaksi,
            'status' => 'BERHASIL',
        ]);
    }

    public function test_webhook_expire_cancels_transaction_and_restores_stock(): void
    {
        $data = $this->createPendingOnlineTransaction(1);
        /** @var Transaksi $transaksi */
        $transaksi = $data['transaksi'];
        $stokCabang = $data['stok_cabang'];

        $stockAfterReservation = $stokCabang->fresh()->stok;

        $payload = $this->buildPayload(
            $transaksi->kode_transaksi,
            (float) $data['total_belanja'],
            'expire'
        );

        $response = $this->postJson('/api/payment/midtrans/notification', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $transaksi->refresh();
        $this->assertEquals('KEDALUWARSA', $transaksi->status_pembayaran);
        $this->assertEquals('DIBATALKAN', $transaksi->status_transaksi);
        $this->assertEquals('DIKEMBALIKAN', $transaksi->status_stok);

        // Stock restored
        $stokCabang->refresh();
        $this->assertEquals($stockAfterReservation + 1, $stokCabang->stok);
    }

    public function test_webhook_cancel_cancels_transaction_and_restores_stock(): void
    {
        $data = $this->createPendingOnlineTransaction(1);
        /** @var Transaksi $transaksi */
        $transaksi = $data['transaksi'];
        $stokCabang = $data['stok_cabang'];

        $stockAfterReservation = $stokCabang->fresh()->stok;

        $payload = $this->buildPayload(
            $transaksi->kode_transaksi,
            (float) $data['total_belanja'],
            'cancel'
        );

        $response = $this->postJson('/api/payment/midtrans/notification', $payload);

        $response->assertStatus(200);

        $transaksi->refresh();
        $this->assertEquals('DIBATALKAN', $transaksi->status_pembayaran);
        $this->assertEquals('DIKEMBALIKAN', $transaksi->status_stok);

        // Stock restored
        $stokCabang->refresh();
        $this->assertEquals($stockAfterReservation + 1, $stokCabang->stok);
    }

    public function test_webhook_idempotency_ignores_duplicate_settlement(): void
    {
        $data = $this->createPendingOnlineTransaction(1);
        /** @var Transaksi $transaksi */
        $transaksi = $data['transaksi'];
        $stokCabang = $data['stok_cabang'];

        $payload = $this->buildPayload(
            $transaksi->kode_transaksi,
            (float) $data['total_belanja'],
            'settlement'
        );

        // First call
        $this->postJson('/api/payment/midtrans/notification', $payload)->assertStatus(200);

        $stockAfterFirstCall = $stokCabang->fresh()->stok;

        // Second duplicate call
        $this->postJson('/api/payment/midtrans/notification', $payload)->assertStatus(200);

        // Stock must not change again (idempotent)
        $stokCabang->refresh();
        $this->assertEquals($stockAfterFirstCall, $stokCabang->stok);
    }

    public function test_webhook_returns_404_for_unknown_order(): void
    {
        $payload = $this->buildPayload('UNKNOWN-ORDER-999999', 100000.00, 'settlement');

        $response = $this->postJson('/api/payment/midtrans/notification', $payload);

        $response->assertStatus(400);
        $response->assertJsonPath('success', false);
    }
}
