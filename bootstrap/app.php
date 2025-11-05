<?php

use App\Jobs\CheckCreditIntegrityJob;
use App\Providers\EventServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'block.suspicious' => \App\Http\Middleware\BlockSuspiciousIPs::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Run credit integrity check weekly on Sundays at 2 AM
        $schedule->job(new CheckCreditIntegrityJob())
            ->weekly()
            ->sundays()
            ->at('02:00')
            ->name('check-credit-integrity')
            ->withoutOverlapping();
    })
    ->withProviders([
        EventServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
