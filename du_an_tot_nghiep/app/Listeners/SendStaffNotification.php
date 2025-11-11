<?php

namespace App\Listeners;

use App\Events\StaffCreated;
use App\Events\NotificationCreated;
use App\Models\ThongBao;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendStaffNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(StaffCreated $event): void
    {
        try {
            $staff = $event->staff;

            // Notify all admins about the new staff (with de-dup within 1 minute)
            $admins = User::where('vai_tro', 'admin')->get(['id']);

            foreach ($admins as $admin) {
                // De-duplication: avoid creating duplicates if a recent one exists
                $existingAdminNotif = ThongBao::where('nguoi_nhan_id', $admin->id)
                    ->where('kenh', 'in_app')
                    ->where('ten_template', 'staff_created')
                    ->where(function($q) use ($staff) {
                        $q->whereJsonContains('payload->staff->id', $staff->id)
                          ->orWhereJsonContains('payload->staff_id', $staff->id);
                    })
                    ->where('created_at', '>=', now()->subMinutes(1))
                    ->first();

                if ($existingAdminNotif) {
                    continue;
                }

                $notification = ThongBao::create([
                    'nguoi_nhan_id' => $admin->id,
                    'kenh' => 'in_app',
                    'ten_template' => 'staff_created',
                    'payload' => [
                        'title' => 'Nhân viên mới đã được thêm',
                        'message' => "Nhân viên {$staff->name} (" . ($staff->phong_ban ?? 'Chưa có phòng ban') . ") vừa được tạo.",
                        'link' => '/admin/nhan-vien',
                        'type' => 'system',
                        'staff' => [
                            'id' => $staff->id,
                            'name' => $staff->name,
                            'email' => $staff->email,
                            'phong_ban' => $staff->phong_ban ?? null,
                            'created_at' => $staff->created_at,
                        ],
                    ],
                    'trang_thai' => 'pending',
                    'so_lan_thu' => 0,
                ]);

                broadcast(new NotificationCreated($notification));
            }

            // Welcome notification to the staff themselves
            // De-duplication for welcome notification
            $existingWelcome = ThongBao::where('nguoi_nhan_id', $staff->id)
                ->where('kenh', 'in_app')
                ->where('ten_template', 'staff_welcome')
                ->where('created_at', '>=', now()->subMinutes(1))
                ->first();

            if (!$existingWelcome) {
                $selfNotification = ThongBao::create([
                'nguoi_nhan_id' => $staff->id,
                'kenh' => 'in_app',
                'ten_template' => 'staff_welcome',
                'payload' => [
                    'title' => 'Chào mừng bạn gia nhập đội ngũ',
                    'message' => 'Tài khoản nhân viên của bạn đã được tạo. Hãy bắt đầu bằng việc cập nhật hồ sơ và xem các tài nguyên dành cho nhân viên.',
                    'link' => '/staff',
                    'type' => 'welcome',
                    'profile' => [
                        'name' => $staff->name,
                        'email' => $staff->email,
                        'phong_ban' => $staff->phong_ban ?? null,
                    ],
                ],
                'trang_thai' => 'pending',
                'so_lan_thu' => 0,
                ]);

                broadcast(new NotificationCreated($selfNotification));
            }

            Log::info('StaffCreated notifications sent', [
                'staff_id' => $staff->id,
                'admin_count' => $admins->count(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send StaffCreated notifications', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}


