<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DatPhong;
use App\Events\ReminderCheckin;
use Carbon\Carbon;

class SendCheckinReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-checkin-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send check-in reminder notifications for bookings tomorrow';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tomorrow = Carbon::tomorrow();
        
        // Find bookings that check-in tomorrow
        $bookings = DatPhong::whereDate('ngay_nhan_phong', $tomorrow)
            ->whereIn('trang_thai', ['da_xac_nhan', 'dang_cho'])
            ->with('nguoiDung')
            ->get();

        $this->info("Found {$bookings->count()} bookings for tomorrow ({$tomorrow->format('d/m/Y')})");

        foreach ($bookings as $booking) {
            // Dispatch the reminder event
            event(new ReminderCheckin($booking));
            $this->line("Sent reminder for booking #{$booking->ma_tham_chieu} - Customer: {$booking->nguoiDung->name}");
        }

        $this->info('Check-in reminders sent successfully!');
    }
}
