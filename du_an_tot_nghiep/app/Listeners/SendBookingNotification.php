<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Events\NotificationCreated;
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
        
        \Illuminate\Support\Facades\Log::info("SendBookingNotification listener triggered", [
            'booking_id' => $booking->id,
            'booking_code' => $booking->ma_dat_phong,
            'customer_id' => $booking->nguoi_dung_id,
            'listener_id' => uniqid()
        ]);
        
        // Kiểm tra xem đã có thông báo cho booking này chưa (trong 1 phút gần đây)
        $existingNotification = ThongBao::where('ten_template', 'booking_created')
            ->whereJsonContains('payload->booking_id', $booking->id)
            ->where('created_at', '>=', now()->subMinutes(1))
            ->first();
            
        if ($existingNotification) {
            \Illuminate\Support\Facades\Log::info("Booking notification already exists", [
                'booking_id' => $booking->id,
                'existing_notification_id' => $existingNotification->id
            ]);
            return; // Không tạo thông báo mới
        }
        
        // Notification for customer
        $customerNotification = ThongBao::create([
            'nguoi_nhan_id' => $booking->nguoi_dung_id,
            'kenh' => 'in_app',
            'ten_template' => 'booking_created',
            'payload' => [
                'title' => 'Đặt phòng thành công',
                'message' => "Bạn đã đặt phòng thành công với mã tham chiếu: {$booking->ma_dat_phong}",
                'link' => "/account/bookings/{$booking->id}",
                'booking_id' => $booking->id,
            ],
            'trang_thai' => 'pending',
            'so_lan_thu' => 0,
        ]);

        \Illuminate\Support\Facades\Log::info("Customer notification created", [
            'customer_id' => $booking->nguoi_dung_id,
            'notification_id' => $customerNotification->id
        ]);

        // Broadcast notification to customer
        broadcast(new NotificationCreated($customerNotification));

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

        \Illuminate\Support\Facades\Log::info("Staff found for booking notification", [
            'staff_count' => $staffUsers->count(),
            'staff_ids' => $staffUsers->pluck('id')->toArray()
        ]);

        foreach ($staffUsers as $staff) {
            $staffNotification = ThongBao::create([
                'nguoi_nhan_id' => $staff->id,
                'kenh' => 'in_app',
                'ten_template' => 'new_booking_alert',
                'payload' => [
                    'title' => 'Đơn đặt phòng mới',
                    'message' => "Có đơn đặt phòng mới từ {$booking->nguoiDung->name} - Mã: {$booking->ma_dat_phong}",
                    'link' => "/admin/dat-phong/{$booking->id}",
                    'booking_id' => $booking->id,
                    'customer_name' => $booking->nguoiDung->name,
                ],
                'trang_thai' => 'pending',
                'so_lan_thu' => 0,
            ]);

            \Illuminate\Support\Facades\Log::info("Staff notification created", [
                'staff_id' => $staff->id,
                'staff_name' => $staff->name,
                'notification_id' => $staffNotification->id
            ]);

            // Broadcast notification to staff
            broadcast(new NotificationCreated($staffNotification));

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

