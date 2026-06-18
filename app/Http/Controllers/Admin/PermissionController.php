<?php

namespace App\Http\Controllers\Admin;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\View\View;

class PermissionController extends AdminController
{
    protected string $permissionModule = 'admins';

    public function index(): View
    {
        $permissions = Permission::query()
            ->with(['roles' => fn ($q) => $q->orderBy('name')])
            ->newestFirst()
            ->get();

        $roles = Role::query()->with('permissions')->newestFirst()->get();

        return view('admin.permissions.index', compact('permissions', 'roles'));
    }
}
