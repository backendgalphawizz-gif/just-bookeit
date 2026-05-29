<?php

namespace App\Services;

use App\Models\Faq;
use App\Models\PlatformSetting;

class PlatformConfigService
{
    public function fullConfig(): array
    {
        return [
            'user' => $this->legalFor(Faq::AUDIENCE_USER),
            'vendor' => $this->legalFor(Faq::AUDIENCE_VENDOR),
            'driver' => $this->legalFor(Faq::AUDIENCE_DRIVER),
            'about_us' => (string) PlatformSetting::get('about_us', ''),
            'help_support' => (string) PlatformSetting::get('help_support', ''),
            'contact' => [
                'email' => PlatformSetting::get('support_email'),
                'phone' => PlatformSetting::get('support_phone'),
                'address' => PlatformSetting::get('contact_address'),
            ],
            'features' => [
                'enable_cod' => (bool) PlatformSetting::get('enable_cod', false),
                'enable_vendor_registration' => (bool) PlatformSetting::get('enable_vendor_registration', false),
                'enable_guest_browse' => (bool) PlatformSetting::get('enable_guest_browse', false),
                'maintenance_mode' => (bool) PlatformSetting::get('maintenance_mode', false),
                'currency' => (string) PlatformSetting::get('currency', 'INR'),
            ],
            'branding' => [
                'platform_name' => (string) PlatformSetting::get('platform_name', 'Just Book IT'),
            ],
        ];
    }

    public function legalFor(string $audience): array
    {
        return [
            'terms_and_conditions' => (string) PlatformSetting::get("terms_conditions_{$audience}", ''),
            'privacy_policy' => (string) PlatformSetting::get("privacy_policy_{$audience}", ''),
            'faq' => $this->faqsFor($audience),
        ];
    }

    public function faqsFor(string $audience): array
    {
        return Faq::query()
            ->forAudience($audience)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'question', 'answer', 'sort_order'])
            ->map(fn (Faq $faq) => [
                'id' => $faq->id,
                'question' => $faq->question,
                'answer' => $faq->answer,
                'sort_order' => $faq->sort_order,
            ])
            ->values()
            ->all();
    }
}
