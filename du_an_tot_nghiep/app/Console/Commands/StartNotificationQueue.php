<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class StartNotificationQueue extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notification:start-queue 
                            {--timeout=300 : Timeout in seconds}
                            {--tries=3 : Number of retry attempts}
                            {--sleep=3 : Sleep time between jobs}
                            {--max-jobs=100 : Maximum jobs to process before restart}
                            {--max-time=3600 : Maximum time to run in seconds}';

    /**
     * The console command description.
     */
    protected $description = 'Start the notification queue worker with optimized settings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $timeout = $this->option('timeout');
        $tries = $this->option('tries');
        $sleep = $this->option('sleep');
        $maxJobs = $this->option('max-jobs');
        $maxTime = $this->option('max-time');

        $this->info('Starting notification queue worker...');
        $this->info("Configuration:");
        $this->info("- Timeout: {$timeout}s");
        $this->info("- Tries: {$tries}");
        $this->info("- Sleep: {$sleep}s");
        $this->info("- Max Jobs: {$maxJobs}");
        $this->info("- Max Time: {$maxTime}s");

        $command = "php artisan queue:work --queue=notifications --timeout={$timeout} --tries={$tries} --sleep={$sleep} --max-jobs={$maxJobs} --max-time={$maxTime}";

        $this->info("Running: {$command}");
        $this->info('Press Ctrl+C to stop the queue worker');

        try {
            Process::start($command, function (string $type, string $output) {
                $this->line($output);
            });
        } catch (\Exception $e) {
            $this->error("Failed to start queue worker: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}