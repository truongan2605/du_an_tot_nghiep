<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\VatDungController as AdminVatDungController;

use App\Http\Controllers\HomeController;

use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\Admin\TangController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PhongController;
use App\Http\Controllers\Client\RoomController;
use App\Http\Controllers\Admin\BedTypeController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\Admin\NhanVienController;
use App\Http\Controllers\Admin\ThongBaoController;
use App\Http\Controllers\Client\BookingController;
use App\Http\Controllers\Client\ProfileController;



// Simple notification route - placed at the top
Route::middleware('auth')->get('notifications/{id}', [ThongBaoController::class, 'clientShow'])->name('notifications.show');

// Test route immediately after
Route::get('test-notifications-route', function() {
    return 'Test: ' . route('notifications.show', 1);
});

// Simple route test
Route::get('test-route-exists', function() {
    return 'Route exists: ' . (Route::has('notifications.show') ? 'YES' : 'NO');
});

use App\Http\Controllers\Admin\LoaiPhongController;
use App\Http\Controllers\Auth\SocialAuthController;




use App\Http\Controllers\Client\WishlistController;


use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\CustomerNotificationController;
use App\Http\Controllers\InternalNotificationController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\BatchNotificationController;
use App\Http\Controllers\Admin\TienNghiController as AdminTienNghiController;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/detail-room/{id}', [RoomController::class, 'show'])->name('rooms.show');

