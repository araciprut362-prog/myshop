<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SimulasiPaymentController extends Controller
{
    public function __construct(
        protected MidtransService $midtransService
    ) {}

    public function simulate(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'string'],
            'status' => ['required', 'in:settlement,pending,expire,cancel,deny'],
        ]);

        $transaksi = Transaksi::where('kode_transaksi', $validated['order_id'])->firstOrFail();

        $statusCode = $validated['status'] === 'settlement' ? '200' : '201';
        $grossAmount = number_format((float) $transaksi->total_belanja, 2, '.', '');
        $serverKey = (string) config('services.midtrans.server_key');
        $signatureKey = hash('sha512', $transaksi->kode_transaksi.$statusCode.$grossAmount.$serverKey);

        $payload = [
            'order_id' => $transaksi->kode_transaksi,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'transaction_status' => $validated['status'],
            'fraud_status' => 'accept',
            'payment_type' => 'qris',
            'transaction_id' => 'SIMULASI-'.bin2hex(random_bytes(8)),
            'signature_key' => $signatureKey,
        ];

        $hasil = $this->midtransService->handleNotification($payload, verifySignature: false);

        if ($request->wantsJson()) {
            return response()->json($hasil);
        }

        return redirect()->back()->with('success', $hasil['message']);
    }
}
