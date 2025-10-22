<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckQueue extends Command
{
    protected $signature = 'queue:check';
    protected $description = 'Check queue status and jobs';

    public function handle()
    {
        $this->info('Queue Status Check');
        $this->line('==================');
        
        // Check jobs table
        $jobsCount = DB::table('jobs')->count();
        $this->info("Jobs in queue: {$jobsCount}");
        
        if ($jobsCount > 0) {
            $this->warn('There are pending jobs in the queue!');
            $this->info('Run: php artisan queue:work to process them');
        } else {
            $this->info('No pending jobs in queue');
        }
        
        // Check failed jobs
        $failedCount = DB::table('failed_jobs')->count();
        $this->info("Failed jobs: {$failedCount}");
        
        if ($failedCount > 0) {
            $this->warn('There are failed jobs!');
            $this->info('Run: php artisan queue:failed to see details');
        }
        
        return 0;
    }
}







