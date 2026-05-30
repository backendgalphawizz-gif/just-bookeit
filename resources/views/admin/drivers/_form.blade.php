@php
    $driver = $driver ?? null;
    $initials = collect(preg_split('/\s+/u', trim($driver?->name ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [])
        ->take(2)
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->implode('') ?: '?';
@endphp
@include('admin.partials.profile-photo-upload', [
    'currentUrl' => $driver?->profileImageUrl(),
    'initials' => $initials,
])
@include('admin.partials.form-input', ['label' => 'Full name', 'name' => 'name', 'value' => old('name', $driver?->name), 'required' => true])
@include('admin.partials.form-input', ['label' => 'Mobile', 'name' => 'mobile', 'value' => old('mobile', $driver?->mobile), 'required' => true, 'restrict' => 'phone'])
@include('admin.partials.form-input', ['label' => 'Email', 'name' => 'email', 'type' => 'email', 'value' => old('email', $driver?->email)])
@include('admin.partials.form-input', ['label' => 'City', 'name' => 'city', 'value' => old('city', $driver?->city)])

<div>
    <label for="status" class="jb-label">Status</label>
    <select id="status" name="status" class="jb-select" required>
        @foreach (['pending', 'active', 'suspended', 'rejected'] as $status)
            <option value="{{ $status }}" @selected(old('status', $driver?->status ?? 'pending') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
</div>

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

<div class="jb-checkbox-row sm:col-span-2">
    <input type="checkbox" name="is_verified" value="1" @checked(old('is_verified', $driver?->is_verified ?? false))>
    <label class="text-sm font-medium text-slate-700">Verified driver</label>
</div>
