<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('cleanup:expired-holds --auto-cancel')->everyMinute();

Schedule::command('booking:auto-block-late-checkouts')->dailyAt('12:02')->timezone('Asia/Ho_Chi_Minh');
Schedule::command('booking:auto-block-late-checkouts')->dailyAt('13:02')->timezone('Asia/Ho_Chi_Minh');
// Schedule::command('booking:auto-block-late-checkouts')->dailyAt('17:06')->timezone('Asia/Ho_Chi_Minh');


