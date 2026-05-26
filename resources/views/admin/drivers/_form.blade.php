@php $driver = $driver ?? null; @endphp
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
        'label' => 'Aadhar card',
        'name' => 'aadhar',
        'currentUrl' => $driver?->aadharUrl(),
        'required' => false,
    ])
</div>

<div class="jb-checkbox-row sm:col-span-2">
    <input type="checkbox" name="is_verified" value="1" @checked(old('is_verified', $driver?->is_verified ?? false))>
    <label class="text-sm font-medium text-slate-700">Verified driver</label>
</div>
