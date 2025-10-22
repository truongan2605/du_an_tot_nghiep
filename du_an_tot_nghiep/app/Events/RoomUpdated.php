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

class RoomUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $phong;
    public $user;
    public $changes;

    /**
     * Create a new event instance.
     */
    public function __construct(Phong $phong, $user, $changes = [])
    {
        $this->phong = $phong;
        $this->user = $user;
        $this->changes = $changes;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}







