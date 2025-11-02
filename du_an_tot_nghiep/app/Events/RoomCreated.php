<?php

namespace App\Events;

use App\Models\Phong;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Phong $room;

    /**
     * Create a new event instance.
     */
    public function __construct(Phong $room)
    {
        $this->room = $room;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('room-updates'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'RoomCreated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'room' => [
                'id' => $this->room->id,
                'ma_phong' => $this->room->ma_phong,
                'name' => $this->room->name,
                'loai_phong' => $this->room->loaiPhong->name ?? null,
                'gia_cuoi_cung' => $this->room->gia_cuoi_cung,
                'trang_thai' => $this->room->trang_thai,
                'created_at' => $this->room->created_at->toISOString(),
            ]
        ];
    }
}