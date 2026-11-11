<?php

namespace App\Services;

use App\Models\LogAktivitas;
use App\Models\Pembayaran;
use App\Models\Transaksi;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;

class MidtransService
{
    public function __construct(
        protected StockService $stockService
    ) {
        $this->configure();
    }

    protected function configure(): void
    {
        Config::$serverKey = (string) config('services.midtrans.server_key');
        Config::$isProduction = (bool) config('services.midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    /**
     * Memeriksa dan menyinkronkan status transaksi langsung dari server API Midtrans.
     * Sangat berguna di environment lokal (localhost) di mana webhook Midtrans tidak bisa menjangkau server secara langsung.
     *
     * @return array{success: bool, message: string}
     */
    public function syncStatusFromMidtrans(Transaksi $transaksi): array
    {
        $this->configure();

        $serverKey = (string) config('services.midtrans.server_key');
        if (empty($serverKey) || $serverKey === 'SB-Mid-server-demo-key') {
            return ['success' => false, 'message' => 'Midtrans server key belum dikonfigurasi.'];
        }

        try {
            $statusObj = Transaction::status($transaksi->kode_transaksi);
            $payload = (array) $statusObj;

            // Direct API response dari Midtrans server (sudah terotentikasi via Basic Auth HTTPS)
            return $this->handleNotification($payload, verifySignature: false);
        } catch (\Throwable $e) {
            Log::info("Cek status Midtrans untuk {$transaksi->kode_transaksi}: {$e->getMessage()}");

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Membuat Snap Token untuk pesanan online.
     *
     * @return array{snap_token: string, redirect_url: string}
     */
    public function createSnapTransaction(Transaksi $transaksi, ?User $user = null): array
    {
        $this->configure();

        $items = [];
        foreach ($transaksi->detailTransaksi as $item) {
            $items[] = [
                'id' => (string) $item->id_produk,
                'price' => (int) round($item->harga_satuan - $item->diskon_item),
                'quantity' => (int) $item->jumlah,
                'name' => mb_substr($item->nama_produk_snapshot, 0, 50),
            ];
        }

        // Tambahkan item ongkos kirim jika ada
        if ($transaksi->ongkir > 0) {
            $items[] = [
                'id' => 'SHIPPING',
                'price' => (int) round($transaksi->ongkir),
                'quantity' => 1,
                'name' => 'Ongkos Kirim',
            ];
        }

        // Tambahkan item pajak simulasi jika ada
        if ($transaksi->pajak > 0) {
            $items[] = [
                'id' => 'TAX',
                'price' => (int) round($transaksi->pajak),
                'quantity' => 1,
                'name' => 'Pajak (PPN)',
            ];
        }

        // Diskon global jika ada
        if ($transaksi->diskon > 0) {
            $items[] = [
                'id' => 'DISCOUNT',
                'price' => -(int) round($transaksi->diskon),
                'quantity' => 1,
                'name' => 'Diskon Promo',
            ];
        }

        $pelanggan = $transaksi->pelanggan;
        $customerDetails = [
            'first_name' => $pelanggan ? $pelanggan->nama_pelanggan : ($user ? $user->nama_lengkap : 'Pelanggan'),
            'email' => $user ? $user->email : 'pelanggan@myshop.test',
            'phone' => $pelanggan ? ($pelanggan->no_hp ?? '') : ($user ? ($user->no_hp ?? '') : ''),
            'billing_address' => [
                'first_name' => $pelanggan ? $pelanggan->nama_pelanggan : 'Pelanggan',
                'address' => $transaksi->alamat_pengiriman ?? ($pelanggan ? $pelanggan->alamat : ''),
                'city' => $pelanggan ? ($pelanggan->kota ?? 'Indonesia') : 'Indonesia',
                'postal_code' => $pelanggan ? ($pelanggan->kode_pos ?? '00000') : '00000',
                'country_code' => 'IDN',
            ],
            'shipping_address' => [
                'first_name' => $pelanggan ? $pelanggan->nama_pelanggan : 'Pelanggan',
                'address' => $transaksi->alamat_pengiriman ?? ($pelanggan ? $pelanggan->alamat : ''),
                'city' => $pelanggan ? ($pelanggan->kota ?? 'Indonesia') : 'Indonesia',
                'postal_code' => $pelanggan ? ($pelanggan->kode_pos ?? '00000') : '00000',
                'country_code' => 'IDN',
            ],
        ];

        $params = [
            'transaction_details' => [
                'order_id' => $transaksi->kode_transaksi,
                'gross_amount' => (int) round($transaksi->total_belanja),
            ],
            'item_details' => $items,
            'customer_details' => $customerDetails,
        ];

        try {
            // Mencoba membuat Snap Token resmi menggunakan SDK Midtrans
            $snapResponse = Snap::createTransaction($params);
            $token = is_object($snapResponse) ? ($snapResponse->token ?? '') : ($snapResponse['token'] ?? '');
            $redirectUrl = is_object($snapResponse) ? ($snapResponse->redirect_url ?? '') : ($snapResponse['redirect_url'] ?? '');

            return [
                'snap_token' => $token,
                'redirect_url' => $redirectUrl,
            ];
        } catch (Exception $e) {
            Log::warning('Midtrans Snap API Warning: '.$e->getMessage().'. Menggunakan Sandbox fallback token.');

            // Fallback Sandbox Token untuk mode simulasi lokal apabila server key dummy
            $fallbackToken = 'SANDBOX-TOKEN-'.bin2hex(random_bytes(16));
            $fallbackRedirect = "https://app.sandbox.midtrans.com/snap/v2/vtweb/{$fallbackToken}";

            return [
                'snap_token' => $fallbackToken,
                'redirect_url' => $fallbackRedirect,
            ];
        }
    }

    /**
     * Verifikasi signature SHA-512 dari webhook Midtrans.
     */
    public function verifySignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool
    {
        $serverKey = (string) config('services.midtrans.server_key');

        // Format angka desimal Midtrans pada signature dapat berupa 10000.00 atau 10000
        $hashDirect = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);
        if ($hashDirect === $signatureKey) {
            return true;
        }

        // Coba format 2 desimal
        $formattedAmount = number_format((float) $grossAmount, 2, '.', '');
        $hashFormatted = hash('sha512', $orderId.$statusCode.$formattedAmount.$serverKey);
        if ($hashFormatted === $signatureKey) {
            return true;
        }

        return false;
    }

    /**
     * Memproses notifikasi webhook dari Midtrans.
     * IDEMPOTENT & THREAD-SAFE.
     *
     * @param  array<string, mixed>  $payload
     * @return array{success: bool, message: string}
     */
    public function handleNotification(array $payload, bool $verifySignature = true): array
    {
        $orderId = $payload['order_id'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;
        $grossAmount = $payload['gross_amount'] ?? '0';
        $signatureKey = $payload['signature_key'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $paymentType = $payload['payment_type'] ?? null;
        $transactionId = $payload['transaction_id'] ?? null;

        if (! $orderId || ! $transactionStatus) {
            return ['success' => false, 'message' => 'Payload tidak lengkap.'];
        }

        // Verifikasi signature jika Server Key sudah dikonfigurasi nyata (khusus webhook publik)
        if ($verifySignature) {
            $serverKey = (string) config('services.midtrans.server_key');
            if (! empty($serverKey) && $serverKey !== 'SB-Mid-server-demo-key') {
                if (! $this->verifySignature((string) $orderId, (string) $statusCode, (string) $grossAmount, (string) $signatureKey)) {
                    Log::error("Midtrans signature invalid for order: {$orderId}");

                    return ['success' => false, 'message' => 'Invalid signature key.'];
                }
            }
        }

        return DB::transaction(function () use (
            $orderId,
            $transactionStatus,
            $fraudStatus,
            $paymentType,
            $transactionId,
            $payload
        ) {
            $transaksi = Transaksi::where('kode_transaksi', $orderId)
                ->lockForUpdate()
                ->first();

            if (! $transaksi) {
                return ['success' => false, 'message' => "Transaksi {$orderId} tidak ditemukan."];
            }

            $pembayaran = Pembayaran::where('id_transaksi', $transaksi->id_transaksi)
                ->latest('id_pembayaran')
                ->lockForUpdate()
                ->first();

            // Pemetaan status Midtrans ke MyShop
            $statusBayar = match ($transactionStatus) {
                'capture' => ($fraudStatus === 'challenge') ? 'PENDING' : 'BERHASIL',
                'settlement' => 'BERHASIL',
                'pending' => 'PENDING',
                'deny' => 'GAGAL',
                'expire' => 'KEDALUWARSA',
                'cancel' => 'DIBATALKAN',
                'refund', 'partial_refund' => 'DIKEMBALIKAN',
                default => 'TIDAK_DIKETAHUI',
            };

            // IDEMPOTENCY: jika status pembayaran sama persis dan sudah berhasil/batal, abaikan duplikat
            if ($transaksi->status_pembayaran === $statusBayar && in_array($statusBayar, ['BERHASIL', 'KEDALUWARSA', 'DIBATALKAN'])) {
                return ['success' => true, 'message' => 'Notifikasi sudah diproses sebelumnya (Idempotent).'];
            }

            // Update data pembayaran
            if ($pembayaran) {
                $pembayaran->status = $statusBayar;
                $pembayaran->midtrans_transaction_id = $transactionId;
                $pembayaran->midtrans_payment_type = $paymentType;
                $pembayaran->midtrans_transaction_status = $transactionStatus;
                $pembayaran->midtrans_fraud_status = $fraudStatus;
                $pembayaran->raw_notification = json_encode($payload);
                if ($statusBayar === 'BERHASIL') {
                    $pembayaran->waktu_pembayaran = now();
                }
                $pembayaran->save();
            }

            $transaksi->status_pembayaran = $statusBayar;

            // Logika pengelolaan stok dan status transaksi
            if ($statusBayar === 'BERHASIL') {
                $transaksi->status_transaksi = 'SELESAI';
                $this->stockService->konfirmasiStokTerbayar($transaksi);

                LogAktivitas::catat(
                    $transaksi->kasir?->id_user ?? null,
                    'Pembayaran online berhasil via Midtrans',
                    'tbl_transaksi',
                    $transaksi->id_transaksi,
                    "Order ID: {$orderId}, Payment Type: {$paymentType}, ID Transaksi Midtrans: {$transactionId}"
                );
            } elseif (in_array($statusBayar, ['KEDALUWARSA', 'DIBATALKAN', 'GAGAL'], true)) {
                $transaksi->status_transaksi = 'DIBATALKAN';
                $this->stockService->kembalikanStokReservasi($transaksi, null, "Midtrans {$transactionStatus}");

                LogAktivitas::catat(
                    null,
                    "Pesanan online {$statusBayar} via Midtrans",
                    'tbl_transaksi',
                    $transaksi->id_transaksi,
                    "Order ID: {$orderId}, Status: {$transactionStatus}"
                );
            }

            $transaksi->save();

            return [
                'success' => true,
                'message' => "Status transaksi {$orderId} berhasil disinkronisasi ke {$statusBayar}.",
            ];
        });
    }
}
