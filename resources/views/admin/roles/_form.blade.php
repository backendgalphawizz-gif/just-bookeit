@php
    $role = $role ?? null;
    $rolePermissions = $rolePermissions ?? [];
    $isSuperAdmin = $isSuperAdmin ?? false;
@endphp

@include('admin.partials.form-input', ['label' => 'Role name', 'name' => 'name', 'value' => old('name', $role?->name), 'required' => true])
@if ($isSuperAdmin)
    <div>
        <label class="jb-label">Slug</label>
        <input type="text" class="jb-input bg-slate-50" value="{{ $role->slug }}" readonly disabled>
        <input type="hidden" name="slug" value="{{ $role->slug }}">
    </div>
@else
    @include('admin.partials.form-input', ['label' => 'Slug', 'name' => 'slug', 'value' => old('slug', $role?->slug), 'placeholder' => 'Auto-generated if empty'])
@endif
@include('admin.partials.form-input', ['label' => 'Description', 'name' => 'description', 'type' => 'textarea', 'value' => old('description', $role?->description), 'full' => true])

<div class="jb-checkbox-row sm:col-span-2">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $role?->is_active ?? true))>
    <label class="text-sm font-medium text-slate-700">Role is active</label>
</div>

<div class="sm:col-span-2">
    <label class="jb-label mb-3 block">Module permissions</label>
    @if ($isSuperAdmin)
        <p class="mb-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Super Admin always has full access to every module.
        </p>
    @else
        <p class="mb-3 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
            Sidebar menu items require the <strong>View</strong> permission (Create/Edit also enable View automatically when saved).
        </p>
    @endif
    <div
        class="jb-table-wrap rounded-xl border border-slate-200"
        x-data="{
            toggleColumn(key, checked) {
                document.querySelectorAll(`input[name*='[${key}]']`).forEach((el) => {
                    if (! el.disabled) el.checked = checked;
                });
            },
            toggleRow(id, checked) {
                document.querySelectorAll(`input[name^='permissions[${id}]']`).forEach((el) => {
                    if (! el.disabled) el.checked = checked;
                });
            },
            ensureViewForRow(id) {
                const view = document.querySelector(`input[name='permissions[${id}][can_view]']`);
                const others = document.querySelectorAll(`input[name^='permissions[${id}]'][name$='can_create]'], input[name^='permissions[${id}]'][name$='can_edit]'], input[name^='permissions[${id}]'][name$='can_delete]'], input[name^='permissions[${id}]'][name$='can_export]']`);
                if (view && [...others].some((el) => el.checked)) {
                    view.checked = true;
                }
            },
            selectAll(checked) {
                document.querySelectorAll('input[name^=permissions]').forEach((el) => {
                    if (! el.disabled) el.checked = checked;
                });
            },
        }"
    >
        @unless ($isSuperAdmin)
            <div class="flex flex-wrap gap-2 border-b border-slate-100 bg-slate-50/80 px-4 py-3">
                <button type="button" class="jb-btn jb-btn-sm jb-btn-secondary" @click="selectAll(true)">Select all</button>
                <button type="button" class="jb-btn jb-btn-sm jb-btn-ghost" @click="selectAll(false)">Clear all</button>
            </div>
        @endunless
        <table class="jb-table text-sm">
            <thead>
                <tr>
                    <th class="jb-col-name">Module</th>
                    <th class="text-center">
                        <span class="block">View</span>
                        @unless ($isSuperAdmin)
                            <button type="button" class="mt-1 text-[10px] font-semibold text-rose-600" @click="toggleColumn('can_view', true)">All</button>
                        @endunless
                    </th>
                    <th class="text-center">
                        <span class="block">Create</span>
                        @unless ($isSuperAdmin)
                            <button type="button" class="mt-1 text-[10px] font-semibold text-rose-600" @click="toggleColumn('can_create', true)">All</button>
                        @endunless
                    </th>
                    <th class="text-center">
                        <span class="block">Edit</span>
                        @unless ($isSuperAdmin)
                            <button type="button" class="mt-1 text-[10px] font-semibold text-rose-600" @click="toggleColumn('can_edit', true)">All</button>
                        @endunless
                    </th>
                    <th class="text-center">
                        <span class="block">Delete</span>
                        @unless ($isSuperAdmin)
                            <button type="button" class="mt-1 text-[10px] font-semibold text-rose-600" @click="toggleColumn('can_delete', true)">All</button>
                        @endunless
                    </th>
                    <th class="text-center">
                        <span class="block">Export</span>
                        @unless ($isSuperAdmin)
                            <button type="button" class="mt-1 text-[10px] font-semibold text-rose-600" @click="toggleColumn('can_export', true)">All</button>
                        @endunless
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($permissions as $permission)
                    @if ($isSuperAdmin && $permission->slug === 'categories')
                        @continue
                    @endif
                    @php
                        $flags = old("permissions.{$permission->id}", $rolePermissions[$permission->id] ?? []);
                    @endphp
                    <tr>
                        <td class="jb-col-name font-medium">
                            {{ $permission->name }}
                            @unless ($isSuperAdmin)
                                <button type="button" class="ml-2 text-[10px] font-semibold text-slate-500 hover:text-rose-600" @click="toggleRow({{ $permission->id }}, true)">All</button>
                            @endunless
                        </td>
                        @foreach (['can_view' => 'View', 'can_create' => 'Create', 'can_edit' => 'Edit', 'can_delete' => 'Delete', 'can_export' => 'Export'] as $key => $label)
                            <td class="text-center">
                                <input
                                    type="checkbox"
                                    name="permissions[{{ $permission->id }}][{{ $key }}]"
                                    value="1"
                                    class="jb-checkbox-accent"
                                    @checked(! empty($flags[$key]))
                                    @disabled($isSuperAdmin)
                                    @unless($isSuperAdmin)
                                        @if($key !== 'can_view')
                                            x-on:change="ensureViewForRow({{ $permission->id }})"
                                        @endif
                                    @endunless
                                >
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
