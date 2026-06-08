<?php

namespace App\Providers;

use App\View\Composers\AdminLayoutComposer;
use App\View\Composers\GuestLayoutComposer;
use App\View\Composers\VendorLayoutComposer;
use App\View\Composers\WebLayoutComposer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('admin.layouts.app', AdminLayoutComposer::class);
        View::composer(['admin.layouts.guest', 'admin.auth.login'], GuestLayoutComposer::class);
        View::composer(['web.layouts.app', 'web.layouts.guest', 'web.layouts.profile'], WebLayoutComposer::class);
        View::composer(['vendor.layouts.app', 'vendor.layouts.guest'], VendorLayoutComposer::class);
        Paginator::defaultView('vendor.pagination.admin');
        Paginator::defaultSimpleView('vendor.pagination.admin');
    }
}
