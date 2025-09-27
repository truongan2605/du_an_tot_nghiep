<?php

use App\Http\Controllers\TienNghiController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PhongController;
use App\Http\Controllers\Admin\VoucherController;


Route::get('/', function () {
    return view('home');
});



Route::prefix('admin')
    ->middleware(['auth', App\Http\Middleware\AdminMiddleware::class])
    ->group(function (): void {
        // Resource cho Voucher
        Route::resource('voucher', VoucherController::class);

        // Nếu cần route riêng cho toggle-active (trạng thái kích hoạt voucher chẳng hạn)
        Route::patch('voucher/{voucher}/toggle-active', [VoucherController::class, 'toggleActive'])
            ->name('voucher.toggle-active');
    });
// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });

// require __DIR__.'/auth.php';




Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('phong', PhongController::class);

   Route::delete('phong-image/{image}', [PhongController::class, 'destroyImage'])
     ->name('phong.image.destroy');

});
Route::prefix('admin')->group(function () {
    // Tien Nghi routes under /admin
    Route::resource('tien-nghi', TienNghiController::class);
    Route::patch('tien-nghi/{tienNghi}/toggle-active', [TienNghiController::class, 'toggleActive'])
        ->name('tien-nghi.toggle-active');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::prefix('admin')
    ->middleware(['auth', App\Http\Middleware\AdminMiddleware::class])
    ->group(function () {
        Route::resource('tien-nghi', TienNghiController::class);
        Route::patch('tien-nghi/{tienNghi}/toggle-active', [TienNghiController::class, 'toggleActive'])
            ->name('tien-nghi.toggle-active');
});

require __DIR__.'/auth.php';

