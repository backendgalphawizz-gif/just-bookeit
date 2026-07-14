<?php

namespace App\View\Composers;

use App\Models\Banner;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Customer;
use App\Models\PlatformSetting;
use App\Services\NotificationInboxService;
use App\Support\WebLocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class WebLayoutComposer
{
    public function compose(View $view): void
    {
        $request = request();
        $customer = Auth::guard('customer')->user();

        $addresses = ($customer instanceof Customer && ! $customer->is_guest)
            ? $customer->addresses()->orderByDesc('is_default')->orderByDesc('id')->get()
            : collect();

        $webNotificationUnread = 0;
        $webNotifications = collect();

        if ($customer instanceof Customer && ! $customer->is_guest) {
            $inbox = app(NotificationInboxService::class);
            $webNotificationUnread = $inbox->unreadCount(
                NotificationInboxService::TYPE_CUSTOMER,
                $customer->id
            );
            $webNotifications = $inbox->paginate(
                NotificationInboxService::TYPE_CUSTOMER,
                $customer->id,
                8
            )->getCollection();
        }

        $webCartCount = 0;

        if ($customer instanceof Customer && ! $customer->is_guest) {
            $webCartCount = (int) CartItem::query()
                ->where('customer_id', $customer->id)
                ->sum('quantity');
        }

        $view->with([
            'webCustomer' => $customer,
            'webNavCategories' => Category::query()
                ->active()
                ->main()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit(8)
                ->get(),
            'webServiceCategories' => Category::query()
                ->active()
                ->service()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'webActiveBanner' => Banner::query()
                ->forAudience(Banner::AUDIENCE_CUSTOMER)
                ->published()
                ->latest('id')
                ->first(),
            'webBranding' => [
                'name' => PlatformSetting::get('platform_name', 'Just Book IT'),
                'logo_url' => PlatformSetting::mediaUrl('website_logo')
                    ?? PlatformSetting::mediaUrl('admin_logo'),
            ],
            'webContact' => [
                'email' => PlatformSetting::get('support_email'),
                'phone' => PlatformSetting::get('support_phone'),
                'address' => PlatformSetting::get('contact_address'),
            ],
            'webLocationLabel' => WebLocation::label($request),
            'webLocationCurrent' => WebLocation::get($request),
            'webLocationCities' => Cache::remember('web_location_cities', 3600, fn () => WebLocation::cityOptions()),
            'webLocationAddresses' => $addresses,
            'webNotificationUnread' => $webNotificationUnread,
            'webNotifications' => $webNotifications,
            'webCartCount' => $webCartCount,
        ]);
    }
}
