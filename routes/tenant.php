<?php

use App\Http\Controllers\Api\TenantAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware('tenant.initialize')->group(function () {
    Route::post('auth/register', [TenantAuthController::class, 'register']);
    Route::post('auth/login', [TenantAuthController::class, 'login']);

    Route::middleware('tenant-api')->group(function () {
        Route::get('auth/me', [TenantAuthController::class, 'me']);
        Route::post('auth/logout', [TenantAuthController::class, 'logout']);
    });
});
