<?php

namespace App\Http\Controllers\Admin;

use App\Models\PlatformSetting;
use App\Support\AdminValidationRules;
use App\Support\RichText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends AdminController
{
    protected string $permissionModule = 'settings';

    public function index(Request $request): View
    {
        $tab = $request->string('tab', 'branding')->toString();
        $allowed = ['branding', 'theme', 'contact', 'legal', 'features', 'commission', 'refund_rules', 'discovery'];
        if (! in_array($tab, $allowed, true)) {
            $tab = 'branding';
        }

        $legalAudience = $request->string('audience', 'customer')->toString();
        if (! in_array($legalAudience, ['customer', 'vendor', 'driver', 'general'], true)) {
            $legalAudience = 'customer';
        }

        $settings = PlatformSetting::query()->get()->groupBy('group');

        return view('admin.settings.index', [
            'tab' => $tab,
            'legalAudience' => $legalAudience,
            'settings' => $settings,
            'values' => PlatformSetting::query()->pluck('value', 'key'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $tab = $request->string('tab', 'branding')->toString();

        $legalAudience = $request->string('legal_audience', 'customer')->toString();

        match ($tab) {
            'branding' => $this->updateBranding($request),
            'theme' => $this->updateTheme($request),
            'contact' => $this->updateContact($request),
            'legal' => $this->updateLegal($request),
            'features' => $this->updateFeatures($request),
            'commission' => $this->updateCommission($request),
            'refund_rules' => $this->updateRefundRules($request),
            'discovery' => $this->updateDiscovery($request),
            default => null,
        };

        $redirectParams = ['tab' => $tab];
        if ($tab === 'legal') {
            $redirectParams['audience'] = in_array($legalAudience, ['customer', 'vendor', 'driver', 'general'], true)
                ? $legalAudience
                : 'customer';
        }

        return redirect()
            ->route('admin.settings.index', $redirectParams)
            ->with('success', 'Settings saved successfully.');
    }

    protected function updateBranding(Request $request): void
    {
        $data = $request->validate(
            AdminValidationRules::settingsBranding(),
            AdminValidationRules::messages(),
            AdminValidationRules::attributes()
        );

        PlatformSetting::set('platform_name', $data['platform_name'], 'branding');

        foreach (['admin_logo', 'vendor_logo', 'website_logo'] as $field) {
            if ($request->hasFile($field)) {
                $oldPath = PlatformSetting::get($field);
                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }

                $path = $request->file($field)->store('logos', 'public');
                PlatformSetting::set($field, $path, 'branding', 'file');
            }
        }
    }

    protected function updateTheme(Request $request): void
    {
        $data = $request->validate(
            AdminValidationRules::settingsTheme(),
            AdminValidationRules::messages(),
            AdminValidationRules::attributes()
        );

        foreach ($data as $key => $value) {
            PlatformSetting::set($key, $value, 'theme');
        }
    }

    protected function updateContact(Request $request): void
    {
        $request->merge(AdminValidationRules::normalizeEmailFields($request->all()));

        $data = $request->validate(
            AdminValidationRules::settingsContact(),
            AdminValidationRules::messages(),
            AdminValidationRules::attributes()
        );

        foreach ($data as $key => $value) {
            PlatformSetting::set($key, $value, 'contact');
        }
    }

    protected function updateLegal(Request $request): void
    {
        $audience = $request->string('legal_audience', 'customer')->toString();
        if (! in_array($audience, ['customer', 'vendor', 'driver', 'general'], true)) {
            $audience = 'customer';
        }

        $data = $request->validate(
            AdminValidationRules::settingsLegalAudience($audience),
            AdminValidationRules::messages(),
            AdminValidationRules::attributes()
        );

        foreach ($data as $key => $value) {
            PlatformSetting::set($key, RichText::sanitize($value), 'legal', 'html');
        }

        PlatformSetting::set('legal_updated_at', now()->format('F j, Y'), 'legal');
    }

    protected function updateFeatures(Request $request): void
    {
        $data = $request->validate(
            AdminValidationRules::settingsFeatures(),
            AdminValidationRules::messages(),
            AdminValidationRules::attributes()
        );

        PlatformSetting::set('enable_cod', $request->boolean('enable_cod'), 'features', 'boolean');
        PlatformSetting::set('enable_vendor_registration', $request->boolean('enable_vendor_registration'), 'features', 'boolean');
        PlatformSetting::set('enable_guest_browse', $request->boolean('enable_guest_browse'), 'features', 'boolean');
        PlatformSetting::set('maintenance_mode', $request->boolean('maintenance_mode'), 'features', 'boolean');
        PlatformSetting::set('currency', $data['currency'], 'features');
    }

    protected function updateCommission(Request $request): void
    {
        $data = $request->validate(
            AdminValidationRules::settingsCommission(),
            AdminValidationRules::messages(),
            AdminValidationRules::attributes()
        );

        PlatformSetting::set('global_commission_percent', $data['global_commission_percent'], 'commission');
    }

    protected function updateDiscovery(Request $request): void
    {
        $data = $request->validate(
            AdminValidationRules::settingsDiscovery(),
            AdminValidationRules::messages(),
            AdminValidationRules::attributes()
        );

        PlatformSetting::set('discovery_radius_km', $data['discovery_radius_km'], 'discovery');
    }

    protected function updateRefundRules(Request $request): void
    {
        $data = $request->validate(
            AdminValidationRules::settingsRefundRules(),
            AdminValidationRules::messages(),
            AdminValidationRules::attributes()
        );

        PlatformSetting::set('refund_enable_rental', $request->boolean('refund_enable_rental'), 'refund_rules', 'boolean');
        PlatformSetting::set('refund_enable_sale', $request->boolean('refund_enable_sale'), 'refund_rules', 'boolean');

        PlatformSetting::set('refund_policy_user', RichText::sanitize($data['refund_policy_user'] ?? ''), 'refund_rules', 'html');
        PlatformSetting::set('return_policy_user', RichText::sanitize($data['return_policy_user'] ?? ''), 'refund_rules', 'html');

        foreach ([
            'refund_rental_cancel_days',
            'refund_rental_late_fee_per_day',
            'refund_rental_deposit_days',
            'refund_sale_window_days',
            'refund_sale_return_days',
            'refund_sale_restocking_percent',
        ] as $key) {
            PlatformSetting::set($key, $data[$key], 'refund_rules');
        }
    }
}
