<?php

namespace App\View\Components\Web;

use App\Models\PlatformSetting;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Logo extends Component
{
    public ?string $logoUrl;

    public string $platformName;

    public function __construct(
        public string $variant = 'header',
    ) {
        $this->logoUrl = PlatformSetting::mediaUrl('website_logo')
            ?? PlatformSetting::mediaUrl('admin_logo');
        $this->platformName = (string) PlatformSetting::get('platform_name', 'Just Book IT');
    }

    public function render(): View|Closure|string
    {
        return view('components.web.logo');
    }
}
