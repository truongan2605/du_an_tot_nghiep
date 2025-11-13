<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\BookingCreated;
use App\Events\PaymentSuccess;
use App\Events\BookingCancelled;
use App\Events\ReminderCheckin;
use App\Events\RoomCreated;
use App\Events\RoomUpdated;
use App\Listeners\SendBookingNotification;
use App\Listeners\SendPaymentNotification;
use App\Listeners\SendCancellationNotification;
use App\Listeners\SendReminderNotification;
use App\Listeners\SendRoomNotification;
use App\Events\StaffCreated;
use App\Listeners\SendStaffNotification;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        BookingCreated::class => [
            SendBookingNotification::class,
        ],
        PaymentSuccess::class => [
            SendPaymentNotification::class,
        ],
        BookingCancelled::class => [
            SendCancellationNotification::class,
        ],
        ReminderCheckin::class => [
            SendReminderNotification::class,
        ],
        RoomCreated::class => [
            SendRoomNotification::class,
        ],
        RoomUpdated::class => [
            SendRoomNotification::class,
        ],
        StaffCreated::class => [
            SendStaffNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

