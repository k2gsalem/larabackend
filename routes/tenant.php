<?php

use App\Http\Controllers\Api\TenantAuthController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\TenantUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware('tenant.initialize')->group(function () {
    Route::post('auth/register', [TenantAuthController::class, 'register']);
    Route::post('auth/login', [TenantAuthController::class, 'login']);
    Route::post('auth/social', [TenantAuthController::class, 'socialLogin']);
    Route::post('auth/mobile/request', [TenantAuthController::class, 'requestOtp']);
    Route::post('auth/mobile/verify', [TenantAuthController::class, 'verifyOtp']);

    Route::middleware('tenant-api')->group(function () {
        Route::get('auth/me', [TenantAuthController::class, 'me']);
        Route::post('auth/logout', [TenantAuthController::class, 'logout']);

        Route::apiResource('users', TenantUserController::class);
        Route::apiResource('roles', RoleController::class);

        Route::post('billing/subscriptions', [SubscriptionController::class, 'store']);
        Route::delete('billing/subscriptions', [SubscriptionController::class, 'destroy']);
    });
});
