<?php

use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\BookingController;
use App\Http\Controllers\Web\CartController;
use App\Http\Controllers\Web\CatalogController;
use App\Http\Controllers\Web\ChatController;
use App\Http\Controllers\Web\CheckoutController;
use App\Http\Controllers\Web\CheckoutPaymentController;
use App\Http\Controllers\Web\ContactController;
use App\Http\Controllers\Web\DisputeController;
use App\Http\Controllers\Web\FaqController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\LocationController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\PaymentController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\VendorController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('web.home');
Route::get('/services', [CatalogController::class, 'services'])->name('web.services.index');
Route::get('/catalog', [CatalogController::class, 'index'])->name('web.catalog.index');
Route::get('/catalog/{item}', [CatalogController::class, 'show'])->name('web.catalog.show');
Route::get('/contact', [ContactController::class, 'index'])->name('web.contact');
Route::get('/faq', [FaqController::class, 'index'])->name('web.faq');

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

Route::post('/location', [LocationController::class, 'update'])->name('web.location.update');

Route::middleware('customer.auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('web.logout');
});

Route::middleware(['customer.auth', 'customer.registered'])->group(function () {
    Route::get('/bookings', [BookingController::class, 'index'])->name('web.bookings.index');
    Route::get('/bookings/checkout/{checkoutOrder}', [BookingController::class, 'showCheckout'])->name('web.bookings.checkout.show');
    Route::get('/bookings/{order}', [BookingController::class, 'show'])->name('web.bookings.show');
    Route::post('/bookings/{order}/cancel', [BookingController::class, 'cancel'])->name('web.bookings.cancel');
    Route::post('/bookings/{order}/dispute', [DisputeController::class, 'store'])->name('web.bookings.dispute.store');
    Route::get('/bookings/{order}/dispute', [DisputeController::class, 'show'])->name('web.bookings.dispute.show');
    Route::post('/bookings/{order}/dispute/messages', [DisputeController::class, 'sendMessage'])->name('web.bookings.dispute.messages');
    Route::get('/bookings/{order}/payment', [PaymentController::class, 'show'])->name('web.bookings.payment');
    Route::post('/bookings/{order}/payment', [PaymentController::class, 'pay'])->name('web.bookings.payment.pay');
    Route::get('/book/{item}', [BookingController::class, 'overview'])->name('web.bookings.overview');
    Route::post('/book/{item}/preview', [BookingController::class, 'preview'])->name('web.bookings.preview');
    Route::post('/book/{item}', [BookingController::class, 'store'])->name('web.bookings.store');

    Route::get('/cart', [CartController::class, 'index'])->name('web.cart.index');
    Route::post('/cart', [CartController::class, 'store'])->name('web.cart.store');
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])->name('web.cart.destroy');

    Route::get('/checkout', [CheckoutController::class, 'show'])->name('web.checkout.show');
    Route::post('/checkout/preview', [CheckoutController::class, 'preview'])->name('web.checkout.preview');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('web.checkout.store');
    Route::get('/checkout-orders/{checkoutOrder}', [CheckoutController::class, 'showOrder'])->name('web.checkout.show-order');
    Route::get('/checkout-orders/{checkoutOrder}/payment', [CheckoutPaymentController::class, 'show'])->name('web.checkout.payment');
    Route::post('/checkout-orders/{checkoutOrder}/payment', [CheckoutPaymentController::class, 'pay'])->name('web.checkout.payment.pay');

    Route::get('/chat', [ChatController::class, 'index'])->name('web.chat.index');
    Route::get('/chat/poll', [ChatController::class, 'poll'])->name('web.chat.poll');
    Route::get('/chat/start/{vendor}', [ChatController::class, 'start'])->name('web.chat.start');
    Route::post('/chat/{chat}/messages', [ChatController::class, 'sendMessage'])->name('web.chat.messages');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('web.notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('web.notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('web.notifications.read');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('web.profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('web.profile.update');
    Route::get('/profile/measurements', [ProfileController::class, 'measurements'])->name('web.profile.measurements');
    Route::get('/profile/measurements/create', [ProfileController::class, 'createMeasurement'])->name('web.profile.measurements.create');
    Route::post('/profile/measurements', [ProfileController::class, 'storeMeasurement'])->name('web.profile.measurements.store');
    Route::get('/profile/measurements/{measurement}/edit', [ProfileController::class, 'editMeasurement'])->name('web.profile.measurements.edit');
    Route::put('/profile/measurements/{measurement}', [ProfileController::class, 'updateMeasurement'])->name('web.profile.measurements.update');
    Route::delete('/profile/measurements/{measurement}', [ProfileController::class, 'destroyMeasurement'])->name('web.profile.measurements.destroy');
    Route::get('/profile/addresses', [ProfileController::class, 'addresses'])->name('web.profile.addresses');
    Route::post('/profile/addresses', [ProfileController::class, 'storeAddress'])->name('web.profile.addresses.store');
    Route::delete('/profile/addresses/{address}', [ProfileController::class, 'destroyAddress'])->name('web.profile.addresses.destroy');
});
