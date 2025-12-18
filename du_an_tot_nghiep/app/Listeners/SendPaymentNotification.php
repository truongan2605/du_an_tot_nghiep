<?php

namespace App\Listeners;

use App\Events\PaymentSuccess;
use App\Events\NotificationCreated;
use App\Models\ThongBao;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use App\Mail\ThongBaoEmail;

class SendPaymentNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(PaymentSuccess $event): void
    {
        $booking = $event->booking;
        $transaction = $event->transaction;
        
        // Notification for customer
        $customerNotification = ThongBao::create([
            'nguoi_nhan_id' => $booking->nguoi_dung_id,
            'kenh' => 'in_app',
            'ten_template' => 'payment_success',
            'payload' => [
                'title' => 'Thanh toán thành công',
                'message' => "Bạn đã thanh toán thành công cho đơn đặt phòng {$booking->ma_tham_chieu}. Số tiền: " . number_format($transaction->so_tien, 0, ',', '.') . " VNĐ",
                'link' => "/account/bookings/{$booking->id}",
                'booking_id' => $booking->id,
                'amount' => $transaction->so_tien,
            ],
            'trang_thai' => 'pending',
            'so_lan_thu' => 0,
        ]);

        // Broadcast notification to customer
        broadcast(new NotificationCreated($customerNotification));

        // Cập nhật trạng thái thông báo in-app
        $customerNotification->update([
            'trang_thai' => 'sent',
            'so_lan_thu' => 1,
            'lan_thu_cuoi' => now(),
        ]);

        // Gửi email về Gmail (gửi trực tiếp, đồng bộ)
        try {
            $user = User::find($booking->nguoi_dung_id);
            if ($user && $user->email) {
                Mail::to($user->email)->send(new ThongBaoEmail($customerNotification));
                \Illuminate\Support\Facades\Log::info('Payment notification email sent to customer', [
                    'user_id' => $user->id,
                    'booking_id' => $booking->id,
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to send payment notification email to customer', [
                'user_id' => $user?->id,
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
            // Không cập nhật trạng thái thành 'failed' vì thông báo in-app đã thành công
        }

        // Notification for accounting staff/manager
        $accountingUsers = User::whereIn('vai_tro', ['admin', 'nhan_vien'])
            ->where('is_active', true)
            ->get();

        foreach ($accountingUsers as $staff) {
            $staffNotification = ThongBao::create([
                'nguoi_nhan_id' => $staff->id,
                'kenh' => 'in_app',
                'ten_template' => 'payment_received',
                'payload' => [
                    'title' => 'Nhận thanh toán mới',
                    'message' => "Khách {$booking->nguoiDung->name} đã thanh toán đơn #{$booking->ma_tham_chieu}. Số tiền: " . number_format($transaction->so_tien, 0, ',', '.') . " VNĐ",
                    'link' => "/staff/bookings/{$booking->id}",
                    'booking_id' => $booking->id,
                    'transaction_id' => $transaction->id,
                    'customer_name' => $booking->nguoiDung->name,
                    'amount' => $transaction->so_tien,
                ],
                'trang_thai' => 'pending',
                'so_lan_thu' => 0,
            ]);

            // Broadcast notification to staff
            broadcast(new NotificationCreated($staffNotification));

            // Cập nhật trạng thái thông báo in-app
            $staffNotification->update([
                'trang_thai' => 'sent',
                'so_lan_thu' => 1,
                'lan_thu_cuoi' => now(),
            ]);

            // Gửi email về Gmail (gửi trực tiếp, đồng bộ)
            try {
                if ($staff->email) {
                    Mail::to($staff->email)->send(new ThongBaoEmail($staffNotification));
                    \Illuminate\Support\Facades\Log::info('Payment notification email sent to staff', [
                        'staff_id' => $staff->id,
                        'booking_id' => $booking->id,
                    ]);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to send payment notification email to staff', [
                    'staff_id' => $staff->id,
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
                // Không cập nhật trạng thái thành 'failed' vì thông báo in-app đã thành công
            }
        }
    }
}
