<?php

namespace App\Jobs;

use App\Models\ThongBao;
use App\Models\User;
use App\Mail\ThongBaoEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;

class SendNotificationJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $timeout = 300; // 5 minutes timeout
    public $tries = 3; // Retry 3 times
    public $backoff = [30, 60, 120]; // Backoff intervals in seconds

    protected $thongBaoId;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct($thongBaoId, $userId = null)
    {
        $this->thongBaoId = $thongBaoId;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $thongBao = ThongBao::findOrFail($this->thongBaoId);
            $user = $this->userId ? User::findOrFail($this->userId) : $thongBao->nguoiNhan;

            if (!$user || !$user->email) {
                $this->updateNotificationStatus($thongBao, 'failed', 'Không tìm thấy email người nhận');
                return;
            }

            // Send email
            if ($thongBao->kenh === 'email') {
                Mail::to($user->email)->send(new ThongBaoEmail($thongBao));
            }

            // Update notification status
            $this->updateNotificationStatus($thongBao, 'sent');

            Log::info("Notification sent successfully", [
                'notification_id' => $thongBao->id,
                'user_id' => $user->id,
                'channel' => $thongBao->kenh
            ]);

        } catch (Exception $e) {
            Log::error("Failed to send notification", [
                'notification_id' => $this->thongBaoId,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            $this->updateNotificationStatus($thongBao, 'failed', $e->getMessage());
            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    /**
     * Update notification status
     */
    private function updateNotificationStatus($thongBao, $status, $errorMessage = null)
    {
        $updateData = [
            'trang_thai' => $status,
            'so_lan_thu' => ($thongBao->so_lan_thu ?? 0) + 1,
            'lan_thu_cuoi' => now(),
        ];

        if ($errorMessage) {
            $updateData['error_message'] = $errorMessage;
        }

        $thongBao->update($updateData);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error("Notification job failed permanently", [
            'notification_id' => $this->thongBaoId,
            'user_id' => $this->userId,
            'error' => $exception->getMessage()
        ]);

        // Mark notification as permanently failed
        $thongBao = ThongBao::find($this->thongBaoId);
        if ($thongBao) {
            $thongBao->update([
                'trang_thai' => 'failed',
                'so_lan_thu' => ($thongBao->so_lan_thu ?? 0) + 1,
                'lan_thu_cuoi' => now(),
                'error_message' => 'Job failed permanently: ' . $exception->getMessage()
            ]);
        }
    }
}
