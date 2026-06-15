@extends('admin.layouts.app')
{{-- @php
    $tabs = [
        'branding' => 'Branding & logos',
        'theme' => 'Admin theme',
        'contact' => 'Contact',
        'legal' => 'Legal & policies',
        'features' => 'Features',
        'commission' => 'Commission',
    ];
@endphp --}}
@php
    $tabs = [
        'branding' => 'Branding & logos',
        'contact' => 'Contact',
        'legal' => 'Legal & policies',
        'refund_rules' => 'Refund rules',
        'commission' => 'Commission',
    ];
@endphp

@section('title', 'Settings')
@section('page_title', 'System settings')
@section('page_subtitle', 'Logos, legal content, refund rules, contact, and platform options')
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
                @php
                    $legalTabs = [
                        'customer' => 'Customer',
                        'vendor' => 'Vendor',
                        'driver' => 'Driver',
                        'general' => 'General',
                    ];
                    $legalMeta = [
                        'customer' => [
                            'title' => 'Customer / User app',
                            'hint' => 'Terms and privacy policy shown in the customer mobile app and website.',
                        ],
                        'vendor' => [
                            'title' => 'Vendor app',
                            'hint' => 'Terms and privacy policy shown in the vendor mobile app and vendor panel.',
                        ],
                        'driver' => [
                            'title' => 'Driver app',
                            'hint' => 'Terms and privacy policy shown in the driver mobile app.',
                        ],
                        'general' => [
                            'title' => 'General content',
                            'hint' => 'About us and help content shared across apps.',
                        ],
                    ];
                @endphp

                <input type="hidden" name="legal_audience" value="{{ $legalAudience }}">

                <div class="jb-tabs-row jb-tabs-row--nested mb-6">
                    <div class="jb-tabs-list">
                        @foreach ($legalTabs as $key => $label)
                            <a href="{{ route('admin.settings.index', ['tab' => 'legal', 'audience' => $key]) }}"
                               class="jb-settings-tab {{ $legalAudience === $key ? 'jb-settings-tab--active' : '' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <section class="space-y-4">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">{{ $legalMeta[$legalAudience]['title'] }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $legalMeta[$legalAudience]['hint'] }} Manage FAQs from the FAQ menu.</p>
                    </div>

                    @if ($legalAudience === 'customer')
                        @include('admin.partials.rich-text-editor', ['label' => 'Terms & conditions', 'name' => 'terms_conditions_user', 'value' => old('terms_conditions_user', $values['terms_conditions_user'] ?? '')])
                        @include('admin.partials.rich-text-editor', ['label' => 'Privacy policy', 'name' => 'privacy_policy_user', 'value' => old('privacy_policy_user', $values['privacy_policy_user'] ?? '')])
                    @elseif ($legalAudience === 'vendor')
                        @include('admin.partials.rich-text-editor', ['label' => 'Terms & conditions', 'name' => 'terms_conditions_vendor', 'value' => old('terms_conditions_vendor', $values['terms_conditions_vendor'] ?? '')])
                        @include('admin.partials.rich-text-editor', ['label' => 'Privacy policy', 'name' => 'privacy_policy_vendor', 'value' => old('privacy_policy_vendor', $values['privacy_policy_vendor'] ?? '')])
                    @elseif ($legalAudience === 'driver')
                        @include('admin.partials.rich-text-editor', ['label' => 'Terms & conditions', 'name' => 'terms_conditions_driver', 'value' => old('terms_conditions_driver', $values['terms_conditions_driver'] ?? '')])
                        @include('admin.partials.rich-text-editor', ['label' => 'Privacy policy', 'name' => 'privacy_policy_driver', 'value' => old('privacy_policy_driver', $values['privacy_policy_driver'] ?? '')])
                    @else
                        @include('admin.partials.rich-text-editor', ['label' => 'About us', 'name' => 'about_us', 'value' => old('about_us', $values['about_us'] ?? '')])
                        @include('admin.partials.rich-text-editor', ['label' => 'Help & support content', 'name' => 'help_support', 'value' => old('help_support', $values['help_support'] ?? '')])
                    @endif
                </section>
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
            @elseif ($tab === 'refund_rules')
                <p class="mb-6 text-sm text-slate-500">Configure refund and return rules for rental outfits and purchase (sale) orders. These policies can be shown to customers and used as platform defaults when processing disputes.</p>

                <section class="space-y-6">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Customer policies</h3>
                        <p class="mt-1 text-sm text-slate-500">Refund and return wording shown to customers on the website and apps.</p>
                    </div>
                    @include('admin.partials.rich-text-editor', ['label' => 'Refund policy', 'name' => 'refund_policy_user', 'value' => old('refund_policy_user', $values['refund_policy_user'] ?? '')])
                    @include('admin.partials.rich-text-editor', ['label' => 'Return policy', 'name' => 'return_policy_user', 'value' => old('return_policy_user', $values['return_policy_user'] ?? '')])
                </section>

                <section class="mt-10 space-y-4 border-t border-slate-100 pt-8">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Rental outfit rules</h3>
                        <p class="mt-1 text-sm text-slate-500">Cancellation, late return, damage, and security deposit handling for rented clothing and jewellery.</p>
                    </div>
                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-4">
                        <input type="checkbox" name="refund_enable_rental" value="1" class="jb-checkbox-accent"
                               @checked(old('refund_enable_rental', filter_var($values['refund_enable_rental'] ?? '1', FILTER_VALIDATE_BOOLEAN)))>
                        <span class="font-medium text-slate-800">Allow refunds on rental orders</span>
                    </label>
                    <div class="grid gap-6 sm:grid-cols-2">
                        @include('admin.partials.form-input', ['label' => 'Full refund if cancelled before rental (days)', 'name' => 'refund_rental_cancel_days', 'type' => 'number', 'min' => '0', 'max' => '365', 'value' => old('refund_rental_cancel_days', $values['refund_rental_cancel_days'] ?? '3'), 'required' => true, 'hint' => 'Days before rental start date'])
                        @include('admin.partials.form-input', ['label' => 'Late return fee (₹ per day)', 'name' => 'refund_rental_late_fee_per_day', 'type' => 'number', 'step' => '0.01', 'min' => '0', 'value' => old('refund_rental_late_fee_per_day', $values['refund_rental_late_fee_per_day'] ?? '500'), 'required' => true])
                        @include('admin.partials.form-input', ['label' => 'Security deposit refund after return (days)', 'name' => 'refund_rental_deposit_days', 'type' => 'number', 'min' => '0', 'max' => '365', 'value' => old('refund_rental_deposit_days', $values['refund_rental_deposit_days'] ?? '7'), 'required' => true, 'hint' => 'Working days after item is returned and inspected'])
                    </div>
                </section>

                <section class="mt-10 space-y-4 border-t border-slate-100 pt-8">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Purchase (sale) rules</h3>
                        <p class="mt-1 text-sm text-slate-500">Refund and return windows for outfits sold outright (not rented).</p>
                    </div>
                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-4">
                        <input type="checkbox" name="refund_enable_sale" value="1" class="jb-checkbox-accent"
                               @checked(old('refund_enable_sale', filter_var($values['refund_enable_sale'] ?? '1', FILTER_VALIDATE_BOOLEAN)))>
                        <span class="font-medium text-slate-800">Allow refunds on purchase (sale) orders</span>
                    </label>
                    <div class="grid gap-6 sm:grid-cols-2">
                        @include('admin.partials.form-input', ['label' => 'Refund request window after delivery (days)', 'name' => 'refund_sale_window_days', 'type' => 'number', 'min' => '0', 'max' => '365', 'value' => old('refund_sale_window_days', $values['refund_sale_window_days'] ?? '7'), 'required' => true])
                        @include('admin.partials.form-input', ['label' => 'Return window after delivery (days)', 'name' => 'refund_sale_return_days', 'type' => 'number', 'min' => '0', 'max' => '365', 'value' => old('refund_sale_return_days', $values['refund_sale_return_days'] ?? '14'), 'required' => true])
                        @include('admin.partials.form-input', ['label' => 'Restocking fee on sale returns (%)', 'name' => 'refund_sale_restocking_percent', 'type' => 'number', 'step' => '0.01', 'min' => '0', 'max' => '100', 'value' => old('refund_sale_restocking_percent', $values['refund_sale_restocking_percent'] ?? '10'), 'required' => true, 'hint' => 'Deducted from refund if item is returned'])
                    </div>
                </section>
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
