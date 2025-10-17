<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Client\AuthController;
use App\Http\Controllers\Client\PaymentController;
use App\Http\Controllers\Payment\ConfirmPaymentController;


Route::middleware('auth:sanctum')->group(function () {
    // POST initiate payment qua API
    Route::post('/payment/initiate', [PaymentController::class, 'initiateVNPay']);

    // Xác nhận thanh toán từ app
    Route::post('/confirm-payment/{dat_phong_id}', [ConfirmPaymentController::class, 'confirm'])->name('api.confirm-payment');
});

// IPN / Return VNPAY
Route::post('/payment/ipn', [PaymentController::class, 'handleIpn'])->name('payment.ipn');
Route::get('/payment/return', [PaymentController::class, 'handleReturn'])->name('payment.return');
Route::get('/payment/callback', [PaymentController::class, 'handleVNPayCallback']);