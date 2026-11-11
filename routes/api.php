<?php

use App\Http\Controllers\Payment\MidtransNotificationController;
use Illuminate\Support\Facades\Route;

Route::post('/payment/midtrans/notification', [MidtransNotificationController::class, 'handle'])
    ->name('api.payment.midtrans.notification');
