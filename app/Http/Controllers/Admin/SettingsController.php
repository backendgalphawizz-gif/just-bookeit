<?php

namespace App\Http\Controllers\Admin;

use App\Models\PlatformSetting;
use App\Support\AdminValidationRules;
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
        $allowed = ['branding', 'theme', 'contact', 'legal', 'features', 'commission'];
        if (! in_array($tab, $allowed, true)) {
            $tab = 'branding';
        }

        $settings = PlatformSetting::query()->get()->groupBy('group');

        return view('admin.settings.index', [
            'tab' => $tab,
            'settings' => $settings,
            'values' => PlatformSetting::query()->pluck('value', 'key'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeAdmin('edit');

        $tab = $request->string('tab', 'branding')->toString();

        match ($tab) {
            'branding' => $this->updateBranding($request),
            'theme' => $this->updateTheme($request),
            'contact' => $this->updateContact($request),
            'legal' => $this->updateLegal($request),
            'features' => $this->updateFeatures($request),
            'commission' => $this->updateCommission($request),
            default => null,
        };

        return redirect()
            ->route('admin.settings.index', ['tab' => $tab])
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
        $data = $request->validate(
            AdminValidationRules::settingsLegal(),
            AdminValidationRules::messages(),
            AdminValidationRules::attributes()
        );

        foreach ($data as $key => $value) {
            PlatformSetting::set($key, $value ?? '', 'legal', 'textarea');
        }
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
}
