<?php

namespace App\Providers;

use App\Models\ChatMessage;
use App\Models\Order;
use App\Observers\ChatMessageObserver;
use App\Observers\OrderObserver;
use App\Support\AdminListOrder;
use App\View\Composers\AdminLayoutComposer;
use App\View\Composers\GuestLayoutComposer;
use App\View\Composers\VendorLayoutComposer;
use App\View\Composers\WebLayoutComposer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\URL;
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
        Broadcast::routes(['middleware' => ['web', 'auth:customer,vendor']]);

        if (! $this->app->runningInConsole()) {
            $request = request();

            if ($request->hasHeader('Host')) {
                URL::forceRootUrl($request->getSchemeAndHttpHost());
            }
        }

        Builder::macro('newestFirst', function (string $column = 'created_at') {
            return AdminListOrder::newestFirst($this, $column);
        });

        Builder::macro('latestIdFirst', function () {
            return AdminListOrder::latestIdFirst($this);
        });

        Order::observe(OrderObserver::class);
        ChatMessage::observe(ChatMessageObserver::class);

        View::composer('admin.layouts.app', AdminLayoutComposer::class);
        View::composer(['admin.layouts.guest', 'admin.auth.login'], GuestLayoutComposer::class);
        View::composer([
            'web.layouts.app',
            'web.layouts.guest',
            'web.layouts.profile',
            'web.*',
            'web.*.*',
            'web.*.*.*',
        ], WebLayoutComposer::class);
        View::composer(['vendor.layouts.app', 'vendor.layouts.guest'], VendorLayoutComposer::class);
        Paginator::defaultView('vendor.pagination.admin');
        Paginator::defaultSimpleView('vendor.pagination.admin');
    }
}
