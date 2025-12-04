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
Schedule::command('booking:auto-block-late-checkouts')->dailyAt('22:25')->timezone('Asia/Ho_Chi_Minh');

// Tự động hủy các đơn đã quá ngày check-in mà khách chưa check-in
// Chạy vào 23:59 mỗi ngày (cuối ngày check-in)
Schedule::command('booking:auto-cancel-missed-checkins')->dailyAt('23:59');
Schedule::command('booking:auto-cancel-missed-checkins')->dailyAt('19:14');