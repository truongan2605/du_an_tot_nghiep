<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Models\DatPhong;

class AutoCancelMissedCheckins extends Command
{
    protected $signature = 'booking:auto-cancel-missed-checkins';
    protected $description = 'Tự động hủy các đơn đặt phòng đã quá ngày check-in mà khách chưa check-in';

    public function handle()
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $yesterday = $now->copy()->subDay()->endOfDay(); // Kết thúc ngày hôm qua
        
        $this->info("AutoCancelMissedCheckins running at: {$now->toDateTimeString()}");
        $this->info("Cancelling bookings with check-in date before: {$yesterday->toDateString()}");

        DB::beginTransaction();
        try {
            // Tìm các đơn đã quá ngày check-in, khách không đến checkin, vẫn ở trạng thái đã xác nhận
            $bookings = DatPhong::whereDate('ngay_nhan_phong', '<', $now->toDateString())
                ->where('trang_thai', 'da_xac_nhan')
                ->whereNull('cancelled_at')
                ->get();

            $cancelledCount = 0;

            foreach ($bookings as $booking) {
                $checkinDate = Carbon::parse($booking->ngay_nhan_phong);
                
                // Chỉ hủy nếu đã qua hết ngày check-in (sau 23:59:59)
                if ($now->isAfter($checkinDate->endOfDay())) {
                    DB::table('dat_phong')
                        ->where('id', $booking->id)
                        ->update([
                            'trang_thai' => 'da_huy',
                            'cancelled_at' => now(),
                            'cancellation_reason' => 'Tự động hủy do khách không check-in trong ngày',
                            'updated_at' => now()
                        ]);

                    // Xóa dat_phong_items
                    if (Schema::hasTable('dat_phong_item')) {
                        DB::table('dat_phong_item')
                            ->where('dat_phong_id', $booking->id)
                            ->delete();
                    }

                    // Xóa giu_phong records
                    if (Schema::hasTable('giu_phong')) {
                        DB::table('giu_phong')
                            ->where('dat_phong_id', $booking->id)
                            ->delete();
                    }

                    $cancelledCount++;
                    $this->info("Cancelled booking #{$booking->id} ({$booking->ma_tham_chieu}) - Check-in date: {$checkinDate->format('d/m/Y')}");
                    
                    Log::info('AutoCancelMissedCheckins: Booking auto-cancelled', [
                        'booking_id' => $booking->id,
                        'ma_tham_chieu' => $booking->ma_tham_chieu,
                        'checkin_date' => $checkinDate->format('Y-m-d'),
                        'previous_status' => $booking->trang_thai
                    ]);
                }
            }

            DB::commit();
            $this->info("AutoCancelMissedCheckins completed. Cancelled {$cancelledCount} booking(s).");
            
            return 0;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('AutoCancelMissedCheckins error: ' . $e->getMessage());
            Log::error('AutoCancelMissedCheckins: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}

