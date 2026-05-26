<?php

namespace App\Services\Admin;

use App\Models\PlatformSetting;

class AdminThemeService
{
    public function variables(): array
    {
        return [
            'primary' => PlatformSetting::get('theme_primary_color', '#be123c'),
            'primary_hover' => PlatformSetting::get('theme_primary_hover', '#9f1239'),
            'sidebar_bg' => PlatformSetting::get('theme_sidebar_bg', '#0f172a'),
            'sidebar_hover' => PlatformSetting::get('theme_sidebar_hover', '#1e293b'),
            'sidebar_text' => PlatformSetting::get('theme_sidebar_text', '#e2e8f0'),
            'topbar_bg' => PlatformSetting::get('theme_topbar_bg', '#ffffff'),
        ];
    }
}
