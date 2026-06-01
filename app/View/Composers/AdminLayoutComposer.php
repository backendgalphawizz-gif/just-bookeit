<?php

namespace App\View\Composers;

use App\Models\PlatformSetting;
use App\Services\Admin\AdminMenuBuilder;
use App\Services\Admin\AdminThemeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminLayoutComposer
{
    public function __construct(
        protected AdminMenuBuilder $menuBuilder,
        protected AdminThemeService $themeService
    ) {}

    public function compose(View $view): void
    {
        $admin = Auth::guard('admin')->user();

        if ($admin) {
            $admin->load(['role.permissions', 'assignedCities']);
        }

        $view->with([
            'adminMenu' => $admin ? $this->menuBuilder->build($admin) : collect(),
            'adminBranding' => [
                'name' => PlatformSetting::get('platform_name', 'Just Book IT'),
                'logo_url' => PlatformSetting::mediaUrl('admin_logo'),
            ],
            'adminTheme' => $this->themeService->variables(),
        ]);
    }
}
