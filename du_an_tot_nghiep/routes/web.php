<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ===== Controllers =====
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
use App\Http\Controllers\Admin\PhongVatDungController;
use App\Http\Controllers\CustomerNotificationController;
use App\Http\Controllers\InternalNotificationController;
use App\Http\Controllers\Admin\VatDungIncidentController;
use App\Http\Controllers\Client\BlogController as ClientBlog;
use App\Http\Controllers\Admin\Blog\TagController as AdminTag;
use App\Http\Controllers\Admin\PhongConsumptionController;
use App\Http\Controllers\Staff\VatDungIncidentController as StaffVatDungIncidentController;

// Vật dụng/phòng (mở rộng)
use App\Http\Controllers\Payment\ConfirmPaymentController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\BatchNotificationController;

// BLOG (Admin + Client)
use App\Http\Controllers\Admin\PhongVatDungInstanceController;
use App\Http\Controllers\Admin\Blog\CategoryController as AdminCategory;
use App\Http\Controllers\Admin\VatDungController as AdminVatDungController;
use App\Http\Controllers\Admin\TienNghiController as AdminTienNghiController;
use App\Http\Controllers\Admin\Blog\PostController as AdminPost;
use App\Http\Controllers\Staff\CheckoutController;

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
    ->middleware(['auth',  'role:nhan_vien|admin', App\Http\Middleware\AdminMiddleware::class])
    ->group(function () {
        // Tiện nghi
        Route::resource('tien-nghi', AdminTienNghiController::class);
        Route::patch('tien-nghi/{tienNghi}/toggle-active', [AdminTienNghiController::class, 'toggleActive'])
            ->name('tien-nghi.toggle-active');

        // Vật dụng
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

        // Tầng
        Route::resource('tang', TangController::class);

        // Người dùng
        Route::get('user/create', [UserController::class, 'create'])->name('user.create');
        Route::post('user', [UserController::class, 'store'])->name('user.store');
        Route::get('user', [UserController::class, 'index'])->name('user.index');
        Route::get('user/{user}', [UserController::class, 'show'])->name('user.show');
        Route::get('user/{user}/edit', [UserController::class, 'edit'])->name('user.edit');
        Route::put('user/{user}', [UserController::class, 'update'])->name('user.update');
        Route::patch('user/{user}/toggle', [UserController::class, 'toggleActive'])->name('user.toggle');

        // Nhân viên
        Route::resource('nhan-vien', NhanVienController::class);
        Route::patch('nhan-vien/{nhan_vien}/toggle', [NhanVienController::class, 'toggleActive'])->name('nhan-vien.toggle');
        // ---- Voucher ----
        Route::resource('voucher', VoucherController::class);
        Route::patch('voucher/{voucher}/toggle-active', [VoucherController::class, 'toggleActive'])->name('voucher.toggle-active');

        // Giường
        Route::resource('bed-types', BedTypeController::class);

        // Thông báo (Notifications)
        Route::get('customer-notifications', [CustomerNotificationController::class, 'index'])->name('customer-notifications.index');
        Route::get('customer-notifications/create', [CustomerNotificationController::class, 'create'])->name('customer-notifications.create');
        Route::post('customer-notifications', [CustomerNotificationController::class, 'store'])->name('customer-notifications.store');
        Route::get('customer-notifications/{notification}', [CustomerNotificationController::class, 'show'])->name('customer-notifications.show');
        Route::get('customer-notifications/{notification}/edit', [CustomerNotificationController::class, 'edit'])->name('customer-notifications.edit');
        Route::put('customer-notifications/{notification}', [CustomerNotificationController::class, 'update'])->name('customer-notifications.update');
        Route::delete('customer-notifications/{notification}', [CustomerNotificationController::class, 'destroy'])->name('customer-notifications.destroy');
        Route::post('customer-notifications/{notification}/resend', [CustomerNotificationController::class, 'resend'])->name('customer-notifications.resend');

        Route::resource('internal-notifications', InternalNotificationController::class)
            ->parameters(['internal-notifications' => 'notification']);
        Route::post('internal-notifications/{notification}/resend', [InternalNotificationController::class, 'resend'])
            ->name('internal-notifications.resend');

        Route::resource('admin-notifications', AdminNotificationController::class);
        Route::get('batch-notifications', [BatchNotificationController::class, 'index'])->name('batch-notifications.index');

        // ===== Vật dụng trong phòng / tiêu hao / instance / sự cố =====
        // Gán/bỏ gán vật dụng bền theo phòng
        Route::post('phong/{phong}/vat-dung/sync', [PhongVatDungController::class, 'sync'])->name('phong.vatdung.sync');
        Route::post('phong/{phong}/vat-dung/remove/{vat_dung}', [PhongVatDungController::class, 'remove'])->name('phong.vatdung.remove');

        // Đồ ăn/đồ tiêu hao theo booking (consumptions)
        Route::post('phong/consumptions', [PhongConsumptionController::class, 'store'])->name('phong.consumptions.store');
        Route::put('phong/consumptions/{consumption}', [PhongConsumptionController::class, 'update'])->name('phong.consumptions.update');
        Route::delete('phong/consumptions/{consumption}', [PhongConsumptionController::class, 'destroy'])->name('phong.consumptions.destroy');
        Route::post('phong/consumptions/{consumption}/mark-consumed', [PhongConsumptionController::class, 'markConsumed'])->name('phong.consumptions.markConsumed');
        Route::post('phong/consumptions/store-and-bill/{phong}', [PhongConsumptionController::class, 'storeAndBill'])->name('phong.consumptions.store_and_bill');

        // Instances & incidents cho vật dụng bền
        Route::post('phong/{phong}/instances', [VatDungIncidentController::class, 'createInstance'])->name('phong.instances.store');
        Route::post('phong/incidents', [VatDungIncidentController::class, 'store'])->name('phong.incidents.store');
        Route::put('phong/incidents/{incident}', [VatDungIncidentController::class, 'update'])->name('phong.incidents.update');
        Route::delete('phong/incidents/{incident}', [VatDungIncidentController::class, 'destroy'])->name('phong.incidents.destroy');

        // Thiết lập đồ ăn theo phòng/booking
        Route::get('phong/{phong}/food-setup', [PhongVatDungController::class, 'showFoodSetup'])->name('phong.food-setup');
        Route::post('phong/{phong}/food-reserve', [PhongVatDungController::class, 'reserveFood'])->name('phong.food-reserve');
        Route::post('/phong/{phong}/food-reserve-item', [PhongVatDungController::class, 'storeItem'])->name('admin.phong.food-reserve-item.store');
        Route::put('/phong/{phong}/food-reserve-item/{consumption}', [PhongVatDungController::class, 'updateItem'])->name('admin.phong.food-reserve-item.update');
        Route::delete('/phong/{phong}/food-reserve-item/{vatDungId}', [PhongVatDungController::class, 'destroyItem'])->name('admin.phong.food-reserve-item.destroy');

        // Quản lý instance của vật dụng bền theo phòng
        Route::get('phong/{phong}/vat-dung-instances', [PhongVatDungInstanceController::class, 'index'])->name('phong.vatdung.instances.index');
        Route::post('phong/{phong}/vat-dung-instances', [PhongVatDungInstanceController::class, 'store'])->name('phong.vatdung.instances.store');
        Route::patch('phong/vat-dung-instances/{instance}', [PhongVatDungInstanceController::class, 'update'])->name('phong.vatdung.instances.update');
        Route::patch('phong/vat-dung-instances/{instance}/status', [PhongVatDungInstanceController::class, 'updateStatus'])->name('phong.vatdung.instances.update-status');
        Route::delete('phong/vat-dung-instances/{instance}', [PhongVatDungInstanceController::class, 'destroy'])->name('phong.vatdung.instances.destroy');
        Route::post('phong/vat-dung-instances/{instance}/mark-lost', [PhongVatDungInstanceController::class, 'markLost'])->name('phong.vatdung.instances.mark-lost');

        // ==================== BLOG (ADMIN) ====================
        Route::prefix('blog')->name('blog.')->group(function () {
            Route::get('posts/trash', [AdminPost::class, 'trash'])->name('posts.trash');
            Route::post('posts/{id}/restore', [AdminPost::class, 'restore'])->name('posts.restore');
            Route::delete('posts/{id}/force', [AdminPost::class, 'forceDelete'])->name('posts.force');

            Route::resource('posts', AdminPost::class)->parameters(['posts' => 'post']);
            Route::resource('categories', AdminCategory::class)->parameters(['categories' => 'category']);
            Route::resource('tags', AdminTag::class)->parameters(['tags' => 'tag']);
        });
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


// ==================== STAFF + ADMIN ====================
Route::middleware(['auth', 'role:nhan_vien|admin'])->group(function () {
    Route::post('/phong/consumptions', [PhongConsumptionController::class, 'store'])
        ->name('phong.consumptions.store');

    Route::put('/phong/consumptions/{consumption}', [PhongConsumptionController::class, 'update'])
        ->name('phong.consumptions.update');

    Route::delete('/phong/consumptions/{consumption}', [PhongConsumptionController::class, 'destroy'])
        ->name('phong.consumptions.destroy');

    Route::post('/phong/consumptions/{consumption}/mark-consumed', [PhongConsumptionController::class, 'markConsumed'])
        ->name('phong.consumptions.markConsumed');

    Route::post('/phong/consumptions/store-and-bill/{phong}', [PhongConsumptionController::class, 'storeAndBill'])
        ->name('phong.consumptions.store_and_bill');

    // staff booking incidents
    Route::post('/bookings/{booking}/incidents', [StaffVatDungIncidentController::class, 'store'])
        ->name('bookings.incidents.store');

    Route::patch('/bookings/{booking}/incidents/{incident}', [StaffVatDungIncidentController::class, 'update'])
        ->name('bookings.incidents.update');

    Route::delete('/bookings/{booking}/incidents/{incident}', [StaffVatDungIncidentController::class, 'destroy'])
        ->name('bookings.incidents.destroy');

    Route::get('/staff/bookings/{booking}/checkout', [CheckoutController::class, 'showCheckoutForm'])
        ->name('staff.bookings.checkout.show');

    Route::post('/staff/bookings/{booking}/checkout', [CheckoutController::class, 'processCheckout'])
        ->name('staff.bookings.checkout.process');

    Route::post('/staff/bookings/{booking}/invoices/{hoaDon}/confirm', [CheckoutController::class, 'confirmPayment'])
        ->name('staff.bookings.invoices.confirm');
});


// ==================== ACCOUNT (client profile area) ====================
Route::middleware('auth')
    ->prefix('account')
    ->name('account.')
    ->group(function () {
        Route::get('settings', function () {
            return view('account.profile');
        })->name('settings');
        Route::patch('settings', [ProfileController::class, 'update'])->name('settings.update');

        // Wishlist
        Route::get('wishlist', [WishlistController::class, 'index'])->name('wishlist');
        Route::post('wishlist/toggle/{phong}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
        Route::delete('wishlist/{id}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');
        Route::post('wishlist/clear', [WishlistController::class, 'clear'])->name('wishlist.clear');

        // Booking cá nhân
        Route::get('booking/{phong}/create', [BookingController::class, 'create'])->name('booking.create');
        Route::post('booking', [BookingController::class, 'store'])->name('booking.store');
        Route::get('bookings', [BookingController::class, 'index'])->name('booking.index');
        Route::get('bookings/{dat_phong}', [BookingController::class, 'show'])->name('booking.show');
    });

// ==================== BLOG (CLIENT) ====================
Route::get('/blog', [ClientBlog::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [ClientBlog::class, 'show'])->name('blog.show');

// ==================== PAYMENT ====================
Route::middleware(['auth'])->group(function () {
    Route::get('/payment/pending-payments', [PaymentController::class, 'pendingPayments'])->name('payment.pending_payments');
    Route::get('/payment/create', [PaymentController::class, 'createPayment'])->name('payment.create');
    Route::post('/payment/initiate', [PaymentController::class, 'initiateVNPay'])->name('payment.initiate');
    Route::post('/confirm-payment/{dat_phong_id}', [ConfirmPaymentController::class, 'confirm'])->name('api.confirm-payment');
    Route::get('/payment/callback', [PaymentController::class, 'handleVNPayCallback'])->name('payment.callback');

    // Thanh toán phần còn lại
    Route::post('/payment/remaining/{dat_phong_id}', [PaymentController::class, 'initiateRemainingPayment'])->name('payment.remaining');
    Route::get('/payment/remaining/callback', [PaymentController::class, 'handleRemainingCallback'])->name('payment.remaining.callback');
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
    if (!$user) return response()->json(['error' => 'Not authenticated']);

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
    if (!$user) return response()->json(['error' => 'Not authenticated']);
    if ($user->vai_tro !== 'admin') return response()->json(['error' => 'Admin access required']);

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
