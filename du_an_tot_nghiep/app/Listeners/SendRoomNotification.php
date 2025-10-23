<?php

namespace App\Listeners;

use App\Events\RoomCreated;
use App\Events\RoomUpdated;
use App\Models\ThongBao;
use App\Models\User;
use App\Jobs\SendBatchNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendRoomNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        try {
            if ($event instanceof RoomCreated) {
                $this->handleRoomCreated($event);
            } elseif ($event instanceof RoomUpdated) {
                $this->handleRoomUpdated($event);
            }
        } catch (\Exception $e) {
            Log::error('Room notification failed', [
                'event' => get_class($event),
                'error' => $e->getMessage(),
                'room_id' => $event->phong->id ?? null
            ]);
        }
    }

    /**
     * Handle room created event
     */
    private function handleRoomCreated(RoomCreated $event): void
    {
        $phong = $event->phong;
        $user = $event->user;

        Log::info('Room created notification started', [
            'room_id' => $phong->id,
            'room_name' => $phong->ten_phong,
            'user_id' => $user->id,
            'user_name' => $user->name
        ]);

        // Get admin and staff users
        $adminStaffIds = User::whereIn('vai_tro', ['admin', 'nhan_vien'])
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        Log::info('Admin/Staff users found', [
            'count' => count($adminStaffIds),
            'user_ids' => $adminStaffIds
        ]);

        if (empty($adminStaffIds)) {
            Log::warning('No admin or staff users found for room notification');
            return;
        }

        // Create notification data
        $notificationData = [
            'kenh' => 'in_app',
            'ten_template' => 'room_created',
            'payload' => json_encode([
                'title' => 'Phòng mới được tạo',
                'message' => "Phòng {$phong->ten_phong} (Tầng {$phong->tang->ten_tang}) đã được tạo bởi {$user->name}",
                'link' => "/admin/phong/{$phong->id}",
                'type' => 'room_created',
                'room_id' => $phong->id,
                'room_name' => $phong->ten_phong,
                'floor' => $phong->tang->ten_tang,
                'created_by' => $user->name
            ])
        ];

        // Dispatch batch notification job
        SendBatchNotificationJob::dispatch($notificationData, $adminStaffIds, 'room_created_' . $phong->id);

        Log::info('Room created notification dispatched', [
            'room_id' => $phong->id,
            'room_name' => $phong->ten_phong,
            'recipients' => count($adminStaffIds),
            'created_by' => $user->name
        ]);
    }

    /**
     * Handle room updated event
     */
    private function handleRoomUpdated(RoomUpdated $event): void
    {
        $phong = $event->phong;
        $user = $event->user;
        $changes = $event->changes;

        // Get admin and staff users
        $adminStaffIds = User::whereIn('vai_tro', ['admin', 'nhan_vien'])
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        if (empty($adminStaffIds)) {
            Log::warning('No admin or staff users found for room notification');
            return;
        }

        // Create notification data
        $notificationData = [
            'kenh' => 'in_app',
            'ten_template' => 'room_updated',
            'payload' => json_encode([
                'title' => 'Phòng đã được cập nhật',
                'message' => "Phòng {$phong->ten_phong} (Tầng {$phong->tang->ten_tang}) đã được cập nhật bởi {$user->name}",
                'link' => "/admin/phong/{$phong->id}",
                'type' => 'room_updated',
                'room_id' => $phong->id,
                'room_name' => $phong->ten_phong,
                'floor' => $phong->tang->ten_tang,
                'updated_by' => $user->name,
                'changes' => $changes
            ])
        ];

        // Dispatch batch notification job
        SendBatchNotificationJob::dispatch($notificationData, $adminStaffIds, 'room_updated_' . $phong->id);

        Log::info('Room updated notification dispatched', [
            'room_id' => $phong->id,
            'room_name' => $phong->ten_phong,
            'recipients' => count($adminStaffIds),
            'updated_by' => $user->name,
            'changes' => $changes
        ]);
    }
}
