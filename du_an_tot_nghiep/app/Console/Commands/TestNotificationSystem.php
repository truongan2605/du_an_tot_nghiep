<?php

namespace App\Console\Commands;

use App\Models\ThongBao;
use App\Models\User;
use App\Jobs\SendNotificationJob;
use App\Jobs\SendBatchNotificationJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

class TestNotificationSystem extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notification:test 
                            {--type=single : Type of test (single, batch, all)}
                            {--user-id= : Specific user ID to test with}
                            {--count=5 : Number of notifications to create for batch test}';

    /**
     * The console command description.
     */
    protected $description = 'Test the notification system by creating sample notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $userId = $this->option('user-id');
        $count = (int) $this->option('count');

        $this->info("Testing notification system: {$type}");

        switch ($type) {
            case 'single':
                $this->testSingleNotification($userId);
                break;
            case 'batch':
                $this->testBatchNotification($count);
                break;
            case 'all':
                $this->testSingleNotification($userId);
                $this->testBatchNotification($count);
                break;
            default:
                $this->error('Invalid test type. Use: single, batch, or all');
                return 1;
        }

        $this->info('Notification system test completed!');
        return 0;
    }

    private function testSingleNotification($userId = null)
    {
        $this->info('Testing single notification...');

        // Get user
        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found");
                return;
            }
        } else {
            $user = User::first();
            if (!$user) {
                $this->error('No users found in database');
                return;
            }
        }

        $this->info("Testing with user: {$user->name} ({$user->email})");

        // Create test notification
        $notification = ThongBao::create([
            'nguoi_nhan_id' => $user->id,
            'kenh' => 'in_app',
            'ten_template' => 'test_notification',
            'payload' => [
                'title' => 'Test Notification',
                'message' => 'This is a test notification created by the test command.',
                'link' => '/account/notifications',
                'test_data' => [
                    'created_at' => now()->toISOString(),
                    'command' => 'notification:test',
                    'type' => 'single'
                ]
            ],
            'trang_thai' => 'pending',
            'so_lan_thu' => 0,
        ]);

        $this->info("Created notification ID: {$notification->id}");

        // Dispatch job
        SendNotificationJob::dispatch($notification->id, $user->id)
            ->onQueue('notifications');

        $this->info('Single notification job dispatched to queue');
    }

    private function testBatchNotification($count = 5)
    {
        $this->info("Testing batch notification with {$count} users...");

        // Get users
        $users = User::limit($count)->get();
        if ($users->isEmpty()) {
            $this->error('No users found in database');
            return;
        }

        $this->info("Testing with {$users->count()} users");

        // Prepare notification data
        $notificationData = [
            'kenh' => 'in_app',
            'ten_template' => 'batch_test_notification',
            'payload' => [
                'title' => 'Batch Test Notification',
                'message' => 'This is a batch test notification created by the test command.',
                'link' => '/account/notifications',
                'test_data' => [
                    'created_at' => now()->toISOString(),
                    'command' => 'notification:test',
                    'type' => 'batch',
                    'user_count' => $users->count()
                ]
            ],
        ];

        $userIds = $users->pluck('id')->toArray();

        // Dispatch batch job
        SendBatchNotificationJob::dispatch($notificationData, $userIds)
            ->onQueue('notifications');

        $this->info('Batch notification job dispatched to queue');
    }
}







