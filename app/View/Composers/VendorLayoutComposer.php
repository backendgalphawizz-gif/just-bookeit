<?php

namespace App\View\Composers;

use App\Models\PlatformSetting;
use App\Services\NotificationInboxService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class VendorLayoutComposer
{
    public function compose(View $view): void
    {
        $vendor = Auth::guard('vendor')->user()?->fresh();
        $menu = collect(config('vendor_menu', []))->map(function (array $item) {
            if (isset($item['children'])) {
                $item['children'] = collect($item['children'])->map(function (array $child) {
                    $child['active'] = request('type') === ($child['params']['type'] ?? null)
                        && request()->routeIs('vendor.products.*');

                    return $child;
                })->all();
                $item['active'] = collect($item['children'])->contains(fn ($c) => $c['active']);
            } else {
                $item['active'] = $this->routeMatches($item['match'] ?? []);
            }

            return $item;
        });

        $vendorNotificationUnread = 0;
        $vendorNotifications = collect();

        if ($vendor) {
            $inbox = app(NotificationInboxService::class);
            $vendorNotificationUnread = $inbox->unreadCount(
                NotificationInboxService::TYPE_VENDOR,
                $vendor->id
            );
            $vendorNotifications = $inbox->paginate(
                NotificationInboxService::TYPE_VENDOR,
                $vendor->id,
                8
            )->getCollection();
        }

        $view->with([
            'vendorUser' => $vendor,
            'vendorMenu' => $menu,
            'vendorNotificationUnread' => $vendorNotificationUnread,
            'vendorNotifications' => $vendorNotifications,
            'vendorBranding' => [
                'name' => PlatformSetting::get('platform_name', 'Just Book IT'),
                'logo_url' => PlatformSetting::mediaUrl('vendor_logo') ?? PlatformSetting::mediaUrl('admin_logo'),
            ],
        ]);
    }

    /** @param array<int, string> $patterns */
    protected function routeMatches(array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (request()->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    }
}
