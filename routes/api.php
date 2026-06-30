<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->as('api.v1.')
    ->middleware(['api.json'])
    ->group(function (): void {
        /*
         |--------------------------------------------------------------------------
         | API v1
         |--------------------------------------------------------------------------
         |
         | API dùng chung Models và Database với Web Admin.
         | Các endpoint nghiệp vụ sẽ được bổ sung sau theo từng module.
         |
         */
        Route::post('login', [AuthController::class, 'login'])->name('login');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('profile', [AuthController::class, 'profile'])->name('profile');
        });
    });
