<?php

namespace App\Listeners;

use App\Events\ReminderCheckin;
use App\Models\ThongBao;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use App\Mail\ThongBaoEmail;

class SendReminderNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ReminderCheckin $event): void
    {
        $booking = $event->booking;
        
        // Notification for customer
        $customerNotification = ThongBao::create([
            'nguoi_nhan_id' => $booking->nguoi_dung_id,
            'kenh' => 'in_app',
            'ten_template' => 'checkin_reminder',
            'payload' => [
                'title' => 'Nhắc nhở check-in',
                'message' => "Ngày mai bạn sẽ check-in lúc 14:00. Đơn đặt phòng: {$booking->ma_tham_chieu}",
                'link' => "/account/bookings/{$booking->id}",
                'booking_id' => $booking->id,
                'checkin_time' => '14:00',
                'checkin_date' => $booking->ngay_nhan_phong->format('d/m/Y'),
            ],
            'trang_thai' => 'pending',
            'so_lan_thu' => 0,
        ]);

        // Send email to customer
        try {
            $user = User::find($booking->nguoi_dung_id);
            if ($user && $user->email) {
                Mail::to($user->email)->send(new ThongBaoEmail($customerNotification));
                $customerNotification->update([
                    'trang_thai' => 'sent',
                    'so_lan_thu' => 1,
                    'lan_thu_cuoi' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            $customerNotification->update([
                'trang_thai' => 'failed',
                'so_lan_thu' => 1,
                'lan_thu_cuoi' => now(),
            ]);
        }

        // Notification for reception staff
        $receptionUsers = User::whereIn('vai_tro', ['admin', 'nhan_vien'])
            ->where('is_active', true)
            ->get();

        foreach ($receptionUsers as $staff) {
            $staffNotification = ThongBao::create([
                'nguoi_nhan_id' => $staff->id,
                'kenh' => 'in_app',
                'ten_template' => 'checkin_reminder_staff',
                'payload' => [
                    'title' => 'Nhắc nhở check-in cho khách',
                    'message' => "Ngày mai có khách {$booking->nguoiDung->name} check-in lúc 14:00. Đơn: {$booking->ma_tham_chieu}",
                    'link' => "/admin/dat-phong/{$booking->id}",
                    'booking_id' => $booking->id,
                    'customer_name' => $booking->nguoiDung->name,
                    'checkin_time' => '14:00',
                    'checkin_date' => $booking->ngay_nhan_phong->format('d/m/Y'),
                ],
                'trang_thai' => 'pending',
                'so_lan_thu' => 0,
            ]);

            // Send email to staff
            try {
                if ($staff->email) {
                    Mail::to($staff->email)->send(new ThongBaoEmail($staffNotification));
                    $staffNotification->update([
                        'trang_thai' => 'sent',
                        'so_lan_thu' => 1,
                        'lan_thu_cuoi' => now(),
                    ]);
                }
            } catch (\Throwable $e) {
                $staffNotification->update([
                    'trang_thai' => 'failed',
                    'so_lan_thu' => 1,
                    'lan_thu_cuoi' => now(),
                ]);
            }
        }
    }
}
