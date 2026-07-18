<?php

use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CheckoutOrderController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DisputeController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PayoutController;
use App\Http\Controllers\Admin\WithdrawalController;
use App\Http\Controllers\Admin\PortfolioController;
use App\Http\Controllers\Admin\VendorPortfolioController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\DamageDeductionSettingsController;
use App\Http\Controllers\Admin\ListExportController;
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
            Route::get('list-export/{module}', ListExportController::class)
                ->where('module', 'customers|vendors|drivers|categories|orders|refunds|disputes|payments|payouts|portfolio|banners|faqs|notifications|admins|roles')
                ->name('list-export');

            Route::resource('customers', CustomerController::class);
            Route::resource('vendors', VendorController::class);
            Route::post('vendors/bulk-approve', [VendorController::class, 'bulkApprove'])->name('vendors.bulk-approve');
            Route::post('vendors/{vendor}/approve', [VendorController::class, 'approve'])->name('vendors.approve');
            Route::post('vendors/{vendor}/reject', [VendorController::class, 'reject'])->name('vendors.reject');
            Route::post('vendors/{vendor}/inactivate', [VendorController::class, 'inactivate'])->name('vendors.inactivate');
            Route::post('vendors/{vendor}/activate', [VendorController::class, 'activate'])->name('vendors.activate');

            Route::resource('drivers', DriverController::class);
            Route::post('drivers/{driver}/approve', [DriverController::class, 'approve'])->name('drivers.approve');
            Route::post('drivers/{driver}/reject', [DriverController::class, 'reject'])->name('drivers.reject');
            Route::post('drivers/{driver}/inactivate', [DriverController::class, 'inactivate'])->name('drivers.inactivate');

            Route::resource('categories', CategoryController::class)->except(['show']);
            Route::resource('orders', OrderController::class);
            Route::resource('checkout-orders', CheckoutOrderController::class)->only(['index', 'show']);
            Route::resource('refunds', RefundController::class);
            Route::resource('disputes', DisputeController::class);
            Route::get('banners/{banner}/preview', [BannerController::class, 'preview'])->name('banners.preview');
            Route::resource('banners', BannerController::class)->except(['show']);
            Route::resource('faqs', FaqController::class)->except(['show']);

            Route::get('contact-messages', [ContactMessageController::class, 'index'])->name('contact-messages.index');
            Route::get('contact-messages/{contactMessage}', [ContactMessageController::class, 'show'])->name('contact-messages.show');
            Route::post('contact-messages/{contactMessage}/mark-read', [ContactMessageController::class, 'markRead'])->name('contact-messages.mark-read');
            Route::delete('contact-messages/{contactMessage}', [ContactMessageController::class, 'destroy'])->name('contact-messages.destroy');

            Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
            Route::get('payments/{order}', [PaymentController::class, 'show'])->name('payments.show');

            Route::get('payouts', [PayoutController::class, 'index'])->name('payouts.index');
            Route::get('payouts/{payout}', [PayoutController::class, 'show'])->name('payouts.show');
            Route::post('payouts/{payout}/mark-paid', [PayoutController::class, 'markPaid'])->name('payouts.mark-paid');

            Route::get('withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
            Route::get('withdrawals/{withdrawal}', [WithdrawalController::class, 'show'])->name('withdrawals.show');
            Route::post('withdrawals/{withdrawal}/approve', [WithdrawalController::class, 'approve'])->name('withdrawals.approve');
            Route::post('withdrawals/{withdrawal}/reject', [WithdrawalController::class, 'reject'])->name('withdrawals.reject');

            Route::get('vendor-portfolio', [VendorPortfolioController::class, 'index'])->name('vendor-portfolio.index');
            Route::get('vendor-portfolio/{vendor}', [VendorPortfolioController::class, 'show'])->name('vendor-portfolio.show');

            Route::get('portfolio/create', [PortfolioController::class, 'create'])->name('portfolio.create');
            Route::post('portfolio', [PortfolioController::class, 'store'])->name('portfolio.store');
            Route::get('portfolio', [PortfolioController::class, 'index'])->name('portfolio.index');
            Route::get('portfolio/{portfolio}/edit', [PortfolioController::class, 'edit'])->name('portfolio.edit');
            Route::put('portfolio/{portfolio}', [PortfolioController::class, 'update'])->name('portfolio.update');
            Route::delete('portfolio/{portfolio}/images/{image}', [PortfolioController::class, 'destroyImage'])->name('portfolio.images.destroy');
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
            Route::get('settings/damage-deduction', [DamageDeductionSettingsController::class, 'index'])->name('settings.damage-deduction.index');
            Route::put('settings/damage-deduction', [DamageDeductionSettingsController::class, 'update'])->name('settings.damage-deduction.update');

            Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
            Route::resource('roles', RoleController::class)->except(['show']);
            Route::resource('admins', AdminUserController::class)->except(['show']);

            Route::post('orders/{order}/update-status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
            Route::post('orders/{order}/manage', [OrderController::class, 'manage'])->name('orders.manage');
            Route::post('customers/{customer}/activate', [CustomerController::class, 'activate'])->name('customers.activate');
            Route::post('customers/{customer}/inactivate', [CustomerController::class, 'inactivate'])->name('customers.inactivate');
            Route::post('refunds/{refund}/approve', [RefundController::class, 'approve'])->name('refunds.approve');
            Route::post('refunds/{refund}/reject', [RefundController::class, 'reject'])->name('refunds.reject');
            Route::post('refunds/{refund}/process', [RefundController::class, 'process'])->name('refunds.process');
            Route::post('disputes/{dispute}/messages', [DisputeController::class, 'sendMessage'])->name('disputes.messages');
            Route::post('disputes/{dispute}/resolve', [DisputeController::class, 'resolve'])->name('disputes.resolve');
            Route::post('disputes/{dispute}/close', [DisputeController::class, 'close'])->name('disputes.close');
        });
    });
});
