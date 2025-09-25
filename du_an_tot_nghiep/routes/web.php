<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TienNghiController;

Route::get('/', function () {
    return view('home');
});

Route::resource('tien-nghi', TienNghiController::class);
Route::patch('tien-nghi/{tienNghi}/toggle-active', [TienNghiController::class, 'toggleActive'])->name('tien-nghi.toggle-active');
