<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TienNghiController;
use App\Http\Controllers\Client\HotelController;

use App\Http\Controllers\Admin\PhongController;
use App\Http\Controllers\Admin\VoucherController;

use App\Http\Controllers\Admin\TienNghiController as AdminTienNghiController;

Route::get('/', function () {
    return view('home');
});

Route::get('/', [HomeController::class, 'index'])->name('home');

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
});

require __DIR__.'/auth.php';

// Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::get('/hotel', [HotelController::class, 'index'])->name('home');



