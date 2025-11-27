<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\DatPhong;

class AutoBlockLateCheckouts extends Command
{
    protected $signature = 'booking:auto-block-late-checkouts';
    protected $description = 'Tự động bật/clear blocks_checkin cho booking có checkout trùng ngày (hôm nay)';

    public function handle()
    {
        $today = Carbon::today('Asia/Ho_Chi_Minh')->toDateString();

        $this->info("AutoBlockLateCheckouts running for date: {$today}");

        DB::beginTransaction();
        try {
            // 1) Bật blocks_checkin = true cho booking có ngay_tra_phong == today và trang_thai === 'dang_su_dung'
            DatPhong::whereDate('ngay_tra_phong', $today)
                ->where('trang_thai', 'dang_su_dung')
                ->where('blocks_checkin', false)
                ->chunkById(100, function ($bookings) {
                    foreach ($bookings as $b) {
                        $b->blocks_checkin = true;
                        $b->save();

                        $this->info("Blocked booking #{$b->id} ({$b->ma_tham_chieu})");
                    }
                });

            // 2) Clear blocks_checkin cho booking đã hoàn thành hoặc không còn là ngày hôm nay nữa
            DatPhong::where('blocks_checkin', true)
                ->where(function ($q) use ($today) {
                    $q->where('trang_thai', 'hoan_thanh')
                      ->orWhereDate('ngay_tra_phong', '!=', $today);
                })
                ->chunkById(100, function ($bookings) {
                    foreach ($bookings as $b) {
                        $b->blocks_checkin = false;
                        $b->save();

                        $this->info("Cleared block for booking #{$b->id} ({$b->ma_tham_chieu})");
                    }
                }); 

            DB::commit();
            $this->info('AutoBlockLateCheckouts completed.');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('AutoBlockLateCheckouts error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
