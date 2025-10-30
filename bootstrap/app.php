<?php

use App\Http\Middleware\EnsureTenantUser;
use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\TenancyServiceProvider;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        AppServiceProvider::class,
        AuthServiceProvider::class,
        TenancyServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'tenant.user' => EnsureTenantUser::class,
            'tenant.initialize' => InitializeTenancyByRequestData::class,
        ]);

        $middleware->group('tenant-api', [
            InitializeTenancyByRequestData::class,
            'auth:sanctum',
            EnsureTenantUser::class,
        ]);

        $middleware->group('api', [
            SubstituteBindings::class,
            'throttle:api',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->reportable(function (\Throwable $throwable) {
            if (app()->bound('sentry') && config('sentry.dsn')) {
                app('sentry')->captureException($throwable);
            }
        });
    })->create();
