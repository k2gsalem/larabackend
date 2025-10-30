<?php

use App\Http\Controllers\Api\CentralAuthController;
use App\Http\Controllers\Api\TenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/admin')->group(function () {
    Route::post('auth/login', [CentralAuthController::class, 'login']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('auth/me', [CentralAuthController::class, 'me']);
        Route::post('auth/logout', [CentralAuthController::class, 'logout']);

        Route::apiResource('tenants', TenantController::class)->except(['update']);
        Route::patch('tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
    });
});
