@php
    $vendor = $vendor ?? null;
    $categories = $categories ?? collect();
    $savedCategoryIds = $vendor
        ? \App\Models\Category::query()->whereIn('name', $vendor->categories ?? [])->pluck('id')->all()
        : [];
    $audienceCategories = $categories->where('type', 'main')->values();
    $serviceCategories = $categories->where('type', 'service')->values();
    $selectedAudienceIds = old(
        'audience_category_ids',
        $audienceCategories->whereIn('id', $savedCategoryIds)->pluck('id')->all()
    );
    $selectedServiceIds = old(
        'service_category_ids',
        $serviceCategories->whereIn('id', $savedCategoryIds)->pluck('id')->all()
    );
    $initials = collect(preg_split('/\s+/u', trim($vendor?->brand_name ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [])
        ->take(2)
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->implode('') ?: '?';
    $isEdit = (bool) ($vendor?->exists ?? false);
    $currentStatus = old('status', $vendor?->status ?? 'pending');
    $createStatusOptions = ['pending', 'active', 'inactive', 'rejected'];
    $statusHelp = match ($currentStatus) {
        'pending', 'rejected' => 'Approve or reject this vendor from their profile page — status cannot be changed here.',
        'inactive' => 'Unblock this vendor from their profile page — status cannot be changed here.',
        default => 'Block this vendor from their profile page — status cannot be changed here.',
    };
@endphp

<p class="jb-form-section-title sm:col-span-2">Profile & branding</p>
@include('admin.partials.profile-photo-upload', [
    'currentUrl' => $vendor?->profileImageUrl(),
    'initials' => $initials,
    'label' => 'Profile photo',
])
<div class="sm:col-span-2">
    @include('admin.partials.image-upload', [
        'label' => 'Shop logo',
        'name' => 'shop_logo',
        'currentUrl' => $vendor?->shopLogoUrl(),
        'hint' => 'Single brand/shop logo used across listings.',
    ])
</div>
<div class="sm:col-span-2">
    @include('admin.partials.multi-image-upload', [
        'label' => 'Shop images',
        'name' => 'shop_images',
        'existingImages' => $vendor?->shopImages ?? collect(),
        'removeField' => 'remove_shop_image_ids',
        'hint' => 'Upload multiple photos of the shop interior, exterior, or workspace.',
    ])
</div>

<p class="jb-form-section-title sm:col-span-2">Business details</p>
@include('admin.partials.form-input', ['label' => 'Shop name', 'name' => 'shop_name', 'value' => old('shop_name', $vendor?->shop_name ?? $vendor?->brand_name), 'maxChars' => 100, 'restrict' => 'title'])
@include('admin.partials.form-input', ['label' => 'Brand name', 'name' => 'brand_name', 'value' => old('brand_name', $vendor?->brand_name), 'required' => true, 'maxChars' => 100, 'restrict' => 'title'])
@include('admin.partials.form-input', ['label' => 'Owner name', 'name' => 'owner_name', 'value' => old('owner_name', $vendor?->owner_name), 'required' => true, 'maxChars' => 100, 'restrict' => 'person-name'])
@include('admin.partials.form-input', ['label' => 'Mobile No', 'name' => 'mobile', 'value' => old('mobile', $vendor?->mobile), 'required' => true, 'restrict' => 'phone', 'hint' => '10 digits'])
@include('admin.partials.form-input', ['label' => 'Email ID', 'name' => 'email', 'type' => 'email', 'value' => old('email', $vendor?->email), 'required' => true])
@include('admin.partials.form-input', ['label' => 'Business Mobile No', 'name' => 'business_mobile', 'value' => old('business_mobile', $vendor?->business_mobile), 'restrict' => 'phone', 'hint' => '10 digits'])
@include('admin.partials.form-input', ['label' => 'Business Email ID', 'name' => 'business_email', 'type' => 'email', 'value' => old('business_email', $vendor?->business_email)])
@include('admin.partials.form-input', ['label' => 'GST number', 'name' => 'gst_number', 'value' => old('gst_number', $vendor?->gst_number), 'restrict' => 'gst', 'placeholder' => '15-character GSTIN'])

@include('admin.partials.address-fields', [
    'enableGooglePlaces' => true,
    'values' => [
        'address' => $vendor?->address,
        'country' => $vendor?->country,
        'state' => $vendor?->state,
        'city' => $vendor?->city,
        'pincode' => $vendor?->pincode,
        'latitude' => $vendor?->latitude,
        'longitude' => $vendor?->longitude,
    ],
])

<p class="jb-form-section-title sm:col-span-2">KYC documents</p>
<div class="sm:col-span-2">
    @include('admin.partials.image-upload', [
        'label' => 'Aadhar front',
        'name' => 'aadhar_front',
        'currentUrl' => $vendor?->aadharFrontUrl(),
    ])
</div>
<div class="sm:col-span-2">
    @include('admin.partials.image-upload', [
        'label' => 'Aadhar back',
        'name' => 'aadhar_back',
        'currentUrl' => $vendor?->aadharBackUrl(),
    ])
</div>
<div class="sm:col-span-2">
    @include('admin.partials.image-upload', [
        'label' => 'PAN card',
        'name' => 'pan_card',
        'currentUrl' => $vendor?->panCardUrl(),
    ])
</div>

@include('admin.partials.bank-details-fields', ['values' => [
    'account_name' => $vendor?->account_name,
    'account_number' => $vendor?->account_number,
    'ifsc_code' => $vendor?->ifsc_code,
    'bank_name' => $vendor?->bank_name,
    'account_type' => $vendor?->account_type,
]])

<p class="jb-form-section-title sm:col-span-2">
    Categories & admin stats
    @if (auth('admin')->user()->hasPermission('categories', 'view'))
        <a href="{{ route('admin.categories.index') }}" class="ml-2 text-xs font-semibold text-rose-600 hover:text-rose-700">Manage categories</a>
    @endif
</p>
<div class="sm:col-span-2" data-jb-audience-categories>
    <p class="jb-label">Categories <span class="text-rose-600">*</span></p>
    <p class="mb-3 text-xs text-slate-500">Select one or more: Men, Women, Kids.</p>
    <div class="flex flex-wrap gap-4">
        @foreach ($audienceCategories as $category)
            <label class="jb-checkbox-row">
                <input
                    type="checkbox"
                    name="audience_category_ids[]"
                    value="{{ $category->id }}"
                    @checked(in_array($category->id, (array) $selectedAudienceIds))
                >
                <span>{{ $category->name }}</span>
            </label>
        @endforeach
    </div>
    @error('audience_category_ids')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
    @error('audience_category_ids.*')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
</div>
<div class="sm:col-span-2">
    @include('admin.partials.multi-select-dropdown', [
        'name' => 'service_category_ids',
        'label' => 'Service categories',
        'required' => true,
        'options' => $serviceCategories->map(fn ($category) => ['id' => $category->id, 'label' => $category->name])->all(),
        'selected' => $selectedServiceIds,
        'placeholder' => 'Select service categories',
        'hint' => 'Open the dropdown and tick one or more service categories.',
    ])
</div>

@if ($isEdit)
    <div class="sm:col-span-2">
        <label class="jb-label">Status</label>
        <div class="mt-1.5 flex flex-wrap items-center gap-3">
            @include('admin.components.status-badge', ['status' => $currentStatus, 'label' => \App\Support\AdminAccountStatus::labelFor($currentStatus)])
            <input type="hidden" name="status" value="{{ $currentStatus }}">
        </div>
        <p class="mt-2 text-sm text-slate-500">{{ $statusHelp }}</p>
    </div>
@else
    <x-admin.form-select label="Status" name="status" :required="true">
        @foreach ($createStatusOptions as $s)
            <option value="{{ $s }}" @selected($currentStatus === $s)>{{ \App\Support\AdminAccountStatus::labelFor($s) }}</option>
        @endforeach
    </x-admin.form-select>
@endif
@include('admin.partials.form-input', ['label' => 'Commission (%)', 'name' => 'commission', 'type' => 'number', 'step' => '0.01', 'min' => '0', 'max' => '100', 'value' => old('commission', $vendor?->commission), 'hint' => 'Leave blank to use global commission (10%)'])
@include('admin.partials.form-input', ['label' => 'Rating', 'name' => 'rating', 'type' => 'number', 'step' => '0.1', 'min' => '0', 'max' => '5', 'value' => old('rating', $vendor?->rating ?? 0)])
@include('admin.partials.form-input', ['label' => 'Orders completed', 'name' => 'orders_completed', 'type' => 'number', 'step' => '1', 'value' => old('orders_completed', $vendor?->orders_completed ?? 0)])
@include('admin.partials.form-input', ['label' => 'Earnings (₹)', 'name' => 'earnings', 'type' => 'number', 'step' => '0.01', 'value' => old('earnings', $vendor?->earnings ?? 0)])
