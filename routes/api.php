<?php

use App\Http\Controllers\Api\V1\ConfigController;
use App\Http\Controllers\Api\V1\UserAuthController;
use App\Http\Controllers\Api\V2\VendorAuthController;
use App\Http\Controllers\Api\V3\DriverAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('config', [ConfigController::class, 'index'])->name('config');

    Route::prefix('auth')->group(function () {
        Route::post('otp/send', [UserAuthController::class, 'sendOtp'])->name('auth.otp.send');
        Route::post('otp/verify', [UserAuthController::class, 'verifyOtp'])->name('auth.otp.verify');
        Route::post('register', [UserAuthController::class, 'register'])->name('auth.register');
        Route::post('guest', [UserAuthController::class, 'guest'])->name('auth.guest');

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [UserAuthController::class, 'me'])->name('auth.me');
            Route::post('profile', [UserAuthController::class, 'updateProfile'])->name('auth.profile.update');
            Route::post('logout', [UserAuthController::class, 'logout'])->name('auth.logout');
        });
    });
});

Route::prefix('v2')->name('api.v2.')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('otp/send', [VendorAuthController::class, 'sendOtp']);
        Route::post('otp/verify', [VendorAuthController::class, 'verifyOtp']);
        Route::post('register', [VendorAuthController::class, 'register']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [VendorAuthController::class, 'me']);
            Route::post('profile', [VendorAuthController::class, 'updateProfile']);
            Route::post('logout', [VendorAuthController::class, 'logout']);
        });
    });
});

Route::prefix('v3')->name('api.v3.')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('otp/send', [DriverAuthController::class, 'sendOtp']);
        Route::post('otp/verify', [DriverAuthController::class, 'verifyOtp']);
        Route::post('register', [DriverAuthController::class, 'register']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [DriverAuthController::class, 'me']);
            Route::post('profile', [DriverAuthController::class, 'updateProfile']);
            Route::post('logout', [DriverAuthController::class, 'logout']);
        });
    });
});
