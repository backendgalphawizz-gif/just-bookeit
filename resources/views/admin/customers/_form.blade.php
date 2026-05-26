@php $customer = $customer ?? null; @endphp
@include('admin.partials.form-input', ['label' => 'Full Name', 'name' => 'name', 'value' => old('name', $customer?->name), 'required' => true])
@include('admin.partials.form-input', ['label' => 'Mobile', 'name' => 'mobile', 'value' => old('mobile', $customer?->mobile), 'required' => true])
@include('admin.partials.form-input', ['label' => 'Email', 'name' => 'email', 'type' => 'email', 'value' => old('email', $customer?->email)])
@include('admin.partials.form-input', ['label' => 'City', 'name' => 'city', 'value' => old('city', $customer?->city)])
<x-admin.form-select label="Status" name="status" :required="true">
    @foreach (['active', 'suspended', 'blocked'] as $s)
        <option value="{{ $s }}" @selected(old('status', $customer?->status ?? 'active') === $s)>{{ ucfirst($s) }}</option>
    @endforeach
</x-admin.form-select>
@include('admin.partials.form-input', ['label' => 'Registered At', 'name' => 'registered_at', 'type' => 'date', 'value' => old('registered_at', $customer?->registered_at?->format('Y-m-d') ?? now()->format('Y-m-d'))])
<div class="jb-checkbox-row sm:col-span-2">
    <input type="checkbox" name="is_verified" value="1" id="is_verified" @checked(old('is_verified', $customer?->is_verified ?? false))>
    <label for="is_verified" class="text-sm font-medium text-slate-700">Verified customer</label>
</div>
