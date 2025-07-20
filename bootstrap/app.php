<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
        'admin' => \App\Http\Middleware\AdminSanctumAuth::class,
        'freelancer' => \App\Http\Middleware\FreelancerSanctumAuth::class,
        'freelancer.hasProfile' => \App\Http\Middleware\EnsureFreelancerHasProfile::class,
        'freelancer.phone' => \App\Http\Middleware\EnsureFreelancerIsPhoneAuthenticated::class,
        'client.phone' => \App\Http\Middleware\EnsureClientIsPhoneAuthenticated::class,
        'client.hasProfile' => \App\Http\Middleware\EnsureClientHasProfile::class,

    ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
