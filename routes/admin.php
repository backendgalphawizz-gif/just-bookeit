<?php

use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DisputeController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PayoutController;
use App\Http\Controllers\Admin\PortfolioController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\VendorController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->prefix('admin')->name('admin.')->group(function () {
    Route::middleware('admin.guest')->group(function () {
        Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('login', [LoginController::class, 'login'])->name('login.submit');
    });

    Route::middleware('admin.auth')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('logout', [LoginController::class, 'logout'])->name('logout');

        Route::get('profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [AdminProfileController::class, 'update'])->name('profile.update');

        Route::middleware('admin.module')->group(function () {
            Route::resource('customers', CustomerController::class);
            Route::resource('vendors', VendorController::class);
            Route::post('vendors/{vendor}/approve', [VendorController::class, 'approve'])->name('vendors.approve');
            Route::post('vendors/{vendor}/reject', [VendorController::class, 'reject'])->name('vendors.reject');
            Route::post('vendors/{vendor}/suspend', [VendorController::class, 'suspend'])->name('vendors.suspend');

            Route::resource('drivers', DriverController::class);
            Route::post('drivers/{driver}/approve', [DriverController::class, 'approve'])->name('drivers.approve');
            Route::post('drivers/{driver}/reject', [DriverController::class, 'reject'])->name('drivers.reject');
            Route::post('drivers/{driver}/suspend', [DriverController::class, 'suspend'])->name('drivers.suspend');

            Route::resource('categories', CategoryController::class)->except(['show']);
            Route::resource('orders', OrderController::class);
            Route::resource('refunds', RefundController::class);
            Route::resource('disputes', DisputeController::class);
            Route::resource('banners', BannerController::class)->except(['show']);
            Route::resource('faqs', FaqController::class)->except(['show']);

            Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
            Route::get('payments/{order}', [PaymentController::class, 'show'])->name('payments.show');

            Route::get('payouts', [PayoutController::class, 'index'])->name('payouts.index');
            Route::get('payouts/{payout}', [PayoutController::class, 'show'])->name('payouts.show');
            Route::post('payouts/{payout}/mark-paid', [PayoutController::class, 'markPaid'])->name('payouts.mark-paid');

            Route::get('portfolio', [PortfolioController::class, 'index'])->name('portfolio.index');
            Route::get('portfolio/{portfolio}', [PortfolioController::class, 'show'])->name('portfolio.show');
            Route::post('portfolio/{portfolio}/approve', [PortfolioController::class, 'approve'])->name('portfolio.approve');
            Route::post('portfolio/{portfolio}/reject', [PortfolioController::class, 'reject'])->name('portfolio.reject');

            Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
            Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');

            Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
            Route::get('notifications/create', [NotificationController::class, 'create'])->name('notifications.create');
            Route::post('notifications', [NotificationController::class, 'store'])->name('notifications.store');
            Route::get('notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');

            Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

            Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
            Route::resource('roles', RoleController::class)->except(['show']);
            Route::resource('admins', AdminUserController::class)->except(['show']);

            Route::post('orders/{order}/update-status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
            Route::post('orders/{order}/manage', [OrderController::class, 'manage'])->name('orders.manage');
            Route::post('customers/{customer}/activate', [CustomerController::class, 'activate'])->name('customers.activate');
            Route::post('customers/{customer}/suspend', [CustomerController::class, 'suspend'])->name('customers.suspend');
            Route::post('refunds/{refund}/approve', [RefundController::class, 'approve'])->name('refunds.approve');
            Route::post('refunds/{refund}/reject', [RefundController::class, 'reject'])->name('refunds.reject');
            Route::post('refunds/{refund}/process', [RefundController::class, 'process'])->name('refunds.process');
            Route::post('disputes/{dispute}/resolve', [DisputeController::class, 'resolve'])->name('disputes.resolve');
            Route::post('disputes/{dispute}/close', [DisputeController::class, 'close'])->name('disputes.close');
        });
    });
});
