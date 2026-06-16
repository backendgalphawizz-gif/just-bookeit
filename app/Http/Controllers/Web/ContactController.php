<?php

namespace App\Http\Controllers\Web;

use App\Models\PlatformSetting;
use Illuminate\View\View;

class ContactController extends WebController
{
    public function index(): View
    {
        return view('web.contact.index', [
            'supportEmail' => PlatformSetting::get('support_email'),
            'supportPhone' => PlatformSetting::get('support_phone'),
            'contactAddress' => PlatformSetting::get('contact_address'),
        ]);
    }
}
