<?php

use App\Http\Controllers\Vendor\Auth\LoginController;
use App\Http\Controllers\Vendor\NotificationController;
use App\Http\Controllers\Vendor\ListExportController;
use App\Http\Controllers\Vendor\BookingController;
use App\Http\Controllers\Vendor\ChatController;
use App\Http\Controllers\Vendor\DashboardController;
use App\Http\Controllers\Vendor\PaymentController;
use App\Http\Controllers\Vendor\PortfolioController;
use App\Http\Controllers\Vendor\ProductController;
use App\Http\Controllers\Vendor\SettingsController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->prefix('vendor')->name('vendor.')->group(function () {
    Route::get('/', function () {
        return Auth::guard('vendor')->check()
            ? redirect()->route('vendor.dashboard')
            : redirect()->route('vendor.login');
    });
    // Route::get('/', fn () => redirect('/admin'));

    Route::middleware('vendor.guest')->group(function () {
        Route::get('login', [LoginController::class, 'showLogin'])->name('login');
        Route::get('register', [LoginController::class, 'showRegister'])->name('register');
        Route::post('otp/send', [LoginController::class, 'sendOtp'])->name('otp.send');
        Route::post('otp/resend', [LoginController::class, 'resendOtp'])->name('otp.resend');
        Route::get('verify-otp', [LoginController::class, 'showVerifyOtp'])->name('verify-otp');
        Route::post('verify-otp', [LoginController::class, 'verifyOtp'])->name('verify-otp.submit');
        Route::get('register/complete', [LoginController::class, 'showRegisterComplete'])->name('register.complete');
        Route::post('register', [LoginController::class, 'register'])->name('register.submit');
    });

    Route::middleware('vendor.auth')->group(function () {
        Route::get('list-export/{module}', ListExportController::class)
            ->where('module', 'bookings|products|payments|wallet|chat')
            ->name('list-export');

        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('logout', [LoginController::class, 'logout'])->name('logout');

        Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
        Route::post('bookings/{booking}/accept', [BookingController::class, 'accept'])->name('bookings.accept');
        Route::post('bookings/{booking}/reject', [BookingController::class, 'reject'])->name('bookings.reject');
        Route::post('bookings/{booking}/items/{item}/accept', [BookingController::class, 'acceptItem'])->name('bookings.items.accept');
        Route::post('bookings/{booking}/items/{item}/reject', [BookingController::class, 'rejectItem'])->name('bookings.items.reject');
        Route::post('bookings/{booking}/status', [BookingController::class, 'updateStatus'])->name('bookings.status');

        Route::get('portfolio', [PortfolioController::class, 'index'])->name('portfolio.index');
        Route::post('portfolio', [PortfolioController::class, 'store'])->name('portfolio.store');
        Route::delete('portfolio/{portfolioImage}', [PortfolioController::class, 'destroy'])->name('portfolio.destroy');

        Route::get('products', [ProductController::class, 'index'])->name('products.index');
        Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('products', [ProductController::class, 'store'])->name('products.store');
        Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('products/{product}/images/{image}', [ProductController::class, 'destroyImage'])->name('products.images.destroy');
        Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

        Route::get('chat', [ChatController::class, 'index'])->name('chat.index');
        Route::get('chat/poll', [ChatController::class, 'poll'])->name('chat.poll');
        Route::get('chat/{chat}', [ChatController::class, 'show'])->name('chat.show');
        Route::post('chat/{chat}/messages', [ChatController::class, 'sendMessage'])->name('chat.messages');

        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('settings/toggle-active', [SettingsController::class, 'toggleActive'])->name('settings.toggle-active');
        Route::post('settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    });
});
