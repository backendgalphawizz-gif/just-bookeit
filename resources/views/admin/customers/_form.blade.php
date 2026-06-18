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

@include('admin.partials.address-fields', ['values' => [
    'address' => $customer?->address,
    'country' => $customer?->country,
    'state' => $customer?->state,
    'city' => $customer?->city,
    'pincode' => $customer?->pincode,
]])
@php
    $isEdit = (bool) ($customer?->exists ?? false);
    $currentStatus = old('status', $customer?->status ?? 'active');
@endphp
@if ($isEdit)
    <div class="sm:col-span-2">
        <label class="jb-label">Status</label>
        <div class="mt-1.5 flex flex-wrap items-center gap-3">
            @include('admin.components.status-badge', ['status' => $currentStatus, 'label' => \App\Support\AdminAccountStatus::labelFor($currentStatus)])
            <input type="hidden" name="status" value="{{ $currentStatus }}">
        </div>
        <p class="mt-2 text-sm text-slate-500">Block or unblock this customer from their profile page — status cannot be changed here.</p>
    </div>
@else
    <x-admin.form-select label="Status" name="status" :required="true">
        @foreach (\App\Support\AdminAccountStatus::filterOptionsForCustomer() as $s)
            <option value="{{ $s }}" @selected($currentStatus === $s)>{{ \App\Support\AdminAccountStatus::labelFor($s) }}</option>
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
