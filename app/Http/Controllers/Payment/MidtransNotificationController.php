<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransNotificationController extends Controller
{
    public function __construct(
        protected MidtransService $midtransService
    ) {}

    public function handle(Request $request): JsonResponse
    {
        Log::info('Midtrans Webhook Received:', $request->all());

        $result = $this->midtransService->handleNotification($request->all());

        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
