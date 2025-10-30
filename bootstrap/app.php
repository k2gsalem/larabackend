<?php

use App\Http\Middleware\EnsureTenantUser;
use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\TenancyServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('api', function (Request $request) {
            return [
                Limit::perMinute((int) env('API_RATE_LIMIT', 60))
                    ->by($request->user()?->getAuthIdentifier() ?? $request->ip()),
            ];
        });

        $middleware->alias([
            'tenant.user' => EnsureTenantUser::class,
            'tenant.initialize' => InitializeTenancyByRequestData::class,
        ]);

        $middleware->group('tenant-api', [
            InitializeTenancyByRequestData::class,
            PreventAccessFromCentralDomains::class,
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
