<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Session\Middleware\StartSession;

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware) {
      
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'manage.users' => \App\Http\Middleware\ManageUsers::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);

      
        $middleware->group('api', [
            EnsureFrontendRequestsAreStateful::class,
            StartSession::class,
            AddQueuedCookiesToResponse::class,
            HandleCors::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
