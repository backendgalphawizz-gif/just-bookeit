<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\SyncsRolePermissions;
use App\Http\Requests\Admin\RoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoleController extends AdminController
{
    use SyncsRolePermissions;

    protected string $permissionModule = 'admins';

    public function index(): View
    {
        $roles = Role::query()
            ->withCount('admins')
            ->with('permissions')
            ->orderBy('name')
            ->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        return view('admin.roles.create', [
            'permissions' => Permission::query()->orderBy('name')->get(),
            
            'rolePermissions' => [],
        ]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['permissions']);

        $role = Role::query()->create($data);
        $this->syncRolePermissions($role, $request);

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): View
    {
        $role->load('permissions');

        return view('admin.roles.edit', [
            'role' => $role,
            'permissions' => Permission::query()->orderBy('name')->get(),
            'rolePermissions' => $this->rolePermissionMap($role),
            'isSuperAdmin' => $role->slug === 'super_admin',
        ]);
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        $data = $request->validated();
        unset($data['permissions']);

        if ($role->slug === 'super_admin') {
            unset($data['slug']);
        }

        $role->update($data);
        $this->syncRolePermissions($role, $request);

        return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->slug === 'super_admin') {
            return back()->with('error', 'The Super Admin role cannot be deleted.');
        }

        if ($role->admins()->exists()) {
            return back()->with('error', 'Cannot delete a role that is assigned to admin users.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted successfully.');
    }
}
