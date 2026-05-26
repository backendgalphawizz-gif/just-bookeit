<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['group' => 'branding', 'key' => 'platform_name', 'value' => 'Just Book IT', 'type' => 'text'],
            ['group' => 'branding', 'key' => 'admin_logo', 'value' => null, 'type' => 'file'],
            ['group' => 'branding', 'key' => 'vendor_logo', 'value' => null, 'type' => 'file'],
            ['group' => 'branding', 'key' => 'website_logo', 'value' => null, 'type' => 'file'],
            ['group' => 'contact', 'key' => 'support_email', 'value' => 'support@justbookit.com', 'type' => 'text'],
            ['group' => 'contact', 'key' => 'support_phone', 'value' => '+91 9876543210', 'type' => 'text'],
            ['group' => 'contact', 'key' => 'contact_address', 'value' => 'India', 'type' => 'text'],
            ['group' => 'legal', 'key' => 'terms_conditions_user', 'value' => 'Terms and Conditions for customers using Just Book IT.', 'type' => 'textarea'],
            ['group' => 'legal', 'key' => 'terms_conditions_vendor', 'value' => 'Terms and Conditions for vendors/designers on Just Book IT.', 'type' => 'textarea'],
            ['group' => 'legal', 'key' => 'privacy_policy_user', 'value' => 'Privacy Policy for customers.', 'type' => 'textarea'],
            ['group' => 'legal', 'key' => 'privacy_policy_vendor', 'value' => 'Privacy Policy for vendors.', 'type' => 'textarea'],
            ['group' => 'legal', 'key' => 'about_us', 'value' => 'Just Book IT connects customers with fashion designers and rental services.', 'type' => 'textarea'],
            ['group' => 'legal', 'key' => 'help_support', 'value' => 'Contact support for booking help, refunds, and vendor onboarding.', 'type' => 'textarea'],
            ['group' => 'features', 'key' => 'enable_cod', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'features', 'key' => 'enable_vendor_registration', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'features', 'key' => 'enable_guest_browse', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'features', 'key' => 'maintenance_mode', 'value' => '0', 'type' => 'boolean'],
            ['group' => 'features', 'key' => 'currency', 'value' => 'INR', 'type' => 'text'],
            ['group' => 'commission', 'key' => 'global_commission_percent', 'value' => '10', 'type' => 'text'],
            ['group' => 'theme', 'key' => 'theme_primary_color', 'value' => '#be123c', 'type' => 'text'],
            ['group' => 'theme', 'key' => 'theme_primary_hover', 'value' => '#9f1239', 'type' => 'text'],
            ['group' => 'theme', 'key' => 'theme_sidebar_bg', 'value' => '#0f172a', 'type' => 'text'],
            ['group' => 'theme', 'key' => 'theme_sidebar_hover', 'value' => '#1e293b', 'type' => 'text'],
            ['group' => 'theme', 'key' => 'theme_sidebar_text', 'value' => '#e2e8f0', 'type' => 'text'],
            ['group' => 'theme', 'key' => 'theme_topbar_bg', 'value' => '#ffffff', 'type' => 'text'],
        ];

        foreach ($defaults as $row) {
            PlatformSetting::query()->updateOrCreate(['key' => $row['key']], $row);
        }
    }
}
