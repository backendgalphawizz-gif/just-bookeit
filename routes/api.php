<?php

use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\ConfigController;
use App\Http\Controllers\Api\V1\DesignerController;
use App\Http\Controllers\Api\V1\DisputeController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\MeasurementController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SupportTicketController;
use App\Http\Controllers\Api\V1\UserAuthController;
use App\Http\Controllers\Api\V2\VendorAuthController;
use App\Http\Controllers\Api\V3\DriverAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('config', [ConfigController::class, 'index'])->name('config');

    Route::get('home', [HomeController::class, 'index'])->name('home');
    Route::get('categories', [CategoryController::class, 'index'])->name('categories');
    Route::get('search', [SearchController::class, 'index'])->name('search');
    Route::get('catalog', [CatalogController::class, 'index'])->name('catalog.index');
    Route::get('catalog/{item}', [CatalogController::class, 'show'])->name('catalog.show');
    Route::get('designers', [DesignerController::class, 'index'])->name('designers.index');
    Route::get('designers/{designer}', [DesignerController::class, 'show'])->name('designers.show');

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

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('bookings/addresses', [BookingController::class, 'addresses'])->name('bookings.addresses');
        Route::get('bookings/preview/{item}', [BookingController::class, 'preview'])->name('bookings.preview');
        Route::post('bookings', [BookingController::class, 'store'])->name('bookings.store');
        Route::get('bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
        Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
        Route::get('bookings/{booking}/dispute', [DisputeController::class, 'show'])->name('bookings.dispute.show');
        Route::post('bookings/{booking}/dispute/messages', [DisputeController::class, 'sendMessage'])->name('bookings.dispute.messages');

        Route::get('payment/methods', [PaymentController::class, 'methods'])->name('payment.methods');
        Route::get('payment/bookings/{booking}', [PaymentController::class, 'summary'])->name('payment.summary');
        Route::post('payment/bookings/{booking}/pay', [PaymentController::class, 'pay'])->name('payment.pay');

        Route::get('profile', [ProfileController::class, 'index'])->name('profile.index');
        Route::get('profile/pages', [ProfileController::class, 'pages'])->name('profile.pages');

        Route::get('addresses', [AddressController::class, 'index'])->name('addresses.index');
        Route::post('addresses', [AddressController::class, 'store'])->name('addresses.store');
        Route::put('addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
        Route::delete('addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');

        Route::get('measurements', [MeasurementController::class, 'index'])->name('measurements.index');
        Route::post('measurements', [MeasurementController::class, 'store'])->name('measurements.store');
        Route::get('measurements/{measurement}', [MeasurementController::class, 'show'])->name('measurements.show');
        Route::put('measurements/{measurement}', [MeasurementController::class, 'update'])->name('measurements.update');
        Route::delete('measurements/{measurement}', [MeasurementController::class, 'destroy'])->name('measurements.destroy');

        Route::get('chats', [ChatController::class, 'index'])->name('chats.index');
        Route::post('chats', [ChatController::class, 'store'])->name('chats.store');
        Route::get('chats/{chat}/messages', [ChatController::class, 'messages'])->name('chats.messages');
        Route::post('chats/{chat}/messages', [ChatController::class, 'sendMessage'])->name('chats.messages.send');

        Route::get('support-tickets', [SupportTicketController::class, 'index'])->name('support-tickets.index');
        Route::post('support-tickets', [SupportTicketController::class, 'store'])->name('support-tickets.store');
        Route::get('support-tickets/{ticket}', [SupportTicketController::class, 'show'])->name('support-tickets.show');
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
