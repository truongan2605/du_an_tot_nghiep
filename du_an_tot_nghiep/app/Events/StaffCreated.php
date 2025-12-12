<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StaffCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $staff;

    /**
     * Create a new event instance.
     */
    public function __construct(User $staff)
    {
        $this->staff = $staff;
    }
}


