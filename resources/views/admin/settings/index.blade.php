@extends('admin.layouts.app')
<!-- @php
    $tabs = [
        'branding' => 'Branding & logos',
        'theme' => 'Admin theme',
        'contact' => 'Contact',
        'legal' => 'Legal & policies',
        'features' => 'Features',
        'commission' => 'Commission',
    ];
@endphp -->

// @php
//     $tabs = [
//         'branding' => 'Branding & logos',
//         'contact' => 'Contact',
//         'legal' => 'Legal & policies',
//         
//         'commission' => 'Commission',
//     ];
// @endphp

@section('title', 'Settings')
@section('page_title', 'System settings')
@section('page_subtitle', 'Logos, legal content, contact, and platform options')
@section('content')
    <div class="jb-tabs-row">
        <div class="jb-tabs-list">
            @foreach ($tabs as $key => $label)
                <a href="{{ route('admin.settings.index', ['tab' => $key]) }}"
                   class="jb-settings-tab {{ $tab === $key ? 'jb-settings-tab--active' : '' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="jb-card">
        @csrf
        @method('PUT')
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="jb-card-header">
            <p class="jb-card-header-title">{{ $tabs[$tab] }}</p>
        </div>
        <div class="jb-card-body">
            @if ($tab === 'branding')
                <div class="grid gap-6 sm:grid-cols-2">
                    @include('admin.partials.form-input', ['label' => 'Platform name', 'name' => 'platform_name', 'value' => old('platform_name', $values['platform_name'] ?? 'Just Book IT'), 'required' => true])
                    @foreach (['admin_logo' => 'Admin panel logo', 'vendor_logo' => 'Vendor app logo', 'website_logo' => 'Website logo'] as $field => $label)
                        @include('admin.partials.logo-upload', ['name' => $field, 'label' => $label])
                    @endforeach
                </div>
            @elseif ($tab === 'theme')
                <p class="mb-4 text-sm text-slate-500">Customize admin panel colors. Changes apply after save (refresh if needed).</p>
                <div class="grid gap-6 sm:grid-cols-2">
                    @include('admin.partials.form-input', ['label' => 'Primary color', 'name' => 'theme_primary_color', 'type' => 'color', 'value' => old('theme_primary_color', $values['theme_primary_color'] ?? '#be123c'), 'required' => true])
                    @include('admin.partials.form-input', ['label' => 'Primary hover color', 'name' => 'theme_primary_hover', 'type' => 'color', 'value' => old('theme_primary_hover', $values['theme_primary_hover'] ?? '#9f1239'), 'required' => true])
                    @include('admin.partials.form-input', ['label' => 'Sidebar background', 'name' => 'theme_sidebar_bg', 'type' => 'color', 'value' => old('theme_sidebar_bg', $values['theme_sidebar_bg'] ?? '#0f172a'), 'required' => true])
                    @include('admin.partials.form-input', ['label' => 'Sidebar hover', 'name' => 'theme_sidebar_hover', 'type' => 'color', 'value' => old('theme_sidebar_hover', $values['theme_sidebar_hover'] ?? '#1e293b'), 'required' => true])
                    @include('admin.partials.form-input', ['label' => 'Sidebar text', 'name' => 'theme_sidebar_text', 'type' => 'color', 'value' => old('theme_sidebar_text', $values['theme_sidebar_text'] ?? '#e2e8f0'), 'required' => true])
                    @include('admin.partials.form-input', ['label' => 'Top bar background', 'name' => 'theme_topbar_bg', 'type' => 'color', 'value' => old('theme_topbar_bg', $values['theme_topbar_bg'] ?? '#ffffff'), 'required' => true])
                </div>
            @elseif ($tab === 'contact')
                <div class="grid gap-6 sm:grid-cols-2">
                    @include('admin.partials.form-input', ['label' => 'Support Email ID', 'name' => 'support_email', 'type' => 'email', 'value' => old('support_email', $values['support_email'] ?? ''), 'required' => true])
                    @include('admin.partials.form-input', ['label' => 'Support phone', 'name' => 'support_phone', 'value' => old('support_phone', $values['support_phone'] ?? ''), 'restrict' => 'phone', 'hint' => '10 digits'])
                    @include('admin.partials.form-input', ['label' => 'Office address', 'name' => 'contact_address', 'type' => 'textarea', 'rows' => 3, 'value' => old('contact_address', $values['contact_address'] ?? ''), 'full' => true])
                </div>
            @elseif ($tab === 'legal')
                <div class="grid gap-8">
                    <section class="space-y-4 rounded-xl border border-slate-200 p-5">
                        <h3 class="text-base font-semibold text-slate-900">Customer / User app</h3>
                        <p class="text-sm text-slate-500">Terms and privacy policy shown in the customer mobile app. Manage FAQs from the FAQ menu.</p>
                        @include('admin.partials.form-input', ['label' => 'Terms & conditions', 'name' => 'terms_conditions_user', 'type' => 'textarea', 'rows' => 8, 'value' => old('terms_conditions_user', $values['terms_conditions_user'] ?? ''), 'full' => true])
                        @include('admin.partials.form-input', ['label' => 'Privacy policy', 'name' => 'privacy_policy_user', 'type' => 'textarea', 'rows' => 8, 'value' => old('privacy_policy_user', $values['privacy_policy_user'] ?? ''), 'full' => true])
                    </section>

                    <section class="space-y-4 rounded-xl border border-slate-200 p-5">
                        <h3 class="text-base font-semibold text-slate-900">Vendor app</h3>
                        <p class="text-sm text-slate-500">Terms and privacy policy shown in the vendor mobile app. Manage FAQs from the FAQ menu.</p>
                        @include('admin.partials.form-input', ['label' => 'Terms & conditions', 'name' => 'terms_conditions_vendor', 'type' => 'textarea', 'rows' => 8, 'value' => old('terms_conditions_vendor', $values['terms_conditions_vendor'] ?? ''), 'full' => true])
                        @include('admin.partials.form-input', ['label' => 'Privacy policy', 'name' => 'privacy_policy_vendor', 'type' => 'textarea', 'rows' => 8, 'value' => old('privacy_policy_vendor', $values['privacy_policy_vendor'] ?? ''), 'full' => true])
                    </section>

                    <section class="space-y-4 rounded-xl border border-slate-200 p-5">
                        <h3 class="text-base font-semibold text-slate-900">Driver app</h3>
                        <p class="text-sm text-slate-500">Terms and privacy policy shown in the driver mobile app. Manage FAQs from the FAQ menu.</p>
                        @include('admin.partials.form-input', ['label' => 'Terms & conditions', 'name' => 'terms_conditions_driver', 'type' => 'textarea', 'rows' => 8, 'value' => old('terms_conditions_driver', $values['terms_conditions_driver'] ?? ''), 'full' => true])
                        @include('admin.partials.form-input', ['label' => 'Privacy policy', 'name' => 'privacy_policy_driver', 'type' => 'textarea', 'rows' => 8, 'value' => old('privacy_policy_driver', $values['privacy_policy_driver'] ?? ''), 'full' => true])
                    </section>

                    <section class="space-y-4 rounded-xl border border-slate-200 p-5">
                        <h3 class="text-base font-semibold text-slate-900">General</h3>
                        @include('admin.partials.form-input', ['label' => 'About us', 'name' => 'about_us', 'type' => 'textarea', 'rows' => 5, 'value' => old('about_us', $values['about_us'] ?? ''), 'full' => true])
                        @include('admin.partials.form-input', ['label' => 'Help & support content', 'name' => 'help_support', 'type' => 'textarea', 'rows' => 5, 'value' => old('help_support', $values['help_support'] ?? ''), 'full' => true])
                    </section>
                </div>
            @elseif ($tab === 'features')
                <div class="space-y-4">
                    @foreach ([
                        'enable_cod' => 'Enable cash on delivery',
                        'enable_vendor_registration' => 'Allow new vendor registration',
                        'enable_guest_browse' => 'Allow guest browsing on website',
                        'maintenance_mode' => 'Maintenance mode (block public apps)',
                    ] as $key => $label)
                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-4">
                            <input type="checkbox" name="{{ $key }}" value="1" class="jb-checkbox-accent"
                                   @checked(old($key, filter_var($values[$key] ?? '0', FILTER_VALIDATE_BOOLEAN)))>
                            <span class="font-medium text-slate-800">{{ $label }}</span>
                        </label>
                    @endforeach
                    @include('admin.partials.form-input', ['label' => 'Default currency', 'name' => 'currency', 'value' => old('currency', $values['currency'] ?? 'INR')])
                </div>
            @elseif ($tab === 'commission')
                @include('admin.partials.form-input', ['label' => 'Global commission (%)', 'name' => 'global_commission_percent', 'type' => 'number', 'step' => '0.01', 'min' => '0', 'max' => '100', 'value' => old('global_commission_percent', $values['global_commission_percent'] ?? '10'), 'required' => true])
                <p class="mt-2 text-sm text-slate-500">Applied to vendor payouts unless overridden per vendor.</p>
            @endif
        </div>
        @if (auth('admin')->user()->hasPermission('settings', 'edit'))
            <div class="border-t border-slate-100 px-6 py-4">
                <x-admin.button variant="primary" type="submit">Save settings</x-admin.button>
            </div>
        @endif
    </form>
@endsection
