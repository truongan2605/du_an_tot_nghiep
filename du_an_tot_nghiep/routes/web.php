<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PhongController;
Route::get('/', function () {
    return view('welcome');
});



Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('phong', PhongController::class);

   Route::delete('phong-image/{image}', [PhongController::class, 'destroyImage'])
     ->name('phong.image.destroy');
    
});