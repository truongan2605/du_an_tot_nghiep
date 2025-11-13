<?php

namespace App\Events;

use App\Models\DatPhong;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public DatPhong $booking;

    /**
     * Create a new event instance.
     */
    public function __construct(DatPhong $booking)
    {
        $this->booking = $booking;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('booking-updates'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'BookingCreated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'booking' => [
                'id' => $this->booking->id,
                'ma_dat_phong' => $this->booking->ma_dat_phong,
                'nguoi_dung_id' => $this->booking->nguoi_dung_id,
                'ngay_nhan_phong' => $this->booking->ngay_nhan_phong,
                'ngay_tra_phong' => $this->booking->ngay_tra_phong,
                'trang_thai' => $this->booking->trang_thai,
                'tong_tien' => $this->booking->tong_tien,
                'created_at' => $this->booking->created_at->toISOString(),
            ]
        ];
    }
}

