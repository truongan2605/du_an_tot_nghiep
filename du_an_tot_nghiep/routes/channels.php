<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Channel for user notifications
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Channel for room updates (public channel)
Broadcast::channel('room-updates', function () {
    return true; // Public channel, anyone can listen
});

// Channel for booking updates (public channel)
Broadcast::channel('booking-updates', function () {
    return true; // Public channel, anyone can listen
});
