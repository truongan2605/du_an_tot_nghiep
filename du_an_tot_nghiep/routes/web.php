<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HomeController;

use App\Http\Controllers\Admin\TangController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PhongController;
use App\Http\Controllers\Client\RoomController;
use App\Http\Controllers\Staff\StaffController;
use App\Http\Controllers\Admin\BedTypeController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\Admin\NhanVienController;
use App\Http\Controllers\Admin\ThongBaoController;
use App\Http\Controllers\Client\BookingController;
use App\Http\Controllers\Client\PaymentController;
use App\Http\Controllers\Client\ProfileController;
use App\Http\Controllers\Admin\LoaiPhongController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Client\WishlistController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\CustomerNotificationController;
use App\Http\Controllers\InternalNotificationController;
use App\Http\Controllers\Payment\ConfirmPaymentController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\BatchNotificationController;
use App\Http\Controllers\Admin\VatDungController as AdminVatDungController;
use App\Http\Controllers\Admin\TienNghiController as AdminTienNghiController;


// ==================== CLIENT ====================
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/list-room', [RoomController::class, 'index'])->name('list-room.index');
Route::get('/list-room/{id}', [RoomController::class, 'show'])->name('list-room.show');

Route::get('/detail-room/{id}', [RoomController::class, 'show'])->name('rooms.show');

// Google login
Route::get('auth/google', [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);

// ==================== BOOKING ====================
Route::get('/booking/availability', [BookingController::class, 'availability'])->name('booking.availability');

// ==================== ADMIN ====================
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', App\Http\Middleware\AdminMiddleware::class])
    ->group(function () {
        // Tiện nghi
        Route::resource('tien-nghi', AdminTienNghiController::class);
        Route::patch('tien-nghi/{tienNghi}/toggle-active', [AdminTienNghiController::class, 'toggleActive'])->name('tien-nghi.toggle-active');

        // vatdung
        Route::resource('vat-dung', AdminVatDungController::class);
        Route::patch('vat-dung/{vat_dung}/toggle-active', [AdminVatDungController::class, 'toggleActive'])
            ->name('vat-dung.toggle-active');

        // Loại phòng
        Route::get('/loai-phong/{id}/tien-nghi', [LoaiPhongController::class, 'getTienNghi']);
        Route::post('loai-phong/{id}/disable', [LoaiPhongController::class, 'disable'])->name('loai_phong.disable');
        Route::post('loai-phong/{id}/enable', [LoaiPhongController::class, 'enable'])->name('loai_phong.enable');
        Route::resource('loai_phong', LoaiPhongController::class);

        // Phòng
        Route::resource('phong', PhongController::class);
        Route::delete('phong-image/{image}', [PhongController::class, 'destroyImage'])->name('phong.image.destroy');
        Route::delete('phong-image/{image}', [PhongController::class, 'destroyImage'])
            ->name('phong.image.destroy');
        // loai phong
        Route::resource('loai_phong', LoaiPhongController::class);

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
        Route::patch('voucher/{voucher}/toggle-active', [VoucherController::class, 'toggleActive'])->name('voucher.toggle-active');

        // ---- Giường ----
        Route::resource('bed-types', BedTypeController::class);

        // ---- Thông báo (Notifications) ----
        Route::get('customer-notifications', [CustomerNotificationController::class, 'index'])->name('customer-notifications.index');
        Route::get('customer-notifications/create', [CustomerNotificationController::class, 'create'])->name('customer-notifications.create');
        Route::post('customer-notifications', [CustomerNotificationController::class, 'store'])->name('customer-notifications.store');
        Route::get('customer-notifications/{notification}', [CustomerNotificationController::class, 'show'])->name('customer-notifications.show');
        Route::get('customer-notifications/{notification}/edit', [CustomerNotificationController::class, 'edit'])->name('customer-notifications.edit');
        Route::put('customer-notifications/{notification}', [CustomerNotificationController::class, 'update'])->name('customer-notifications.update');
        Route::delete('customer-notifications/{notification}', [CustomerNotificationController::class, 'destroy'])->name('customer-notifications.destroy');
        Route::post('customer-notifications/{notification}/resend', [CustomerNotificationController::class, 'resend'])->name('customer-notifications.resend');

        Route::resource('internal-notifications', InternalNotificationController::class);
        Route::post('internal-notifications/{internal_notification}/resend', [InternalNotificationController::class, 'resend'])->name('internal-notifications.resend');
        Route::resource('admin-notifications', AdminNotificationController::class);
        Route::get('batch-notifications', [BatchNotificationController::class, 'index'])->name('batch-notifications.index');
    });

// ==================== STAFF ====================
Route::middleware(['auth', 'role:nhan_vien|admin'])
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {
        // Dashboard
        Route::get('/', [StaffController::class, 'index'])->name('index');

        // Booking chờ xác nhận
        Route::get('/pending-bookings', [StaffController::class, 'pendingBookings'])->name('pending-bookings');
        // Route::get('/assign-rooms/{dat_phong_id}', [StaffController::class, 'assignRoomsForm'])->name('assign-rooms');
        // Route::post('/assign-rooms/{dat_phong_id}', [StaffController::class, 'assignRooms'])->name('assign-rooms.post');
        Route::post('/confirm/{id}', [StaffController::class, 'confirm'])->name('confirm');
        Route::delete('/cancel/{id}', [StaffController::class, 'cancel'])->name('cancel');
        Route::get('/bookings', [StaffController::class, 'bookings'])->name('bookings');
        Route::get('/bookings/{booking}', [StaffController::class, 'showBooking'])->name('bookings.show');

        // Quản lý phòng
        Route::get('/rooms', [StaffController::class, 'rooms'])->name('rooms');
        Route::patch('/rooms/{room}', [StaffController::class, 'updateRoom'])->name('rooms.update');

        // Checkin / Checkout
        Route::get('/checkin', [StaffController::class, 'checkinForm'])->name('checkin');
        Route::post('/process-checkin', [StaffController::class, 'processCheckin'])->name('processCheckin');
        


        // Báo cáo / Tổng quan
        Route::get('/reports', [StaffController::class, 'reports'])->name('reports');
        Route::get('/room-overview', [StaffController::class, 'roomOverview'])->name('room-overview');
    });


