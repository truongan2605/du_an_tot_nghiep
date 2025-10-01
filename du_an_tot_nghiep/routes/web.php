<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\TangController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PhongController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\Admin\NhanVienController;

use App\Http\Controllers\Admin\TienNghiController;
use App\Http\Controllers\Client\ProfileController;
use App\Http\Controllers\Client\WishlistController;

Route::get('/', [HomeController::class, 'index'])->name('home');


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
        Route::get('user/create', [UserController::class, 'create'])->name('user.create');
        Route::post('user', [UserController::class, 'store'])->name('user.store');
        Route::get('user', [UserController::class, 'index'])->name('user.index');
        Route::get('user/{user}', [UserController::class, 'show'])->name('user.show');
        Route::get('user/{user}/edit', [UserController::class, 'edit'])->name('user.edit');
        Route::put('user/{user}', [UserController::class, 'update'])->name('user.update');
        Route::patch('user/{user}/toggle', [UserController::class, 'toggleActive'])->name('user.toggle');


        // ---- Nhân viên ----
        Route::get('nhan-vien', [NhanVienController::class, 'index'])->name('nhan-vien.index');
        Route::get('nhan-vien/create', [NhanVienController::class, 'create'])->name('nhan-vien.create');
        Route::post('nhan-vien', [NhanVienController::class, 'store'])->name('nhan-vien.store');
        Route::get('nhan-vien/{user}', [NhanVienController::class, 'show'])->name('nhan-vien.show');
        Route::get('nhan-vien/{user}/edit', [NhanVienController::class, 'edit'])->name('nhan-vien.edit');
        Route::put('nhan-vien/{user}', [NhanVienController::class, 'update'])->name('nhan-vien.update');
        Route::patch('nhan-vien/{user}/toggle', [NhanVienController::class, 'toggleActive'])->name('nhan-vien.toggle');
        Route::delete('nhan-vien/{user}', [NhanVienController::class, 'destroy'])->name('nhan-vien.destroy');

        // ---- Voucher ----
        Route::resource('voucher', VoucherController::class);
        Route::patch('voucher/{voucher}/toggle-active', [VoucherController::class, 'toggleActive'])
            ->name('voucher.toggle-active');
    });

Route::middleware('auth')->prefix('account')
    ->name('account.')
    ->group(function () {

        Route::get('settings', function () {
            return view('account.profile');
        })->name('settings');
        Route::patch('settings', [ProfileController::class, 'update'])->name('settings.update');

        Route::get('wishlist', [WishlistController::class, 'index'])->name('wishlist');
        Route::post('wishlist/toggle/{phong}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
        Route::delete('wishlist/{id}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');
        Route::post('wishlist/clear', [WishlistController::class, 'clear'])->name('wishlist.clear');
    });


require __DIR__ . '/auth.php';
