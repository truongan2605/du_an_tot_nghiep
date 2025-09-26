<?php

use App\Http\Controllers\TienNghiController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});


Route::prefix('admin')
    ->middleware(['auth', App\Http\Middleware\AdminMiddleware::class]) 
    ->group(function () {
        Route::resource('tien-nghi', TienNghiController::class);
        Route::patch('tien-nghi/{tienNghi}/toggle-active', [TienNghiController::class, 'toggleActive'])
            ->name('tien-nghi.toggle-active');
});

require __DIR__.'/auth.php';
