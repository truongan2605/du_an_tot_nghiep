<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TienNghiController;
use App\Http\Controllers\Client\HotelController;

Route::get('/', [HomeController::class, 'index'])->name('home');


Route::prefix('admin')
    ->middleware(['auth', App\Http\Middleware\AdminMiddleware::class]) 
    ->group(function () {
        Route::resource('tien-nghi', TienNghiController::class);
        Route::patch('tien-nghi/{tienNghi}/toggle-active', [TienNghiController::class, 'toggleActive'])
            ->name('tien-nghi.toggle-active');
});

require __DIR__.'/auth.php';

// Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::get('/hotel', [HotelController::class, 'index'])->name('home');


// Routes for TienNghi CRUD
Route::resource('tien-nghi', TienNghiController::class);
Route::patch('tien-nghi/{tienNghi}/toggle-active', [TienNghiController::class, 'toggleActive'])->name('tien-nghi.toggle-active');
