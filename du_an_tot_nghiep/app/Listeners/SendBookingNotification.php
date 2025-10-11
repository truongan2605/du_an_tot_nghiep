<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Models\ThongBao;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use App\Mail\ThongBaoEmail;

class SendBookingNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(BookingCreated $event): void
    {
        $booking = $event->booking;
        
        // Notification for customer
        $customerNotification = ThongBao::create([
            'nguoi_nhan_id' => $booking->nguoi_dung_id,
            'kenh' => 'in_app',
            'ten_template' => 'booking_created',
            'payload' => [
                'title' => 'Đặt phòng thành công',
                'message' => "Bạn đã đặt phòng thành công với mã tham chiếu: {$booking->ma_tham_chieu}",
                'link' => "/account/bookings/{$booking->id}",
                'booking_id' => $booking->id,
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

        // Notification for staff (reception/manager)
        $staffUsers = User::whereIn('vai_tro', ['admin', 'nhan_vien'])
            ->where('is_active', true)
            ->get();

        foreach ($staffUsers as $staff) {
            $staffNotification = ThongBao::create([
                'nguoi_nhan_id' => $staff->id,
                'kenh' => 'in_app',
                'ten_template' => 'new_booking_alert',
                'payload' => [
                    'title' => 'Đơn đặt phòng mới',
                    'message' => "Có đơn đặt phòng mới từ {$booking->nguoiDung->name} - Mã: {$booking->ma_tham_chieu}",
                    'link' => "/admin/dat-phong/{$booking->id}",
                    'booking_id' => $booking->id,
                    'customer_name' => $booking->nguoiDung->name,
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

