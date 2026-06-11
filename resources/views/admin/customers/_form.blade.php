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
<x-admin.form-select label="Status" name="status" :required="true">
    @foreach (['active', 'suspended', 'blocked'] as $s)
        <option value="{{ $s }}" @selected(old('status', $customer?->status ?? 'active') === $s)>{{ ucfirst($s) }}</option>
    @endforeach
</x-admin.form-select>
@include('admin.partials.form-input', [
    'label' => 'Registered At',
    'name' => 'registered_at',
    'type' => 'date',
    'value' => old('registered_at', $customer?->registeredAtForForm() ?? now()->format('Y-m-d')),
    'min' => '1970-01-01',
    'max' => now()->format('Y-m-d'),
    'hint' => 'Must be between Jan 1, 1970 and today.',
])
<div class="jb-checkbox-row sm:col-span-2">
    <input type="checkbox" name="is_verified" value="1" id="is_verified" @checked(old('is_verified', $customer?->is_verified ?? false))>
    <label for="is_verified" class="text-sm font-medium text-slate-700">Verified customer</label>
</div>
