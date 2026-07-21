<?php

use App\Http\Controllers\Api\SystemController;
use App\Http\Controllers\Api\ProductOptionController;
use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\ConfigController;
use App\Http\Controllers\Api\V1\DesignerController;
use App\Http\Controllers\Api\V1\DisputeController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\MeasurementController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SupportTicketController;
use App\Http\Controllers\Api\V1\UserAuthController;
use App\Http\Controllers\Api\V2\BookingController as VendorBookingController;
use App\Http\Controllers\Api\V2\CategoryController as VendorCategoryController;
use App\Http\Controllers\Api\V2\ConfigController as VendorConfigController;
use App\Http\Controllers\Api\V2\ChatController as VendorChatController;
use App\Http\Controllers\Api\V2\HomeController as VendorHomeController;
use App\Http\Controllers\Api\V2\LocationController as VendorLocationController;
use App\Http\Controllers\Api\V2\NotificationController as VendorNotificationController;
use App\Http\Controllers\Api\V2\PaymentController as VendorPaymentController;
use App\Http\Controllers\Api\V2\PortfolioController as VendorPortfolioController;
use App\Http\Controllers\Api\V2\ProductController as VendorProductController;
use App\Http\Controllers\Api\V2\ProfileController as VendorProfileController;
use App\Http\Controllers\Api\V2\VendorAuthController;
use App\Http\Controllers\Api\V3\ConfigController as DriverConfigController;
use App\Http\Controllers\Api\V3\DeliveryController as DriverDeliveryController;
use App\Http\Controllers\Api\V3\DriverAuthController;
use App\Http\Controllers\Api\V3\HomeController as DriverHomeController;
use App\Http\Controllers\Api\V3\NotificationController as DriverNotificationController;
use App\Http\Controllers\Api\V3\PaymentController as DriverPaymentController;
use App\Http\Controllers\Api\V3\ProfileController as DriverProfileController;
use Illuminate\Support\Facades\Route;

