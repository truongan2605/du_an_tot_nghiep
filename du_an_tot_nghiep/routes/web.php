<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PhongController;
use App\Http\Controllers\Admin\VoucherController;

use App\Http\Controllers\Admin\TienNghiController as AdminTienNghiController;

Route::get('/', function () {
    return view('home');
});






Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', App\Http\Middleware\AdminMiddleware::class])
    ->group(function () {
        // Tiện nghi
        Route::resource('tien-nghi', AdminTienNghiController::class);
        Route::patch('tien-nghi/{tienNghi}/toggle-active', [AdminTienNghiController::class, 'toggleActive'])
            ->name('tien-nghi.toggle-active');

        // Phòng
        Route::resource('phong', PhongController::class);
        Route::delete('phong-image/{image}', [PhongController::class, 'destroyImage'])
            ->name('phong.image.destroy');
        Route::resource('voucher', VoucherController::class);

        // Nếu cần route riêng cho toggle-active (trạng thái kích hoạt voucher chẳng hạn)
        Route::patch('voucher/{voucher}/toggle-active', [VoucherController::class, 'toggleActive'])
            ->name('voucher.toggle-active');
    });


require __DIR__.'/auth.php';

