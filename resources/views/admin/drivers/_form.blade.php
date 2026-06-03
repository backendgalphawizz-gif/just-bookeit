@php
    $driver = $driver ?? null;
    $initials = collect(preg_split('/\s+/u', trim($driver?->name ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [])
        ->take(2)
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->implode('') ?: '?';
@endphp

<p class="jb-form-section-title sm:col-span-2">Profile</p>
@include('admin.partials.profile-photo-upload', [
    'currentUrl' => $driver?->profileImageUrl(),
    'initials' => $initials,
])

<p class="jb-form-section-title sm:col-span-2">Personal details</p>
@include('admin.partials.form-input', ['label' => 'Full name', 'name' => 'name', 'value' => old('name', $driver?->name), 'required' => true, 'maxChars' => 100, 'restrict' => 'person-name'])
@include('admin.partials.form-input', ['label' => 'Mobile No', 'name' => 'mobile', 'value' => old('mobile', $driver?->mobile), 'required' => true, 'restrict' => 'phone', 'hint' => '10 digits'])
@include('admin.partials.form-input', ['label' => 'Email ID', 'name' => 'email', 'type' => 'email', 'value' => old('email', $driver?->email)])
@include('admin.partials.form-input', ['label' => 'City', 'name' => 'city', 'value' => old('city', $driver?->city)])
@include('admin.partials.form-input', ['label' => 'Vehicle number', 'name' => 'vehicle_no', 'value' => old('vehicle_no', $driver?->vehicle_no), 'restrict' => 'vehicle-no', 'hint' => 'Max 20 characters. Required before selecting account type.'])

<div>
    <label for="status" class="jb-label">Status</label>
    <select id="status" name="status" class="jb-select" required>
        @foreach (['pending', 'active', 'suspended', 'rejected'] as $status)
            <option value="{{ $status }}" @selected(old('status', $driver?->status ?? 'pending') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
</div>

<div class="jb-checkbox-row sm:col-span-2">
    <input type="checkbox" name="is_verified" value="1" @checked(old('is_verified', $driver?->is_verified ?? false))>
    <label class="text-sm font-medium text-slate-700">Verified driver</label>
</div>

<p class="jb-form-section-title sm:col-span-2">KYC documents</p>
<div class="sm:col-span-2">
    @include('admin.partials.image-upload', [
        'label' => 'Aadhar front',
        'name' => 'aadhar_front',
        'currentUrl' => $driver?->aadharFrontUrl(),
    ])
</div>
<div class="sm:col-span-2">
    @include('admin.partials.image-upload', [
        'label' => 'Aadhar back',
        'name' => 'aadhar_back',
        'currentUrl' => $driver?->aadharBackUrl(),
    ])
</div>
<div class="sm:col-span-2">
    @include('admin.partials.image-upload', [
        'label' => 'Driving licence',
        'name' => 'driving_licence',
        'currentUrl' => $driver?->drivingLicenceUrl(),
    ])
</div>
@if ($driver?->aadharUrl())
    <div class="sm:col-span-2">
        @include('admin.partials.image-upload', [
            'label' => 'Aadhar (legacy)',
            'name' => 'aadhar',
            'currentUrl' => $driver->aadharUrl(),
        ])
    </div>
@endif

@include('admin.partials.bank-details-fields', ['values' => [
    'account_name' => $driver?->account_name,
    'account_number' => $driver?->account_number,
    'ifsc_code' => $driver?->ifsc_code,
    'bank_name' => $driver?->bank_name,
    'account_type' => $driver?->account_type,
]])
