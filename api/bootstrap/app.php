<?php

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
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        if (config('network_tracker.enabled')) {
            $schedule->command('network:snapshot KOTO')
                ->everyFifteenMinutes()
                ->withoutOverlapping(600);
        }
        if (config('faucet.auto_sync_balance')) {
            $m = (int) config('faucet.sync_balance_interval_minutes', 5);
            $schedule->command('faucet:sync-balance')
                ->cron(sprintf('*/%d * * * *', $m))
                ->withoutOverlapping(180);
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