// ==================== ACCOUNT ====================
Route::middleware('auth')
    ->prefix('account')
    ->name('account.')
    ->group(function () {

        // Cài đặt tài khoản
        Route::get('settings', function () {
            return view('account.profile');
        })->name('settings');
        Route::patch('settings', [ProfileController::class, 'update'])->name('settings.update');

        // Danh sách yêu thích
        Route::get('wishlist', [WishlistController::class, 'index'])->name('wishlist');
        Route::post('wishlist/toggle/{phong}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
        Route::delete('wishlist/{id}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');
        Route::post('wishlist/clear', [WishlistController::class, 'clear'])->name('wishlist.clear');

        // Đặt phòng
        Route::get('booking/{phong}/create', [BookingController::class, 'create'])->name('booking.create');
        Route::post('booking', [BookingController::class, 'store'])->name('booking.store');
        Route::get('bookings', [BookingController::class, 'index'])->name('booking.index');
        Route::get('bookings/{dat_phong}', [BookingController::class, 'show'])->name('booking.show');
    });
// ==================== PAYMENT ====================
Route::middleware(['auth'])->group(function () {
    Route::get('/payment/pending-payments', [PaymentController::class, 'pendingPayments'])->name('payment.pending_payments');
    Route::get('/payment/create', [PaymentController::class, 'createPayment'])->name('payment.create');
    Route::post('/payment/initiate', [PaymentController::class, 'initiateVNPay'])->name('payment.initiate');
    Route::post('/confirm-payment/{dat_phong_id}', [ConfirmPaymentController::class, 'confirm'])->name('api.confirm-payment');
    Route::get('/payment/callback', [PaymentController::class, 'handleVNPayCallback'])->name('payment.callback');
    Route::post('/payment/remaining/{dat_phong_id}', [PaymentController::class, 'initiateRemainingPayment'])
        ->name('payment.remaining');
    Route::get('/payment/remaining/callback', [PaymentController::class, 'handleRemainingCallback'])
        ->name('payment.remaining.callback');
    
});
Route::get('/payment/simulate-callback', [PaymentController::class, 'simulateCallback']);

// ==================== NOTIFICATIONS ====================
Route::middleware('auth')->group(function () {
    Route::get('notifications/{id}', [ThongBaoController::class, 'clientShow'])->name('notifications.show');
    Route::post('notifications/{id}/read', [ThongBaoController::class, 'markReadOnView'])->name('notifications.read');
    Route::get('notifications/{id}/modal', [ThongBaoController::class, 'clientModal'])->name('notifications.modal');

    Route::prefix('api/notifications')->group(function () {
        Route::get('/recent', [NotificationController::class, 'getRecent']);
        Route::get('/unread-count', [NotificationController::class, 'getUnreadCount']);
        Route::get('/{id}', [NotificationController::class, 'show']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    });
});

// ==================== TEST ====================
Route::get('/test-notifications', function () {
    $user = Auth::user();
    if (!$user) {
        return response()->json(['error' => 'Not authenticated']);
    }

    $unreadCount = \App\Models\ThongBao::where('nguoi_nhan_id', $user->id)
        ->where('kenh', 'in_app')
        ->where('trang_thai', '!=', 'read')
        ->count();

    $recentNotifications = \App\Models\ThongBao::where('nguoi_nhan_id', $user->id)
        ->where('kenh', 'in_app')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();

    return response()->json([
        'user' => $user->name,
        'user_id' => $user->id,
        'role' => $user->vai_tro,
        'unread_count' => $unreadCount,
        'recent_notifications' => $recentNotifications
    ]);
})->middleware('auth');

Route::get('/test-admin-notifications', function () {
    $user = Auth::user();
    if (!$user) {
        return response()->json(['error' => 'Not authenticated']);
    }

    if ($user->vai_tro !== 'admin') {
        return response()->json(['error' => 'Admin access required']);
    }

    $unreadCount = \App\Models\ThongBao::where('nguoi_nhan_id', $user->id)
        ->where('kenh', 'in_app')
        ->where('trang_thai', '!=', 'read')
        ->count();

    $recentNotifications = \App\Models\ThongBao::where('nguoi_nhan_id', $user->id)
        ->where('kenh', 'in_app')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();

    return response()->json([
        'user' => $user->name,
        'user_id' => $user->id,
        'role' => $user->vai_tro,
        'unread_count' => $unreadCount,
        'recent_notifications' => $recentNotifications
    ]);
})->middleware(['auth', 'admin']);

// ==================== AUTH ====================
require __DIR__ . '/auth.php';
