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
        // Ensure we use Asia/Bangkok as you mentioned
        $today = Carbon::now('Asia/Ho_Chi_Minh')->toDateString();

        $this->info("AutoBlockLateCheckouts running for date: {$today}");

        DB::beginTransaction();
        try {
            // 1) Bật blocks_checkin = true cho booking có ngay_tra_phong == today và chưa hoan_thanh/không hủy
            $toBlock = DatPhong::whereDate('ngay_tra_phong', $today)
                ->whereNotIn('trang_thai', ['hoan_thanh', 'da_huy'])
                ->where('blocks_checkin', false)
                ->get();

            foreach ($toBlock as $b) {
                // set flag (system auto)
                $b->blocks_checkin = true;
                $b->blocks_checkin_at = now();
                $b->blocks_checkin_by = null; // null => system
                $b->blocks_checkin_reason = 'auto_late_checkout';
                $b->save();

                $this->info("Blocked booking #{$b->id} ({$b->ma_tham_chieu})");
            }

            // 2) Clear blocks_checkin cho booking đã được hoàn tất (hoan_thanh)
            $toClear = DatPhong::where('blocks_checkin', true)
                ->where(function ($q) use ($today) {
                    // clear if booking now hoan_thanh OR ngay_tra_phong no longer today (e.g. moved)
                    $q->where('trang_thai', 'hoan_thanh')
                      ->orWhereDate('ngay_tra_phong', '!=', $today);
                })
                ->get();

            foreach ($toClear as $b) {
                $b->blocks_checkin = false;
                $b->blocks_checkin_at = null;
                $b->blocks_checkin_by = null;
                $b->blocks_checkin_reason = null;
                $b->save();

                $this->info("Cleared block for booking #{$b->id} ({$b->ma_tham_chieu})");
            }

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
