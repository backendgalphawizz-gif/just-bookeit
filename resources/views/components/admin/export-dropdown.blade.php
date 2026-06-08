@props([
    'module',
    'params' => [],
    'permission' => null,
])

@php
    $permissionModule = $permission ?? (in_array($module, ['admins', 'roles'], true) ? 'admins' : $module);
    $canExport = auth('admin')->user()?->hasPermission($permissionModule, 'export') ?? false;
    $query = request()->only($params);

    if ($permission) {
        $query['export_permission'] = $permission;
    }
@endphp

@if ($canExport)
    <div class="jb-export-dropdown" x-data="{ open: false }" @click.outside="open = false">
        <button type="button" class="jb-btn jb-btn-secondary jb-btn-sm" @click="open = !open" aria-haspopup="true" :aria-expanded="open">
            Export
            <span aria-hidden="true">▾</span>
        </button>
        <div class="jb-export-menu" x-show="open" x-cloak x-transition>
            <a href="{{ route('admin.list-export', array_merge(['module' => $module, 'format' => 'csv'], $query)) }}" class="jb-export-menu-item">CSV</a>
            <a href="{{ route('admin.list-export', array_merge(['module' => $module, 'format' => 'pdf'], $query)) }}" class="jb-export-menu-item">PDF</a>
        </div>
    </div>
@endif
