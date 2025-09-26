<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TienNghiController;
use App\Http\Controllers\Admin\TangController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PhongController;
use App\Http\Controllers\Admin\NhanVienController;

Route::get('/', function () {
    return view('home');
});




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
Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('tang', TangController::class);
});

// Route::prefix('admin')->name('admin.')->group(function () {
//     Route::middleware('auth')->group(function () {  // Require login
//         Route::get('user', [UserController::class, 'index'])->name('user.index');
//         Route::get('user/{user}', [UserController::class, 'show'])->name('user.show');
//         Route::patch('user/{user}/toggle', [UserController::class, 'toggleActive'])->name('user.toggle');
//     });
// });

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('user', [UserController::class, 'index'])->name('user.index');
    Route::get('user/{user}', [UserController::class, 'show'])->name('user.show');
    Route::patch('user/{user}/toggle', [UserController::class, 'toggleActive'])->name('user.toggle');
});

// Route::prefix('admin')->name('admin.')->group(function () {
//     Route::middleware('auth')->group(function () {
//         Route::resource('nhan-vien', NhanVienController::class);  // Full CRUD routes
//         Route::patch('nhan-vien/{user}/toggle', [NhanVienController::class, 'toggleActive'])->name('nhan-vien.toggle');
//     });
// });
Route::prefix('admin')->name('admin.')->group(function () {
    // Full CRUD routes cho nhân viên
    Route::resource('nhan-vien', NhanVienController::class);

    // Route toggle active
    Route::patch('nhan-vien/{user}/toggle', [NhanVienController::class, 'toggleActive'])
        ->name('nhan-vien.toggle');
});


Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

