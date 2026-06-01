@php
    $admin = $admin ?? null;
    $cities = $cities ?? collect();
    $selectedCity = old('city', $admin?->assignedCity() ?? '');
    $superAdminRoleId = ($roles ?? collect())->firstWhere('slug', 'super_admin')?->id;
    $hideCityField = (string) old('role_id', $admin?->role_id) === (string) $superAdminRoleId;
@endphp

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

<div id="admin-city-field" @class(['hidden' => $hideCityField])>
    <label for="city" class="jb-label">Assigned city</label>
    <select id="city" name="city" class="jb-select">
        <option value="">Select city</option>
        @foreach ($cities as $city)
            <option value="{{ $city }}" @selected($selectedCity === $city)>{{ $city }}</option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-slate-500">Sub-admins only see vendors, drivers, customers, and orders for their assigned city. Super Admin has access to all cities.</p>
    @error('city')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
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

<script>
    (function () {
        const roleSelect = document.getElementById('role_id');
        const cityField = document.getElementById('admin-city-field');
        const citySelect = document.getElementById('city');
        if (!roleSelect || !cityField) return;

        const superAdminRoleId = @json(
            ($roles ?? collect())->firstWhere('slug', 'super_admin')?->id
        );

        function toggleCity() {
            const isSuperAdmin = superAdminRoleId && String(roleSelect.value) === String(superAdminRoleId);
            cityField.classList.toggle('hidden', isSuperAdmin);
            if (citySelect) {
                citySelect.required = !isSuperAdmin;
            }
        }

        roleSelect.addEventListener('change', toggleCity);
        toggleCity();
    })();
</script>
