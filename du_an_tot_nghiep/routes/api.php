<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Client\AuthController;
use App\Http\Controllers\Client\PaymentController;
use App\Http\Controllers\Payment\ConfirmPaymentController;


Route::post('/payment/initiate', [PaymentController::class, 'initiate']);
Route::get('/payment/callback', [PaymentController::class, 'handleVNPayCallback']);
Route::post('/confirm-payment/{dat_phong_id}', [ConfirmPaymentController::class, 'confirm'])->name('api.confirm-payment');