@php
    $customer = $customer ?? null;
    $initials = collect(preg_split('/\s+/u', trim($customer?->name ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [])
        ->take(2)
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->implode('') ?: '?';
@endphp
@include('admin.partials.profile-photo-upload', [
    'currentUrl' => $customer?->profileImageUrl(),
    'initials' => $initials,
])
@include('admin.partials.form-input', ['label' => 'Full Name', 'name' => 'name', 'value' => old('name', $customer?->name), 'required' => true, 'restrict' => 'person-name', 'maxChars' => 100])
@include('admin.partials.form-input', ['label' => 'Mobile No', 'name' => 'mobile', 'value' => old('mobile', $customer?->mobile), 'required' => true, 'restrict' => 'phone', 'hint' => '10 digits required'])
@include('admin.partials.form-input', ['label' => 'Email ID', 'name' => 'email', 'type' => 'email', 'value' => old('email', $customer?->email)])
@include('admin.partials.form-input', ['label' => 'City', 'name' => 'city', 'value' => old('city', $customer?->city)])
@php
    $isEdit = (bool) ($customer?->exists ?? false);
    $currentStatus = old('status', $customer?->status ?? 'active');
@endphp
@if ($isEdit)
    <div class="sm:col-span-2">
        <label class="jb-label">Status</label>
        <div class="mt-1.5 flex flex-wrap items-center gap-3">
            @include('admin.components.status-badge', ['status' => $currentStatus])
            <input type="hidden" name="status" value="{{ $currentStatus }}">
        </div>
        <p class="mt-2 text-sm text-slate-500">Suspend or block this customer from their profile page — status cannot be changed here.</p>
    </div>
@else
    <x-admin.form-select label="Status" name="status" :required="true">
        @foreach (['active', 'suspended', 'blocked'] as $s)
            <option value="{{ $s }}" @selected($currentStatus === $s)>{{ ucfirst($s) }}</option>
        @endforeach
    </x-admin.form-select>
@endif
@include('admin.partials.form-input', [
    'label' => 'Registered At',
    'name' => 'registered_at',
    'type' => 'date',
    'value' => old('registered_at', $customer?->registeredAtForForm() ?? now()->format('Y-m-d')),
    'min' => \App\Support\AdminValidationRules::MYSQL_MIN_TIMESTAMP_DATE,
    'max' => \App\Support\AdminValidationRules::listDateMax(),
    'hint' => 'Must be between Jan 1, 1970 and today.',
])
<div class="jb-checkbox-row sm:col-span-2">
    <input type="checkbox" name="is_verified" value="1" id="is_verified" @checked(old('is_verified', $customer?->is_verified ?? false))>
    <label for="is_verified" class="text-sm font-medium text-slate-700">Verified customer</label>
</div>