Route::get('system/clear-cache', [SystemController::class, 'clearCache'])->name('system.clear-cache');

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('config', [ConfigController::class, 'index'])->name('config');
    Route::get('product-sizes', [ProductOptionController::class, 'sizes'])->name('product-sizes');
    Route::get('product-colors', [ProductOptionController::class, 'colors'])->name('product-colors');

    Route::get('home', [HomeController::class, 'index'])->name('home');
    Route::get('categories', [CategoryController::class, 'index'])->name('categories');
    Route::get('search', [SearchController::class, 'index'])->name('search');
    Route::get('catalog', [CatalogController::class, 'index'])->name('catalog.index');
    Route::get('catalog/{item}', [CatalogController::class, 'show'])->name('catalog.show');
    Route::get('designers', [DesignerController::class, 'index'])->name('designers.index');
    Route::get('designers/{designer}', [DesignerController::class, 'show'])->name('designers.show');
    Route::get('locations/countries', [LocationController::class, 'countries'])->name('locations.countries');
    Route::get('locations/states', [LocationController::class, 'states'])->name('locations.states');
    Route::get('locations/cities', [LocationController::class, 'cities'])->name('locations.cities');
    Route::get('locations', [LocationController::class, 'catalog'])->name('locations.catalog');

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
        Route::post('bookings/{booking}/review', [BookingController::class, 'review'])->name('bookings.review');
        Route::post('bookings/{booking}/dispute', [DisputeController::class, 'store'])->name('bookings.dispute.store');
        Route::get('bookings/{booking}/dispute', [DisputeController::class, 'show'])->name('bookings.dispute.show');
        Route::post('bookings/{booking}/dispute/messages', [DisputeController::class, 'sendMessage'])->name('bookings.dispute.messages');

        Route::get('cart', [CartController::class, 'index'])->name('cart.index');
        Route::post('cart', [CartController::class, 'store'])->name('cart.store');
        Route::delete('cart/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');

        Route::post('checkout/preview', [CheckoutController::class, 'preview'])->name('checkout.preview');
        Route::post('checkout', [CheckoutController::class, 'store'])->name('checkout.store');
        Route::get('checkout-orders', [CheckoutController::class, 'index'])->name('checkout-orders.index');
        Route::get('checkout-orders/{checkoutOrder}', [CheckoutController::class, 'show'])->name('checkout-orders.show');

        Route::get('payment/methods', [PaymentController::class, 'methods'])->name('payment.methods');
        Route::get('payment/bookings/{booking}', [PaymentController::class, 'summary'])->name('payment.summary');
        Route::post('payment/bookings/{booking}/razorpay/order', [PaymentController::class, 'createRazorpayOrder'])->name('payment.razorpay.order');
        Route::post('payment/bookings/{booking}/pay', [PaymentController::class, 'pay'])->name('payment.pay');
        Route::get('payment/checkout-orders/{checkoutOrder}', [PaymentController::class, 'checkoutSummary'])->name('payment.checkout.summary');
        Route::post('payment/checkout-orders/{checkoutOrder}/razorpay/order', [PaymentController::class, 'createCheckoutRazorpayOrder'])->name('payment.checkout.razorpay.order');
        Route::post('payment/checkout-orders/{checkoutOrder}/pay', [PaymentController::class, 'payCheckout'])->name('payment.checkout.pay');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::post('notifications/{notification}/unread', [NotificationController::class, 'markUnread'])->name('notifications.unread');

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
        Route::post('broadcasting/auth', [\Illuminate\Broadcasting\BroadcastController::class, 'authenticate'])
            ->name('broadcasting.auth');

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

    Route::get('config', [VendorConfigController::class, 'index'])->name('config');
    Route::get('product-sizes', [ProductOptionController::class, 'sizes'])->name('product-sizes');
    Route::get('product-colors', [ProductOptionController::class, 'colors'])->name('product-colors');
    Route::get('categories', [VendorCategoryController::class, 'index'])->name('categories');
    Route::get('categories/{category}/subcategories', [VendorCategoryController::class, 'subcategories'])->name('categories.subcategories');

    Route::get('locations/countries', [VendorLocationController::class, 'countries'])->name('locations.countries');
    Route::get('locations/states', [VendorLocationController::class, 'states'])->name('locations.states');
    Route::get('locations/cities', [VendorLocationController::class, 'cities'])->name('locations.cities');
    Route::get('locations', [VendorLocationController::class, 'catalog'])->name('locations.catalog');

    Route::middleware(['auth:sanctum', 'vendor.api'])->group(function () {
        Route::get('home', [VendorHomeController::class, 'index'])->name('home');

        Route::get('notifications', [VendorNotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/read-all', [VendorNotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('notifications/{notification}/read', [VendorNotificationController::class, 'markRead'])->name('notifications.read');
        Route::post('notifications/{notification}/unread', [VendorNotificationController::class, 'markUnread'])->name('notifications.unread');

        Route::get('bookings', [VendorBookingController::class, 'index'])->name('bookings.index');
        Route::get('bookings/{booking}', [VendorBookingController::class, 'show'])->name('bookings.show');
        Route::post('bookings/{booking}/accept', [VendorBookingController::class, 'accept'])->name('bookings.accept');
        Route::post('bookings/{booking}/reject', [VendorBookingController::class, 'reject'])->name('bookings.reject');
        Route::post('bookings/{booking}/items/{item}/accept', [VendorBookingController::class, 'acceptItem'])->name('bookings.items.accept');
        Route::post('bookings/{booking}/items/{item}/reject', [VendorBookingController::class, 'rejectItem'])->name('bookings.items.reject');
        Route::post('bookings/{booking}/status', [VendorBookingController::class, 'updateStatus'])->name('bookings.status');
        Route::post('bookings/{booking}/damage', [VendorBookingController::class, 'updateDamage'])->name('bookings.damage');

        Route::get('products', [VendorProductController::class, 'index'])->name('products.index');
        Route::post('products', [VendorProductController::class, 'store'])->name('products.store');
        Route::get('products/{product}', [VendorProductController::class, 'show'])->name('products.show');
        Route::match(['put', 'post'], 'products/{product}', [VendorProductController::class, 'update'])->name('products.update');
        Route::post('products/{product}/available', [VendorProductController::class, 'markAvailable'])->name('products.available');
        Route::post('products/{product}/unavailable', [VendorProductController::class, 'markUnavailable'])->name('products.unavailable');
        Route::post('products/{product}/availability', [VendorProductController::class, 'toggleAvailability'])->name('products.availability');
        Route::delete('products/{product}', [VendorProductController::class, 'destroy'])->name('products.destroy');

        Route::get('chats', [VendorChatController::class, 'index'])->name('chats.index');
        Route::post('chats', [VendorChatController::class, 'store'])->name('chats.store');
        Route::get('chats/{chat}', [VendorChatController::class, 'show'])->name('chats.show');
        Route::get('chats/{chat}/messages', [VendorChatController::class, 'messages'])->name('chats.messages');
        Route::post('chats/{chat}/messages', [VendorChatController::class, 'sendMessage'])->name('chats.messages.send');
        Route::post('broadcasting/auth', [\Illuminate\Broadcasting\BroadcastController::class, 'authenticate'])
            ->name('broadcasting.auth');

        Route::get('payments', [VendorPaymentController::class, 'index'])->name('payments.index');

        Route::get('profile', [VendorProfileController::class, 'index'])->name('profile.index');
        Route::get('profile/pages', [VendorProfileController::class, 'pages'])->name('profile.pages');
        Route::get('profile/business', [VendorProfileController::class, 'business'])->name('profile.business.show');
        Route::post('profile/business', [VendorProfileController::class, 'updateBusiness'])->name('profile.business');
        Route::get('profile/bank', [VendorProfileController::class, 'bank'])->name('profile.bank.show');
        Route::post('profile/bank', [VendorProfileController::class, 'updateBank'])->name('profile.bank');
        Route::post('profile/bio', [VendorProfileController::class, 'updateBio'])->name('profile.bio');
        Route::post('profile/availability', [VendorProfileController::class, 'toggleAvailability'])->name('profile.availability');
        Route::post('profile/available', [VendorProfileController::class, 'markAvailable'])->name('profile.available');
        Route::post('profile/unavailable', [VendorProfileController::class, 'markUnavailable'])->name('profile.unavailable');
        Route::post('profile/password', [VendorProfileController::class, 'updatePassword'])->name('profile.password');

        Route::get('portfolio', [VendorPortfolioController::class, 'index'])->name('portfolio.index');
        Route::post('portfolio', [VendorPortfolioController::class, 'store'])->name('portfolio.store');
        Route::delete('portfolio/{portfolio}', [VendorPortfolioController::class, 'destroy'])->name('portfolio.destroy');
    });
});

