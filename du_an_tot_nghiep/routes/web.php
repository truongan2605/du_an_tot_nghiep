<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TangController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PhongController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\Admin\NhanVienController;
use App\Http\Controllers\Admin\TienNghiController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Trang chủ
Route::get('/', function () {
    return view('home');
});

// Nhóm route dành cho admin
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', App\Http\Middleware\AdminMiddleware::class])
    ->group(function () {

        // ---- Tiện nghi ----
        Route::resource('tien-nghi', TienNghiController::class);
        Route::patch('tien-nghi/{tienNghi}/toggle-active', [TienNghiController::class, 'toggleActive'])
            ->name('tien-nghi.toggle-active');

        // ---- Phòng ----
        Route::resource('phong', PhongController::class);
        Route::delete('phong-image/{image}', [PhongController::class, 'destroyImage'])
            ->name('phong.image.destroy');

        // ---- Tầng ----
        Route::resource('tang', TangController::class);

        // ---- Người dùng ----
        Route::get('user/create', [UserController::class, 'create'])->name('user.create');  // 👈 ROUTE NÀY PHẢI ĐẶT TRÊN CÙNG
        Route::post('user', [UserController::class, 'store'])->name('user.store');       // Lưu khách hàng
    
        Route::get('user', [UserController::class, 'index'])->name('user.index');
        Route::get('user/{user}', [UserController::class, 'show'])->name('user.show');
        Route::patch('user/{user}/toggle', [UserController::class, 'toggleActive'])->name('user.toggle');


        // ---- Nhân viên ----
        Route::resource('nhan-vien', NhanVienController::class);
        Route::patch('nhan-vien/{user}/toggle', [NhanVienController::class, 'toggleActive'])
            ->name('nhan-vien.toggle');

        // ---- Voucher ----
        Route::resource('voucher', VoucherController::class);
        Route::patch('voucher/{voucher}/toggle-active', [VoucherController::class, 'toggleActive'])
            ->name('voucher.toggle-active');
    });

// Auth routes (Laravel Breeze / Jetstream...)
require __DIR__ . '/auth.php';
