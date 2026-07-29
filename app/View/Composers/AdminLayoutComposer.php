<?php

namespace App\View\Composers;

use App\Models\PlatformSetting;
use App\Services\Admin\AdminInboxNotificationService;
use App\Services\Admin\AdminMenuBuilder;
use App\Services\Admin\AdminThemeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminLayoutComposer
{
    public function __construct(
        protected AdminMenuBuilder $menuBuilder,
        protected AdminThemeService $themeService,
        protected AdminInboxNotificationService $inboxNotifications
    ) {}

    public function compose(View $view): void
    {
        $admin = Auth::guard('admin')->user();

        if ($admin) {
            $admin->load(['role.permissions', 'assignedCities']);
        }

        $adminInboxUnread = 0;
        $adminInboxNotifications = collect();

        if ($admin) {
            $adminInboxUnread = $this->inboxNotifications->unreadCount($admin);
            $adminInboxNotifications = $this->inboxNotifications->recent($admin, 8);
        }

        $view->with([
            'adminMenu' => $admin ? $this->menuBuilder->build($admin) : collect(),
            'adminInboxUnread' => $adminInboxUnread,
            'adminInboxNotifications' => $adminInboxNotifications,
            'adminBranding' => [
                'name' => PlatformSetting::get('platform_name', 'Just Book IT'),
                'logo_url' => PlatformSetting::mediaUrl('admin_logo'),
            ],
            'adminTheme' => $this->themeService->variables(),
        ]);
    }
}
