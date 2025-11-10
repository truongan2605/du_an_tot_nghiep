<?php

namespace App\Services;

use App\Events\NotificationCreated;
use App\Models\DatPhong;
use App\Models\GiaoDich;
use App\Models\ThongBao;
use App\Models\User;
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
}

