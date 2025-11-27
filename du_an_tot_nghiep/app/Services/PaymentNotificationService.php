<?php

namespace App\Services;

use App\Events\NotificationCreated;
use App\Models\DatPhong;
use App\Models\GiaoDich;
use App\Models\ThongBao;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ThongBaoEmail;

class PaymentNotificationService
{
    /**
     * Gửi thông báo khi thanh toán tiền cọc thành công
     */
    public function sendDepositPaymentNotification(DatPhong $booking, GiaoDich $transaction): void
    {
        try {
            // Thông báo cho khách hàng
            $customerNotification = ThongBao::create([
                'nguoi_nhan_id' => $booking->nguoi_dung_id,
                'kenh' => 'in_app',
                'ten_template' => 'deposit_payment_success',
                'payload' => [
                    'title' => 'Thanh toán tiền cọc thành công',
                    'message' => "Bạn đã thanh toán tiền cọc thành công cho đơn đặt phòng {$booking->ma_tham_chieu}. Số tiền cọc: " . number_format($transaction->so_tien, 0, ',', '.') . " VNĐ",
                    'link' => "/account/bookings/{$booking->id}",
                    'booking_id' => $booking->id,
                    'amount' => $transaction->so_tien,
                    'payment_type' => 'deposit',
                ],
                'trang_thai' => 'pending',
                'so_lan_thu' => 0,
            ]);

            // Broadcast notification to customer
            broadcast(new NotificationCreated($customerNotification));

            // Thông báo in-app đã thành công, đánh dấu là 'sent'
            // Email chỉ là bổ sung, không ảnh hưởng đến trạng thái thông báo in-app
            $customerNotification->update([
                'trang_thai' => 'sent',
                'so_lan_thu' => 1,
                'lan_thu_cuoi' => now(),
            ]);

            // Gửi email cho khách hàng (nếu có lỗi email, chỉ log, không đổi trạng thái)
            $user = User::find($booking->nguoi_dung_id);
            if ($user && $user->email) {
                try {
                    Mail::to($user->email)->send(new ThongBaoEmail($customerNotification));
                    Log::info('Deposit payment email sent to customer', [
                        'user_id' => $user->id,
                        'booking_id' => $booking->id,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to send deposit payment email to customer (notification still sent)', [
                        'user_id' => $user->id,
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage(),
                    ]);
                    // Không cập nhật trạng thái thành 'failed' vì thông báo in-app đã thành công
                }
            }

            // Thông báo cho nhân viên/admin
            $staffUsers = User::whereIn('vai_tro', ['admin', 'nhan_vien'])
                ->where('is_active', true)
                ->get();

            foreach ($staffUsers as $staff) {
                $customerName = $booking->nguoiDung->name ?? 'N/A';
                $staffNotification = ThongBao::create([
                    'nguoi_nhan_id' => $staff->id,
                    'kenh' => 'in_app',
                    'ten_template' => 'deposit_payment_received',
                    'payload' => [
                        'title' => 'Nhận thanh toán tiền cọc mới',
                        'message' => "Khách hàng {$customerName} đã thanh toán tiền cọc cho đơn #{$booking->ma_tham_chieu}. Số tiền: " . number_format($transaction->so_tien, 0, ',', '.') . " VNĐ",
                        'link' => "/admin/giao-dich/{$transaction->id}",
                        'booking_id' => $booking->id,
                        'transaction_id' => $transaction->id,
                        'customer_name' => $customerName,
                        'amount' => $transaction->so_tien,
                        'payment_type' => 'deposit',
                    ],
                    'trang_thai' => 'pending',
                    'so_lan_thu' => 0,
                ]);

                // Broadcast notification to staff
                broadcast(new NotificationCreated($staffNotification));

                // Thông báo in-app đã thành công, đánh dấu là 'sent'
                $staffNotification->update([
                    'trang_thai' => 'sent',
                    'so_lan_thu' => 1,
                    'lan_thu_cuoi' => now(),
                ]);

                // Gửi email cho nhân viên (nếu có lỗi email, chỉ log, không đổi trạng thái)
                if ($staff->email) {
                    try {
                        Mail::to($staff->email)->send(new ThongBaoEmail($staffNotification));
                        Log::info('Deposit payment email sent to staff', [
                            'staff_id' => $staff->id,
                            'booking_id' => $booking->id,
                        ]);
                    } catch (\Throwable $e) {
                        Log::warning('Failed to send deposit payment email to staff (notification still sent)', [
                            'staff_id' => $staff->id,
                            'booking_id' => $booking->id,
                            'error' => $e->getMessage(),
                        ]);
                        // Không cập nhật trạng thái thành 'failed' vì thông báo in-app đã thành công
                    }
                }
            }

            Log::info('Deposit payment notification sent', [
                'booking_id' => $booking->id,
                'transaction_id' => $transaction->id,
                'amount' => $transaction->so_tien,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send deposit payment notification', [
                'booking_id' => $booking->id,
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Gửi thông báo khi thanh toán tiền phòng thành công
     */
    public function sendRoomPaymentNotification(DatPhong $booking, GiaoDich $transaction): void
    {
        try {
            // Tính tổng đã thanh toán
            $totalPaid = $booking->giaoDichs()
                ->where('trang_thai', 'thanh_cong')
                ->sum('so_tien');
            
            $remaining = $booking->tong_tien - $totalPaid;
            $isFullyPaid = $remaining <= 0;

            // Thông báo cho khách hàng
            $customerNotification = ThongBao::create([
                'nguoi_nhan_id' => $booking->nguoi_dung_id,
                'kenh' => 'in_app',
                'ten_template' => 'room_payment_success',
                'payload' => [
                    'title' => $isFullyPaid ? 'Thanh toán đầy đủ thành công' : 'Thanh toán tiền phòng thành công',
                    'message' => $isFullyPaid 
                        ? "Bạn đã thanh toán đầy đủ cho đơn đặt phòng {$booking->ma_tham_chieu}. Tổng tiền: " . number_format($booking->tong_tien, 0, ',', '.') . " VNĐ"
                        : "Bạn đã thanh toán thêm " . number_format($transaction->so_tien, 0, ',', '.') . " VNĐ cho đơn đặt phòng {$booking->ma_tham_chieu}. Còn lại: " . number_format($remaining, 0, ',', '.') . " VNĐ",
                    'link' => "/account/bookings/{$booking->id}",
                    'booking_id' => $booking->id,
                    'amount' => $transaction->so_tien,
                    'total_paid' => $totalPaid,
                    'remaining' => $remaining,
                    'is_fully_paid' => $isFullyPaid,
                    'payment_type' => 'room',
                ],
                'trang_thai' => 'pending',
                'so_lan_thu' => 0,
            ]);

            // Broadcast notification to customer
            broadcast(new NotificationCreated($customerNotification));

            // Thông báo in-app đã thành công, đánh dấu là 'sent'
            $customerNotification->update([
                'trang_thai' => 'sent',
                'so_lan_thu' => 1,
                'lan_thu_cuoi' => now(),
            ]);

            // Gửi email cho khách hàng (nếu có lỗi email, chỉ log, không đổi trạng thái)
            $user = User::find($booking->nguoi_dung_id);
            if ($user && $user->email) {
                try {
                    Mail::to($user->email)->send(new ThongBaoEmail($customerNotification));
                    Log::info('Room payment email sent to customer', [
                        'user_id' => $user->id,
                        'booking_id' => $booking->id,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to send room payment email to customer (notification still sent)', [
                        'user_id' => $user->id,
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage(),
                    ]);
                    // Không cập nhật trạng thái thành 'failed' vì thông báo in-app đã thành công
                }
            }

            // Thông báo cho nhân viên/admin
            $staffUsers = User::whereIn('vai_tro', ['admin', 'nhan_vien'])
                ->where('is_active', true)
                ->get();

            foreach ($staffUsers as $staff) {
                $customerName = $booking->nguoiDung->name ?? 'N/A';
                $staffNotification = ThongBao::create([
                    'nguoi_nhan_id' => $staff->id,
                    'kenh' => 'in_app',
                    'ten_template' => 'room_payment_received',
                    'payload' => [
                        'title' => $isFullyPaid ? 'Thanh toán đầy đủ' : 'Nhận thanh toán tiền phòng',
                        'message' => $isFullyPaid
                            ? "Khách hàng {$customerName} đã thanh toán đầy đủ cho đơn #{$booking->ma_tham_chieu}. Tổng tiền: " . number_format($booking->tong_tien, 0, ',', '.') . " VNĐ"
                            : "Khách hàng {$customerName} đã thanh toán thêm " . number_format($transaction->so_tien, 0, ',', '.') . " VNĐ cho đơn #{$booking->ma_tham_chieu}. Còn lại: " . number_format($remaining, 0, ',', '.') . " VNĐ",
                        'link' => "/admin/giao-dich/{$transaction->id}",
                        'booking_id' => $booking->id,
                        'transaction_id' => $transaction->id,
                        'customer_name' => $customerName,
                        'amount' => $transaction->so_tien,
                        'total_paid' => $totalPaid,
                        'remaining' => $remaining,
                        'is_fully_paid' => $isFullyPaid,
                        'payment_type' => 'room',
                    ],
                    'trang_thai' => 'pending',
                    'so_lan_thu' => 0,
                ]);

                // Broadcast notification to staff
                broadcast(new NotificationCreated($staffNotification));

                // Thông báo in-app đã thành công, đánh dấu là 'sent'
                $staffNotification->update([
                    'trang_thai' => 'sent',
                    'so_lan_thu' => 1,
                    'lan_thu_cuoi' => now(),
                ]);

                // Gửi email cho nhân viên (nếu có lỗi email, chỉ log, không đổi trạng thái)
                if ($staff->email) {
                    try {
                        Mail::to($staff->email)->send(new ThongBaoEmail($staffNotification));
                        Log::info('Room payment email sent to staff', [
                            'staff_id' => $staff->id,
                            'booking_id' => $booking->id,
                        ]);
                    } catch (\Throwable $e) {
                        Log::warning('Failed to send room payment email to staff (notification still sent)', [
                            'staff_id' => $staff->id,
                            'booking_id' => $booking->id,
                            'error' => $e->getMessage(),
                        ]);
                        // Không cập nhật trạng thái thành 'failed' vì thông báo in-app đã thành công
                    }
                }
            }

            Log::info('Room payment notification sent', [
                'booking_id' => $booking->id,
                'transaction_id' => $transaction->id,
                'amount' => $transaction->so_tien,
                'is_fully_paid' => $isFullyPaid,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send room payment notification', [
                'booking_id' => $booking->id,
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Gửi thông báo khi check-in thành công
     */
    public function sendCheckinNotification(DatPhong $booking): void
    {
        try {
            $customerName = $booking->nguoiDung->name ?? 'N/A';
            $checkinTime = $booking->checked_in_at ? $booking->checked_in_at->format('H:i d/m/Y') : now()->format('H:i d/m/Y');

            // Thông báo cho khách hàng
            $customerNotification = ThongBao::create([
                'nguoi_nhan_id' => $booking->nguoi_dung_id,
                'kenh' => 'in_app',
                'ten_template' => 'checkin_success',
                'payload' => [
                    'title' => 'Check-in thành công',
                    'message' => "Bạn đã check-in thành công cho đơn đặt phòng {$booking->ma_tham_chieu} lúc {$checkinTime}. Chúc bạn có một kỳ nghỉ vui vẻ!",
                    'link' => "/account/bookings/{$booking->id}",
                    'booking_id' => $booking->id,
                    'checkin_time' => $checkinTime,
                ],
                'trang_thai' => 'pending',
                'so_lan_thu' => 0,
            ]);

            // Broadcast notification to customer
            broadcast(new NotificationCreated($customerNotification));

            $customerNotification->update([
                'trang_thai' => 'sent',
                'so_lan_thu' => 1,
                'lan_thu_cuoi' => now(),
            ]);

            // Gửi email cho khách hàng
            $user = User::find($booking->nguoi_dung_id);
            if ($user && $user->email) {
                try {
                    Mail::to($user->email)->send(new ThongBaoEmail($customerNotification));
                    Log::info('Checkin email sent to customer', [
                        'user_id' => $user->id,
                        'booking_id' => $booking->id,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to send checkin email to customer (notification still sent)', [
                        'user_id' => $user->id,
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Thông báo cho nhân viên/admin
            $staffUsers = User::whereIn('vai_tro', ['admin', 'nhan_vien'])
                ->where('is_active', true)
                ->get();

            foreach ($staffUsers as $staff) {
                $staffNotification = ThongBao::create([
                    'nguoi_nhan_id' => $staff->id,
                    'kenh' => 'in_app',
                    'ten_template' => 'checkin_completed',
                    'payload' => [
                        'title' => 'Khách hàng đã check-in',
                        'message' => "Khách hàng {$customerName} đã check-in cho đơn #{$booking->ma_tham_chieu} lúc {$checkinTime}",
                        'link' => "/staff/bookings/{$booking->id}",
                        'booking_id' => $booking->id,
                        'customer_name' => $customerName,
                        'checkin_time' => $checkinTime,
                    ],
                    'trang_thai' => 'pending',
                    'so_lan_thu' => 0,
                ]);

                broadcast(new NotificationCreated($staffNotification));

                $staffNotification->update([
                    'trang_thai' => 'sent',
                    'so_lan_thu' => 1,
                    'lan_thu_cuoi' => now(),
                ]);

                if ($staff->email) {
                    try {
                        Mail::to($staff->email)->send(new ThongBaoEmail($staffNotification));
                        Log::info('Checkin email sent to staff', [
                            'staff_id' => $staff->id,
                            'booking_id' => $booking->id,
                        ]);
                    } catch (\Throwable $e) {
                        Log::warning('Failed to send checkin email to staff (notification still sent)', [
                            'staff_id' => $staff->id,
                            'booking_id' => $booking->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            Log::info('Checkin notification sent', [
                'booking_id' => $booking->id,
                'checkin_time' => $checkinTime,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send checkin notification', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Gửi thông báo khi checkout thành công
     */
    public function sendCheckoutNotification(DatPhong $booking, $hoaDonId = null): void
    {
        try {
            $customerName = $booking->nguoiDung->name ?? 'N/A';
            $checkoutTime = $booking->checkout_at ? $booking->checkout_at->format('H:i d/m/Y') : now()->format('H:i d/m/Y');
            $hoaDonText = $hoaDonId ? " (Hóa đơn #{$hoaDonId})" : '';

            // Thông báo cho khách hàng
            $customerNotification = ThongBao::create([
                'nguoi_nhan_id' => $booking->nguoi_dung_id,
                'kenh' => 'in_app',
                'ten_template' => 'checkout_success',
                'payload' => [
                    'title' => 'Checkout thành công',
                    'message' => "Bạn đã checkout thành công cho đơn đặt phòng {$booking->ma_tham_chieu} lúc {$checkoutTime}{$hoaDonText}. Cảm ơn bạn đã sử dụng dịch vụ!",
                    'link' => "/account/bookings/{$booking->id}",
                    'booking_id' => $booking->id,
                    'checkout_time' => $checkoutTime,
                    'hoa_don_id' => $hoaDonId,
                ],
                'trang_thai' => 'pending',
                'so_lan_thu' => 0,
            ]);

            broadcast(new NotificationCreated($customerNotification));

            $customerNotification->update([
                'trang_thai' => 'sent',
                'so_lan_thu' => 1,
                'lan_thu_cuoi' => now(),
            ]);

            // Gửi email cho khách hàng
            $user = User::find($booking->nguoi_dung_id);
            if ($user && $user->email) {
                try {
                    Mail::to($user->email)->send(new ThongBaoEmail($customerNotification));
                    Log::info('Checkout email sent to customer', [
                        'user_id' => $user->id,
                        'booking_id' => $booking->id,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to send checkout email to customer (notification still sent)', [
                        'user_id' => $user->id,
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Thông báo cho nhân viên/admin
            $staffUsers = User::whereIn('vai_tro', ['admin', 'nhan_vien'])
                ->where('is_active', true)
                ->get();

            foreach ($staffUsers as $staff) {
                $staffNotification = ThongBao::create([
                    'nguoi_nhan_id' => $staff->id,
                    'kenh' => 'in_app',
                    'ten_template' => 'checkout_completed',
                    'payload' => [
                        'title' => 'Khách hàng đã checkout',
                        'message' => "Khách hàng {$customerName} đã checkout cho đơn #{$booking->ma_tham_chieu} lúc {$checkoutTime}{$hoaDonText}",
                        'link' => "/staff/bookings/{$booking->id}",
                        'booking_id' => $booking->id,
                        'customer_name' => $customerName,
                        'checkout_time' => $checkoutTime,
                        'hoa_don_id' => $hoaDonId,
                    ],
                    'trang_thai' => 'pending',
                    'so_lan_thu' => 0,
                ]);

                broadcast(new NotificationCreated($staffNotification));

                $staffNotification->update([
                    'trang_thai' => 'sent',
                    'so_lan_thu' => 1,
                    'lan_thu_cuoi' => now(),
                ]);

                if ($staff->email) {
                    try {
                        Mail::to($staff->email)->send(new ThongBaoEmail($staffNotification));
                        Log::info('Checkout email sent to staff', [
                            'staff_id' => $staff->id,
                            'booking_id' => $booking->id,
                        ]);
                    } catch (\Throwable $e) {
                        Log::warning('Failed to send checkout email to staff (notification still sent)', [
                            'staff_id' => $staff->id,
                            'booking_id' => $booking->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            Log::info('Checkout notification sent', [
                'booking_id' => $booking->id,
                'checkout_time' => $checkoutTime,
                'hoa_don_id' => $hoaDonId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send checkout notification', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Gửi thông báo khi checkout sớm thành công
     */
    public function sendEarlyCheckoutNotification(DatPhong $booking, $hoaDonId = null, $earlyDays = 0, $refundAmount = 0): void
    {
        try {
            $customerName = $booking->nguoiDung->name ?? 'N/A';
            $checkoutTime = $booking->checkout_at ? $booking->checkout_at->format('H:i d/m/Y') : now()->format('H:i d/m/Y');
            $hoaDonText = $hoaDonId ? " (Hóa đơn #{$hoaDonId})" : '';
            
            // Tính ngày checkout dự kiến
            $expectedCheckoutDate = $booking->ngay_tra_phong ? Carbon::parse($booking->ngay_tra_phong)->format('d/m/Y') : 'N/A';
            
            $refundText = $refundAmount > 0 
                ? " Số tiền hoàn lại: " . number_format($refundAmount, 0, ',', '.') . " VNĐ."
                : " Không có khoản hoàn tiền.";

            // Thông báo cho khách hàng
            $customerNotification = ThongBao::create([
                'nguoi_nhan_id' => $booking->nguoi_dung_id,
                'kenh' => 'in_app',
                'ten_template' => 'early_checkout_success',
                'payload' => [
                    'title' => 'Checkout sớm thành công',
                    'message' => "Bạn đã checkout sớm {$earlyDays} ngày cho đơn đặt phòng {$booking->ma_tham_chieu} lúc {$checkoutTime}{$hoaDonText}. Ngày checkout dự kiến: {$expectedCheckoutDate}.{$refundText} Cảm ơn bạn đã sử dụng dịch vụ!",
                    'link' => "/account/bookings/{$booking->id}",
                    'booking_id' => $booking->id,
                    'checkout_time' => $checkoutTime,
                    'expected_checkout_date' => $expectedCheckoutDate,
                    'early_days' => $earlyDays,
                    'refund_amount' => $refundAmount,
                    'hoa_don_id' => $hoaDonId,
                    'is_early_checkout' => true,
                ],
                'trang_thai' => 'pending',
                'so_lan_thu' => 0,
            ]);

            broadcast(new NotificationCreated($customerNotification));

            $customerNotification->update([
                'trang_thai' => 'sent',
                'so_lan_thu' => 1,
                'lan_thu_cuoi' => now(),
            ]);

            // Gửi email cho khách hàng
            $user = User::find($booking->nguoi_dung_id);
            if ($user && $user->email) {
                try {
                    Mail::to($user->email)->send(new ThongBaoEmail($customerNotification));
                    Log::info('Early checkout email sent to customer', [
                        'user_id' => $user->id,
                        'booking_id' => $booking->id,
                        'early_days' => $earlyDays,
                        'refund_amount' => $refundAmount,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to send early checkout email to customer (notification still sent)', [
                        'user_id' => $user->id,
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Thông báo cho nhân viên/admin
            $staffUsers = User::whereIn('vai_tro', ['admin', 'nhan_vien'])
                ->where('is_active', true)
                ->get();

            foreach ($staffUsers as $staff) {
                $staffNotification = ThongBao::create([
                    'nguoi_nhan_id' => $staff->id,
                    'kenh' => 'in_app',
                    'ten_template' => 'early_checkout_completed',
                    'payload' => [
                        'title' => 'Khách hàng đã checkout sớm',
                        'message' => "Khách hàng {$customerName} đã checkout sớm {$earlyDays} ngày cho đơn #{$booking->ma_tham_chieu} lúc {$checkoutTime}{$hoaDonText}. Ngày checkout dự kiến: {$expectedCheckoutDate}.{$refundText}",
                        'link' => "/staff/bookings/{$booking->id}",
                        'booking_id' => $booking->id,
                        'customer_name' => $customerName,
                        'checkout_time' => $checkoutTime,
                        'expected_checkout_date' => $expectedCheckoutDate,
                        'early_days' => $earlyDays,
                        'refund_amount' => $refundAmount,
                        'hoa_don_id' => $hoaDonId,
                        'is_early_checkout' => true,
                    ],
                    'trang_thai' => 'pending',
                    'so_lan_thu' => 0,
                ]);

                broadcast(new NotificationCreated($staffNotification));

                $staffNotification->update([
                    'trang_thai' => 'sent',
                    'so_lan_thu' => 1,
                    'lan_thu_cuoi' => now(),
                ]);

                if ($staff->email) {
                    try {
                        Mail::to($staff->email)->send(new ThongBaoEmail($staffNotification));
                        Log::info('Early checkout email sent to staff', [
                            'staff_id' => $staff->id,
                            'booking_id' => $booking->id,
                            'early_days' => $earlyDays,
                            'refund_amount' => $refundAmount,
                        ]);
                    } catch (\Throwable $e) {
                        Log::warning('Failed to send early checkout email to staff (notification still sent)', [
                            'staff_id' => $staff->id,
                            'booking_id' => $booking->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            Log::info('Early checkout notification sent', [
                'booking_id' => $booking->id,
                'checkout_time' => $checkoutTime,
                'early_days' => $earlyDays,
                'refund_amount' => $refundAmount,
                'hoa_don_id' => $hoaDonId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send early checkout notification', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Gửi thông báo khi đã thanh toán toàn bộ
     */
    public function sendFullPaymentNotification(DatPhong $booking, GiaoDich $transaction = null): void
    {
        try {
            $totalPaid = $booking->giaoDichs()
                ->where('trang_thai', 'thanh_cong')
                ->sum('so_tien');
            
            $customerName = $booking->nguoiDung->name ?? 'N/A';

            // Thông báo cho khách hàng
            $customerNotification = ThongBao::create([
                'nguoi_nhan_id' => $booking->nguoi_dung_id,
                'kenh' => 'in_app',
                'ten_template' => 'full_payment_success',
                'payload' => [
                    'title' => 'Thanh toán toàn bộ thành công',
                    'message' => "Bạn đã thanh toán toàn bộ cho đơn đặt phòng {$booking->ma_tham_chieu}. Tổng tiền: " . number_format($booking->tong_tien, 0, ',', '.') . " VNĐ. Bạn có thể tiến hành check-in.",
                    'link' => "/account/bookings/{$booking->id}",
                    'booking_id' => $booking->id,
                    'total_amount' => $booking->tong_tien,
                    'total_paid' => $totalPaid,
                ],
                'trang_thai' => 'pending',
                'so_lan_thu' => 0,
            ]);

            broadcast(new NotificationCreated($customerNotification));

            $customerNotification->update([
                'trang_thai' => 'sent',
                'so_lan_thu' => 1,
                'lan_thu_cuoi' => now(),
            ]);

            // Gửi email cho khách hàng
            $user = User::find($booking->nguoi_dung_id);
            if ($user && $user->email) {
                try {
                    Mail::to($user->email)->send(new ThongBaoEmail($customerNotification));
                    Log::info('Full payment email sent to customer', [
                        'user_id' => $user->id,
                        'booking_id' => $booking->id,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to send full payment email to customer (notification still sent)', [
                        'user_id' => $user->id,
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Thông báo cho nhân viên/admin
            $staffUsers = User::whereIn('vai_tro', ['admin', 'nhan_vien'])
                ->where('is_active', true)
                ->get();

            foreach ($staffUsers as $staff) {
                $staffNotification = ThongBao::create([
                    'nguoi_nhan_id' => $staff->id,
                    'kenh' => 'in_app',
                    'ten_template' => 'full_payment_received',
                    'payload' => [
                        'title' => 'Khách hàng đã thanh toán toàn bộ',
                        'message' => "Khách hàng {$customerName} đã thanh toán toàn bộ cho đơn #{$booking->ma_tham_chieu}. Tổng tiền: " . number_format($booking->tong_tien, 0, ',', '.') . " VNĐ",
                        'link' => $transaction ? "/admin/giao-dich/{$transaction->id}" : "/staff/bookings/{$booking->id}",
                        'booking_id' => $booking->id,
                        'transaction_id' => $transaction ? $transaction->id : null,
                        'customer_name' => $customerName,
                        'total_amount' => $booking->tong_tien,
                        'total_paid' => $totalPaid,
                    ],
                    'trang_thai' => 'pending',
                    'so_lan_thu' => 0,
                ]);

                broadcast(new NotificationCreated($staffNotification));

                $staffNotification->update([
                    'trang_thai' => 'sent',
                    'so_lan_thu' => 1,
                    'lan_thu_cuoi' => now(),
                ]);

                if ($staff->email) {
                    try {
                        Mail::to($staff->email)->send(new ThongBaoEmail($staffNotification));
                        Log::info('Full payment email sent to staff', [
                            'staff_id' => $staff->id,
                            'booking_id' => $booking->id,
                        ]);
                    } catch (\Throwable $e) {
                        Log::warning('Failed to send full payment email to staff (notification still sent)', [
                            'staff_id' => $staff->id,
                            'booking_id' => $booking->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            Log::info('Full payment notification sent', [
                'booking_id' => $booking->id,
                'total_amount' => $booking->tong_tien,
                'total_paid' => $totalPaid,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send full payment notification', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

