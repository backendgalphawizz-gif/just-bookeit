<?php

use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\BookingController;
use App\Http\Controllers\Web\CatalogController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\VendorController;
use Illuminate\Support\Facades\Route;

// Route::get('/', [HomeController::class, 'index'])->name('web.home');
Route::get('/', fn () => redirect('/admin'))->name('web.home');
Route::get('/catalog', [CatalogController::class, 'index'])->name('web.catalog.index');
Route::get('/catalog/{item}', [CatalogController::class, 'show'])->name('web.catalog.show');

Route::get('/designers/{vendor}', [VendorController::class, 'show'])->name('web.vendors.show');

Route::middleware('customer.guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('web.login');
    Route::get('/register', [LoginController::class, 'showRegisterMobile'])->name('web.register');
    Route::post('/login/otp', [LoginController::class, 'sendOtp'])->name('web.login.otp');
    Route::get('/verify-otp', [LoginController::class, 'showVerifyOtp'])->name('web.verify-otp');
    Route::post('/verify-otp', [LoginController::class, 'verifyOtp'])->name('web.verify-otp.submit');
});

Route::get('/register/complete', [LoginController::class, 'showRegisterComplete'])->name('web.register.complete');
Route::post('/register/complete', [LoginController::class, 'register'])->name('web.register.submit');

Route::post('/guest', [LoginController::class, 'guest'])->name('web.guest');

Route::middleware('customer.auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('web.logout');

    Route::get('/bookings', [BookingController::class, 'index'])->name('web.bookings.index');
    Route::get('/bookings/{order}', [BookingController::class, 'show'])->name('web.bookings.show');
    Route::get('/book/{item}', [BookingController::class, 'overview'])->name('web.bookings.overview');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('web.profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('web.profile.update');
    Route::get('/profile/measurements', [ProfileController::class, 'measurements'])->name('web.profile.measurements');
    Route::get('/profile/measurements/create', [ProfileController::class, 'createMeasurement'])->name('web.profile.measurements.create');
    Route::get('/profile/addresses', [ProfileController::class, 'addresses'])->name('web.profile.addresses');
});