Route::prefix('v3')->name('api.v3.')->group(function () {
    Route::get('config', [DriverConfigController::class, 'index'])->name('config');

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

    Route::middleware(['auth:sanctum', 'driver.api'])->group(function () {
        Route::get('home', [DriverHomeController::class, 'index'])->name('home');

        Route::get('deliveries', [DriverDeliveryController::class, 'index'])->name('deliveries.index');
        Route::get('deliveries/{delivery}', [DriverDeliveryController::class, 'show'])->name('deliveries.show');
        Route::post('deliveries/{delivery}/accept', [DriverDeliveryController::class, 'accept'])->name('deliveries.accept');
        Route::post('deliveries/{delivery}/reject', [DriverDeliveryController::class, 'reject'])->name('deliveries.reject');
        Route::post('deliveries/{delivery}/pickup', [DriverDeliveryController::class, 'pickup'])->name('deliveries.pickup');
        Route::post('deliveries/{delivery}/dispatch', [DriverDeliveryController::class, 'dispatch'])->name('deliveries.dispatch');
        Route::post('deliveries/{delivery}/out-for-delivery', [DriverDeliveryController::class, 'outForDelivery'])->name('deliveries.out-for-delivery');
        Route::post('deliveries/{delivery}/delivered', [DriverDeliveryController::class, 'delivered'])->name('deliveries.delivered');
        Route::post('deliveries/{delivery}/deliver', [DriverDeliveryController::class, 'deliver'])->name('deliveries.deliver');
        Route::post('deliveries/{delivery}/rescheduled', [DriverDeliveryController::class, 'rescheduled'])->name('deliveries.rescheduled');

        Route::get('payments', [DriverPaymentController::class, 'index'])->name('payments.index');
        Route::post('payments/withdraw', [DriverPaymentController::class, 'withdraw'])->name('payments.withdraw');

        Route::get('notifications', [DriverNotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/read-all', [DriverNotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('notifications/{notification}/read', [DriverNotificationController::class, 'markRead'])->name('notifications.read');
        Route::post('notifications/{notification}/unread', [DriverNotificationController::class, 'markUnread'])->name('notifications.unread');

        Route::get('profile', [DriverProfileController::class, 'index'])->name('profile.index');
        Route::get('profile/documents', [DriverProfileController::class, 'documents'])->name('profile.documents');
        Route::get('profile/pages', [DriverProfileController::class, 'pages'])->name('profile.pages');
    });
});
