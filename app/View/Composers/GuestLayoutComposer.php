<?php

namespace App\View\Composers;

use App\Models\PlatformSetting;
use Illuminate\View\View;

class GuestLayoutComposer
{
    public function compose(View $view): void
    {
        $logoUrl = PlatformSetting::mediaUrl('admin_logo');

        $view->with([
            'loginBranding' => [
                'name' => PlatformSetting::get('platform_name', 'Just Book IT'),
                'logo_url' => $logoUrl ?: asset('images/just-book-it-logo.png'),               
            ],
        ]);
    }
}