Route::get('auth/google', [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);

Route::get('/booking/availability', [\App\Http\Controllers\Client\BookingController::class, 'availability'])
    ->name('booking.availability');

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', App\Http\Middleware\AdminMiddleware::class])
    ->group(function () {
        // Tiện nghi
        Route::resource('tien-nghi', AdminTienNghiController::class);
        Route::patch('tien-nghi/{tienNghi}/toggle-active', [AdminTienNghiController::class, 'toggleActive'])
            ->name('tien-nghi.toggle-active');

             // vatdung
        Route::resource('vat-dung', AdminVatDungController::class);
        Route::patch('vat-dung/{vat_dung}/toggle-active', [AdminVatDungController::class, 'toggleActive'])
        ->name('vat-dung.toggle-active');
            
        // Loại phòng
        Route::get('/loai-phong/{id}/tien-nghi', [LoaiPhongController::class, 'getTienNghi']);
        Route::post('loai-phong/{id}/disable', [LoaiPhongController::class, 'disable'])
            ->name('loai_phong.disable');
        Route::post('loai-phong/{id}/enable', [LoaiPhongController::class, 'enable'])
            ->name('loai_phong.enable');

        // Phòng
        Route::resource('phong', PhongController::class);
        Route::delete('phong-image/{image}', [PhongController::class, 'destroyImage'])
            ->name('phong.image.destroy');
        // loai phong 
        Route::resource('loai_phong', LoaiPhongController::class);

        // ---- Tầng ----
        Route::resource('tang', TangController::class);

        // ---- Loại giường ----
        Route::resource('bed-types', BedTypeController::class);

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

        // ---- Giường ----
        Route::resource('bed-types', BedTypeController::class);
        // ---- Redirect old routes ----
        Route::get('thong-bao', function () {
            return redirect()->route('admin.customer-notifications.index');
        })->name('thong-bao.index');
        Route::get('thong-bao/create', function () {
            return redirect()->route('admin.customer-notifications.create');
        })->name('thong-bao.create');

        // ---- Thông báo khách hàng ----
        Route::get('customer-notifications', [App\Http\Controllers\CustomerNotificationController::class, 'index'])
            ->name('customer-notifications.index');
        Route::get('customer-notifications/create', [App\Http\Controllers\CustomerNotificationController::class, 'create'])
            ->name('customer-notifications.create');
        Route::post('customer-notifications', [App\Http\Controllers\CustomerNotificationController::class, 'store'])
            ->name('customer-notifications.store');
        Route::get('customer-notifications/{notification}', [App\Http\Controllers\CustomerNotificationController::class, 'show'])
            ->name('customer-notifications.show');
        Route::get('customer-notifications/{notification}/edit', [App\Http\Controllers\CustomerNotificationController::class, 'edit'])
            ->name('customer-notifications.edit');
        Route::put('customer-notifications/{notification}', [App\Http\Controllers\CustomerNotificationController::class, 'update'])
            ->name('customer-notifications.update');
        Route::delete('customer-notifications/{notification}', [App\Http\Controllers\CustomerNotificationController::class, 'destroy'])
            ->name('customer-notifications.destroy');
        Route::post('customer-notifications/{notification}/resend', [App\Http\Controllers\CustomerNotificationController::class, 'resend'])
            ->name('customer-notifications.resend');

        // ---- Thông báo nội bộ ----
        Route::get('internal-notifications', [App\Http\Controllers\InternalNotificationController::class, 'index'])
            ->name('internal-notifications.index');
        Route::get('internal-notifications/create', [App\Http\Controllers\InternalNotificationController::class, 'create'])
            ->name('internal-notifications.create');
        Route::post('internal-notifications', [App\Http\Controllers\InternalNotificationController::class, 'store'])
            ->name('internal-notifications.store');
        Route::get('internal-notifications/{notification}', [App\Http\Controllers\InternalNotificationController::class, 'show'])
            ->name('internal-notifications.show');
        Route::get('internal-notifications/{notification}/edit', [App\Http\Controllers\InternalNotificationController::class, 'edit'])
            ->name('internal-notifications.edit');
        Route::put('internal-notifications/{notification}', [App\Http\Controllers\InternalNotificationController::class, 'update'])
            ->name('internal-notifications.update');
        Route::delete('internal-notifications/{notification}', [App\Http\Controllers\InternalNotificationController::class, 'destroy'])
            ->name('internal-notifications.destroy');
        Route::post('internal-notifications/{notification}/resend', [App\Http\Controllers\InternalNotificationController::class, 'resend'])
            ->name('internal-notifications.resend');

        // ---- Thông báo Admin/Nhân viên ----
        Route::resource('admin-notifications', AdminNotificationController::class);
        Route::post('admin-notifications/{notification}/mark-read', [AdminNotificationController::class, 'markAsRead'])
            ->name('admin-notifications.mark-read');
        Route::post('admin-notifications/mark-all-read', [AdminNotificationController::class, 'markAllAsRead'])
            ->name('admin-notifications.mark-all-read');
        Route::get('api/admin-notifications/unread-count', [AdminNotificationController::class, 'getUnreadCount'])
            ->name('admin-notifications.unread-count');
        Route::get('api/admin-notifications/recent', [AdminNotificationController::class, 'getRecentNotifications'])
            ->name('admin-notifications.recent');

        // ---- Batch Notifications ----
        Route::get('batch-notifications', [BatchNotificationController::class, 'index'])
            ->name('batch-notifications.index');
        Route::get('batch-notifications/{batchId}', [BatchNotificationController::class, 'show'])
            ->name('batch-notifications.show');
        Route::get('api/batch-notifications/{batchId}/stats', [BatchNotificationController::class, 'getBatchStats'])
            ->name('batch-notifications.stats');
        Route::get('api/batch-notifications/{batchId}/progress', [BatchNotificationController::class, 'getBatchProgress'])
            ->name('batch-notifications.progress');
        Route::post('api/batch-notifications/{batchId}/retry', [BatchNotificationController::class, 'retryBatch'])
            ->name('batch-notifications.retry');
        Route::delete('api/batch-notifications/{batchId}', [BatchNotificationController::class, 'deleteBatch'])
            ->name('batch-notifications.delete');
    });

Route::middleware('auth')->prefix('account')
    ->name('account.')
    ->group(function () {

// In-app notification: mark as read
Route::middleware('auth')->post('thong-bao/{thong_bao}/read', [ThongBaoController::class, 'markRead'])->name('thong-bao.mark-read');

// Mark notification as read when viewing
Route::middleware('auth')->post('notifications/{id}/read', [ThongBaoController::class, 'markReadOnView'])->name('thong-bao.mark-read-on-view');

// Client notification modal
Route::middleware('auth')->get('notifications/{id}/modal', [ThongBaoController::class, 'clientModal'])->name('thong-bao.client-modal');

// API for unread count
Route::middleware('auth')->get('api/notifications/unread-count', [ThongBaoController::class, 'getUnreadCount']);

// API routes for notifications - Basic access for all users
Route::middleware('auth')->prefix('api/notifications')->group(function () {
    Route::get('/recent', [App\Http\Controllers\Api\NotificationController::class, 'getRecent']);
    Route::get('/unread-count', [App\Http\Controllers\Api\NotificationController::class, 'getUnreadCount']);
    Route::get('/{id}', [App\Http\Controllers\Api\NotificationController::class, 'show']);
    Route::post('/{id}/read', [App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::post('/mark-all-read', [App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
});

// API routes for notifications - Admin only access
Route::middleware(['auth', 'admin'])->prefix('api/notifications')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::get('/stats', [App\Http\Controllers\Api\NotificationController::class, 'getStats']);
    Route::post('/mark-multiple-read', [App\Http\Controllers\Api\NotificationController::class, 'markMultipleAsRead']);
    Route::delete('/{id}', [App\Http\Controllers\Api\NotificationController::class, 'destroy']);
});
        Route::get('settings', function () {
            return view('account.profile');
        })->name('settings');
        Route::patch('settings', [ProfileController::class, 'update'])->name('settings.update');

        Route::get('wishlist', [WishlistController::class, 'index'])->name('wishlist');
        Route::post('wishlist/toggle/{phong}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
        Route::delete('wishlist/{id}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');
        Route::post('wishlist/clear', [WishlistController::class, 'clear'])->name('wishlist.clear');

        Route::get('/booking/{phong}/create', [BookingController::class, 'create'])->name('booking.create');
        Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');

        Route::get('bookings', [BookingController::class, 'index'])
            ->name('booking.index');

        Route::get('bookings/{dat_phong}', [BookingController::class, 'show'])
            ->name('booking.show');
    });



require __DIR__ . '/auth.php';
