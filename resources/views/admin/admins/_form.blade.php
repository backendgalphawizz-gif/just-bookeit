@php $admin = $admin ?? null; @endphp

@include('admin.partials.form-input', ['label' => 'Full name', 'name' => 'name', 'value' => old('name', $admin?->name), 'required' => true])
@include('admin.partials.form-input', ['label' => 'Username', 'name' => 'username', 'value' => old('username', $admin?->username), 'required' => true])
@include('admin.partials.form-input', ['label' => 'Email', 'name' => 'email', 'type' => 'email', 'value' => old('email', $admin?->email), 'required' => true])

<div>
    <label for="role_id" class="jb-label">Role</label>
    <select id="role_id" name="role_id" class="jb-select" required>
        <option value="">Select role</option>
        @foreach ($roles as $role)
            <option value="{{ $role->id }}" @selected(old('role_id', $admin?->role_id) == $role->id)>{{ $role->name }}</option>
        @endforeach
    </select>
    @error('role_id')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
</div>

<div>
    <label for="status" class="jb-label">Account status</label>
    <select id="status" name="status" class="jb-select" required>
        @foreach (['active', 'inactive', 'suspended'] as $status)
            <option value="{{ $status }}" @selected(old('status', $admin?->status ?? 'active') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
    @error('status')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
</div>

@include('admin.partials.form-input', [
    'label' => $admin ? 'New password (optional)' : 'Password',
    'name' => 'password',
    'type' => 'password',
    'required' => ! $admin,
    'full' => true,
    'hint' => 'Minimum 8 characters',
])
