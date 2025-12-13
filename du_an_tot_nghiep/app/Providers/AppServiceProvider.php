<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use App\Models\Phong;
use App\Models\DatPhong;
use App\Observers\PhongObserver;
use App\Observers\DatPhongObserver;
use Illuminate\Support\Facades\Mail;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Phong::observe(PhongObserver::class);
        DatPhong::observe(DatPhongObserver::class);
        Paginator::useBootstrapFive();

        Mail::alwaysTo('truongan26052005@gmail.com');
    }
}
