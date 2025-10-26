<?php

namespace App\Jobs;

use App\Models\ThongBao;
use App\Models\User;
use App\Events\NotificationCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class SendBatchNotificationJob implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $notificationData;
    protected $userIds;
    protected $batchId;

    /**
     * Create a new job instance.
     */
    public function __construct($notificationData, $userIds, $batchId = null)
    {
        $this->notificationData = $notificationData;
        $this->userIds = $userIds;
        $this->batchId = $batchId ?: 'batch_' . uniqid() . '_' . time();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Starting batch notification job", [
                'batch_id' => $this->batchId,
                'user_count' => count($this->userIds),
                'notification_data' => $this->notificationData
            ]);

            // Process users in chunks of 50 to avoid memory issues
            $chunkSize = 50;
            $chunks = array_chunk($this->userIds, $chunkSize);

            foreach ($chunks as $chunkIndex => $userChunk) {
                $this->processUserChunk($userChunk, $chunkIndex);
            }

            Log::info("Batch notification job completed", [
                'batch_id' => $this->batchId,
                'total_users' => count($this->userIds)
            ]);

        } catch (Exception $e) {
            Log::error("Batch notification job failed", [
                'batch_id' => $this->batchId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    /**
     * Process a chunk of users
     */
    private function processUserChunk($userIds, $chunkIndex)
    {
        foreach ($userIds as $userId) {
            try {
                $user = User::find($userId);
                if (!$user) {
                    Log::warning("User not found for batch notification", [
                        'user_id' => $userId,
                        'batch_id' => $this->batchId
                    ]);
                    continue;
                }

                // Create notification record
                $notification = ThongBao::create([
                    'nguoi_nhan_id' => $userId,
                    'kenh' => $this->notificationData['kenh'],
                    'ten_template' => $this->notificationData['ten_template'],
                    'payload' => $this->notificationData['payload'],
                    'trang_thai' => 'pending',
                    'so_lan_thu' => 0,
                    'batch_id' => $this->batchId,
                ]);

                // Broadcast notification for real-time updates
                if ($this->notificationData['kenh'] === 'in_app') {
                    broadcast(new NotificationCreated($notification));
                }

                Log::info("Created notification for user", [
                    'user_id' => $userId,
                    'batch_id' => $this->batchId
                ]);

            } catch (Exception $e) {
                Log::error("Failed to create notification for user", [
                    'user_id' => $userId,
                    'batch_id' => $this->batchId,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
