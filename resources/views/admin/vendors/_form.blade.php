@php
    $vendor = $vendor ?? null;
    $categories = $categories ?? collect();
    $selectedCategoryIds = old(
        'category_ids',
        $vendor
            ? \App\Models\Category::query()->whereIn('name', $vendor->categories ?? [])->pluck('id')->all()
            : []
    );
    $initials = collect(preg_split('/\s+/u', trim($vendor?->brand_name ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [])
        ->take(2)
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->implode('') ?: '?';
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
    ])
</div>

<p class="jb-form-section-title sm:col-span-2">Business details</p>
@include('admin.partials.form-input', ['label' => 'Shop name', 'name' => 'shop_name', 'value' => old('shop_name', $vendor?->shop_name ?? $vendor?->brand_name), 'maxChars' => 100, 'restrict' => 'title'])
@include('admin.partials.form-input', ['label' => 'Brand name', 'name' => 'brand_name', 'value' => old('brand_name', $vendor?->brand_name), 'required' => true, 'maxChars' => 100, 'restrict' => 'title'])
@include('admin.partials.form-input', ['label' => 'Owner name', 'name' => 'owner_name', 'value' => old('owner_name', $vendor?->owner_name), 'required' => true, 'maxChars' => 100, 'restrict' => 'person-name'])
@include('admin.partials.form-input', ['label' => 'Mobile No', 'name' => 'mobile', 'value' => old('mobile', $vendor?->mobile), 'required' => true, 'restrict' => 'phone', 'hint' => '10 digits'])
@include('admin.partials.form-input', ['label' => 'Email ID', 'name' => 'email', 'type' => 'email', 'value' => old('email', $vendor?->email), 'required' => true])
@include('admin.partials.form-input', ['label' => 'Service types', 'name' => 'service_types', 'value' => old('service_types', $vendor?->service_types), 'full' => true, 'placeholder' => 'Fashion Designer, Rented Dress, Rented Jewellery'])
@include('admin.partials.form-input', ['label' => 'Business Mobile No', 'name' => 'business_mobile', 'value' => old('business_mobile', $vendor?->business_mobile), 'restrict' => 'phone', 'hint' => '10 digits'])
@include('admin.partials.form-input', ['label' => 'Business Email ID', 'name' => 'business_email', 'type' => 'email', 'value' => old('business_email', $vendor?->business_email)])
@include('admin.partials.form-input', ['label' => 'GST number', 'name' => 'gst_number', 'value' => old('gst_number', $vendor?->gst_number), 'restrict' => 'gst', 'placeholder' => '15-character GSTIN'])

@include('admin.partials.address-fields', ['values' => [
    'address' => $vendor?->address,
    'country' => $vendor?->country,
    'state' => $vendor?->state,
    'city' => $vendor?->city,
    'pincode' => $vendor?->pincode,
]])

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

<p class="jb-form-section-title sm:col-span-2">Categories & admin stats</p>
<div class="sm:col-span-2">
    <label for="category_ids" class="jb-label">Categories</label>
    <select id="category_ids" name="category_ids[]" class="jb-select" multiple size="{{ min(max($categories->count(), 3), 8) }}">
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected(in_array($category->id, (array) $selectedCategoryIds))>
                {{ $category->name }}@if($category->type === 'service') (Service)@endif
            </option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-slate-500">Hold Ctrl (Windows) or Cmd (Mac) to select multiple categories.</p>
    @error('category_ids')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
    @error('category_ids.*')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
</div>

<x-admin.form-select label="Status" name="status" :required="true">
    @foreach (['pending', 'active', 'suspended', 'rejected'] as $s)
        <option value="{{ $s }}" @selected(old('status', $vendor?->status ?? 'pending') === $s)>{{ ucfirst($s) }}</option>
    @endforeach
</x-admin.form-select>
@include('admin.partials.form-input', ['label' => 'Rating', 'name' => 'rating', 'type' => 'number', 'step' => '0.1', 'min' => '0', 'max' => '5', 'value' => old('rating', $vendor?->rating ?? 0)])
@include('admin.partials.form-input', ['label' => 'Orders completed', 'name' => 'orders_completed', 'type' => 'number', 'step' => '1', 'value' => old('orders_completed', $vendor?->orders_completed ?? 0)])
@include('admin.partials.form-input', ['label' => 'Earnings (₹)', 'name' => 'earnings', 'type' => 'number', 'step' => '0.01', 'value' => old('earnings', $vendor?->earnings ?? 0)])
