<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Client\AuthController;
use App\Http\Controllers\Client\PaymentController;


Route::post('/payment/initiate', [PaymentController::class, 'initiate']);
Route::get('/payment/callback', [PaymentController::class, 'handleVNPayCallback']);
