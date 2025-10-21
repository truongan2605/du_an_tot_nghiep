<?php

namespace App\Console\Commands;

use App\Models\ThongBao;
use Illuminate\Console\Command;

class CheckNotifications extends Command
{
    protected $signature = 'notification:check';
    protected $description = 'Check recent notifications';

    public function handle()
    {
        $this->info('Total notifications: ' . ThongBao::count());
        
        $this->info('Recent room notifications:');
        $notifications = ThongBao::where('ten_template', 'room_created')
            ->latest()
            ->take(5)
            ->get(['id', 'ten_template', 'trang_thai', 'created_at']);
            
        foreach ($notifications as $notification) {
            $this->line("ID: {$notification->id} - Template: {$notification->ten_template} - Status: {$notification->trang_thai} - Created: {$notification->created_at}");
        }
        
        $this->info('Recent batch notifications:');
        $batchNotifications = ThongBao::whereNotNull('batch_id')
            ->where('batch_id', 'like', 'room_created_%')
            ->latest()
            ->take(5)
            ->get(['id', 'ten_template', 'trang_thai', 'batch_id', 'created_at']);
            
        foreach ($batchNotifications as $notification) {
            $this->line("ID: {$notification->id} - Template: {$notification->ten_template} - Status: {$notification->trang_thai} - Batch: {$notification->batch_id} - Created: {$notification->created_at}");
        }
        
        return 0;
    }
}
