<?php

use App\Http\Controllers\TienNghiController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PhongController;
use App\Http\Controllers\VoucherController;

Route::get('/', function () {
    return view('home');
});

// Routes for TienNghi CRUD
Route::resource('tien-nghi', TienNghiController::class);
Route::patch('tien-nghi/{tienNghi}/toggle-active', [TienNghiController::class, 'toggleActive'])->name('tien-nghi.toggle-active');

Route::get('/voucher', [VoucherController::class, 'index'])->name('voucher.index');

// Thêm mới
Route::get('/voucher/create', [VoucherController::class, 'create'])->name('voucher.create');
Route::post('/voucher', [VoucherController::class, 'store'])->name('voucher.store');

// Sửa
Route::get('/voucher/{voucher}/edit', [VoucherController::class, 'edit'])->name('voucher.edit');
Route::put('/voucher/{voucher}', [VoucherController::class, 'update'])->name('voucher.update');

// Xóa
Route::delete('/voucher/{voucher}', [VoucherController::class, 'destroy'])->name('voucher.destroy');

// Xem chi tiết (nếu cần)
Route::get('/voucher/{voucher}', [VoucherController::class, 'show'])->name('voucher.show');
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

