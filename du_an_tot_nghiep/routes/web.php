<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PhongController;
use App\Http\Controllers\Admin\LoaiPhongController;
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

        // loai phong 
        Route::resource('loai_phong', LoaiPhongController::class);
        
    });
    Route::get('/admin/loai-phong/{id}/tien-nghi', [LoaiPhongController::class, 'getTienNghi']);


require __DIR__.'/auth.php';

