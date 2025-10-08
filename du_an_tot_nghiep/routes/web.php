<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;


use App\Http\Controllers\Admin\TangController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PhongController;
use App\Http\Controllers\Client\RoomController;
use App\Http\Controllers\Staff\StaffController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\Admin\NhanVienController;
use App\Http\Controllers\Admin\TienNghiController;
use App\Http\Controllers\Client\PaymentController;
use App\Http\Controllers\Client\ProfileController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Client\WishlistController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/detail-room/{id}', [RoomController::class, 'show'])->name('rooms.show');

// Google login
Route::get('auth/google', [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);

// ==================== ADMIN ====================
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
        Route::resource('nhan-vien', NhanVienController::class);
        Route::patch('nhan-vien/{user}/toggle', [NhanVienController::class, 'toggleActive'])->name('nhan-vien.toggle');

        // ---- Voucher ----
        Route::resource('voucher', VoucherController::class);
        Route::patch('voucher/{voucher}/toggle-active', [VoucherController::class, 'toggleActive'])
            ->name('voucher.toggle-active');
    });

// ==================== STAFF  ====================
Route::middleware(['auth', 'role:nhan_vien|admin'])
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {
        Route::get('/', [StaffController::class, 'index'])->name('index');
        Route::get('/pending-bookings', [StaffController::class, 'pendingBookings'])->name('pending-bookings');
        Route::get('/assign-rooms/{dat_phong_id}', [StaffController::class, 'assignRoomsForm'])->name('assign-rooms');
        Route::post('/assign-rooms/{dat_phong_id}', [StaffController::class, 'assignRooms'])->name('assign-rooms.post');
        Route::get('/rooms', [StaffController::class, 'rooms'])->name('rooms');
        Route::post('/confirm/{id}', [StaffController::class, 'confirm'])->name('confirm');
        Route::get('/bookings', [StaffController::class, 'bookings'])->name('bookings');
        Route::delete('/cancel/{id}', [StaffController::class, 'cancel'])->name('cancel');
    });

// ==================== ACCOUNT ====================
Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::view('settings', 'account.profile')->name('settings');
    Route::patch('settings', [ProfileController::class, 'update'])->name('settings.update');

    Route::get('wishlist', [WishlistController::class, 'index'])->name('wishlist');
    Route::post('wishlist/toggle/{phong}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::delete('wishlist/{id}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');
    Route::post('wishlist/clear', [WishlistController::class, 'clear'])->name('wishlist.clear');
});

// ================== Thanh toán ================
Route::middleware('auth:sanctum')->post('/payment/initiate', [PaymentController::class, 'initiateVNPay']);
Route::post('/payment/initiate', [PaymentController::class, 'initiate'])->name('payment.initiate');
Route::get('/payment/callback', [PaymentController::class, 'handleVNPayCallback'])->name('payment.callback');
require __DIR__ . '/auth.php';
