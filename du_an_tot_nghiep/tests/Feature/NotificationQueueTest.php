<?php

namespace Tests\Feature;

use App\Jobs\SendNotificationJob;
use App\Jobs\SendBatchNotificationJob;
use App\Models\ThongBao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_notification_job_can_be_dispatched()
    {
        Queue::fake();

        $user = User::factory()->create();
        $notification = ThongBao::create([
            'nguoi_nhan_id' => $user->id,
            'kenh' => 'email',
            'ten_template' => 'test_template',
            'payload' => ['message' => 'Test message'],
            'trang_thai' => 'pending',
            'so_lan_thu' => 0,
        ]);

        SendNotificationJob::dispatch($notification->id, $user->id);

        Queue::assertPushed(SendNotificationJob::class, function ($job) use ($notification, $user) {
            return $job->thongBaoId === $notification->id && $job->userId === $user->id;
        });
    }

    public function test_batch_notification_job_can_be_dispatched()
    {
        Queue::fake();

        $users = User::factory()->count(3)->create();
        $userIds = $users->pluck('id')->toArray();

        $notificationData = [
            'kenh' => 'email',
            'ten_template' => 'batch_test',
            'payload' => ['message' => 'Batch test message'],
        ];

        SendBatchNotificationJob::dispatch($notificationData, $userIds);

        Queue::assertPushed(SendBatchNotificationJob::class);
    }

    public function test_batch_notification_creates_individual_notifications()
    {
        $users = User::factory()->count(5)->create();
        $userIds = $users->pluck('id')->toArray();

        $notificationData = [
            'kenh' => 'in_app',
            'ten_template' => 'batch_test',
            'payload' => ['message' => 'Batch test message'],
        ];

        $batchId = 'test_batch_' . uniqid();
        $job = new SendBatchNotificationJob($notificationData, $userIds, $batchId);
        
        // Mock the job execution
        $job->handle();

        // Assert that notifications were created
        $this->assertEquals(5, ThongBao::where('batch_id', $batchId)->count());
        
        // Assert that all notifications have the correct data
        $notifications = ThongBao::where('batch_id', $batchId)->get();
        foreach ($notifications as $notification) {
            $this->assertEquals('in_app', $notification->kenh);
            $this->assertEquals('batch_test', $notification->ten_template);
            $this->assertEquals('pending', $notification->trang_thai);
            $this->assertEquals($batchId, $notification->batch_id);
        }
    }

    public function test_notification_job_handles_missing_user()
    {
        $notification = ThongBao::create([
            'nguoi_nhan_id' => 99999, // Non-existent user
            'kenh' => 'email',
            'ten_template' => 'test_template',
            'payload' => ['message' => 'Test message'],
            'trang_thai' => 'pending',
            'so_lan_thu' => 0,
        ]);

        $job = new SendNotificationJob($notification->id, 99999);
        
        // This should not throw an exception
        $job->handle();

        // Notification should be marked as failed
        $notification->refresh();
        $this->assertEquals('failed', $notification->trang_thai);
        $this->assertNotNull($notification->error_message);
    }

    public function test_batch_notification_with_chunking()
    {
        // Create 100 users to test chunking
        $users = User::factory()->count(100)->create();
        $userIds = $users->pluck('id')->toArray();

        $notificationData = [
            'kenh' => 'in_app',
            'ten_template' => 'large_batch_test',
            'payload' => ['message' => 'Large batch test message'],
        ];

        $batchId = 'large_batch_' . uniqid();
        $job = new SendBatchNotificationJob($notificationData, $userIds, $batchId);
        
        $job->handle();

        // Should create 100 notifications
        $this->assertEquals(100, ThongBao::where('batch_id', $batchId)->count());
        
        // Should be processed in chunks of 50
        $chunks = ThongBao::where('batch_id', $batchId)
            ->selectRaw('COUNT(*) as count')
            ->groupBy('batch_id')
            ->first();
        
        $this->assertEquals(100, $chunks->count);
    }
}