<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

trait SyncsRolePermissions
{
    protected function syncRolePermissions(Role $role, Request $request): void
    {
        if ($role->slug === 'super_admin') {
            return;
        }

        $input = $request->input('permissions', []);
        $sync = [];

        foreach (Permission::query()->pluck('id') as $permissionId) {
            $flags = $input[$permissionId] ?? [];
            $canView = ! empty($flags['can_view']);
            $canCreate = ! empty($flags['can_create']);
            $canEdit = ! empty($flags['can_edit']);
            $canDelete = ! empty($flags['can_delete']);
            $canExport = ! empty($flags['can_export']);

            if ($canCreate || $canEdit || $canDelete || $canExport) {
                $canView = true;
            }

            $sync[$permissionId] = [
                'can_view' => $canView,
                'can_create' => $canCreate,
                'can_edit' => $canEdit,
                'can_delete' => $canDelete,
                'can_export' => $canExport,
            ];
        }

        $role->permissions()->sync($sync);
    }

    protected function rolePermissionMap(Role $role): array
    {
        $map = [];

        foreach ($role->permissions as $permission) {
            $map[$permission->id] = [
                'can_view' => (bool) $permission->pivot->can_view,
                'can_create' => (bool) $permission->pivot->can_create,
                'can_edit' => (bool) $permission->pivot->can_edit,
                'can_delete' => (bool) $permission->pivot->can_delete,
                'can_export' => (bool) $permission->pivot->can_export,
            ];
        }

        return $map;
    }
}
