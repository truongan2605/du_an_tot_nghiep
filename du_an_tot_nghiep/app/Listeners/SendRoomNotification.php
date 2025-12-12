<?php

namespace App\Listeners;

use App\Events\RoomCreated;
use App\Events\RoomUpdated;
use App\Events\NotificationCreated;
use App\Models\ThongBao;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendRoomNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(RoomCreated|RoomUpdated $event): void
    {
        try {
            $room = $event->room;
            $eventType = $event instanceof RoomCreated ? 'created' : 'updated';
            
            Log::info("SendRoomNotification listener triggered", [
                'room_id' => $room->id,
                'room_code' => $room->ma_phong,
                'event_type' => $eventType,
                'listener_id' => uniqid()
            ]);
            
            // Kiểm tra xem đã có thông báo cho phòng này chưa (trong 1 phút gần đây)
            $existingNotification = ThongBao::where('ten_template', 'room_' . $eventType)
                ->whereJsonContains('payload->room_id', $room->id)
                ->where('created_at', '>=', now()->subMinutes(1)) // Trong 1 phút gần đây
                ->first();
                
            if ($existingNotification) {
                Log::info("Notification already exists for this room event", [
                    'room_id' => $room->id,
                    'existing_notification_id' => $existingNotification->id,
                    'event_type' => $eventType
                ]);
                return; // Không tạo thông báo mới
            }
            
            // Lấy danh sách admin và nhân viên để gửi thông báo
            $staff = User::whereIn('vai_tro', ['admin', 'nhan_vien'])
                ->where('is_active', true)
                ->get();

            Log::info("Staff found for notification", [
                'staff_count' => $staff->count(),
                'staff_ids' => $staff->pluck('id')->toArray()
            ]);

            Log::info("Sending notification to all staff", [
                'total_staff' => $staff->count(),
                'staff_details' => $staff->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'role' => $user->vai_tro
                    ];
                })->toArray()
            ]);

            foreach ($staff as $user) {
                $notification = ThongBao::create([
                    'nguoi_nhan_id' => $user->id,
                    'kenh' => 'in_app',
                    'ten_template' => 'room_' . $eventType,
                    'payload' => [
                        'title' => $eventType === 'created' ? 'Phòng mới được thêm' : 'Phòng được cập nhật',
                        'message' => sprintf(
                            'Phòng %s (%s) đã được %s. Giá: %s VNĐ',
                            $room->ma_phong,
                            $room->name ?? 'Không có tên',
                            $eventType === 'created' ? 'thêm mới' : 'cập nhật',
                            number_format($room->gia_cuoi_cung ?? 0)
                        ),
                        'link' => '/admin/phong',
                        'room_id' => $room->id,
                        'room_code' => $room->ma_phong,
                    ],
                    'trang_thai' => 'pending',
                    'so_lan_thu' => 0,
                ]);

                Log::info("Notification created for user", [
                    'user_id' => $user->id,
                    'notification_id' => $notification->id
                ]);

                // Broadcast notification for real-time updates
                broadcast(new NotificationCreated($notification));
            }

            Log::info("Room {$eventType} notification sent", [
                'room_id' => $room->id,
                'room_code' => $room->ma_phong,
                'staff_count' => $staff->count()
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send room {$eventType} notification", [
                'room_id' => $event->room->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }
}