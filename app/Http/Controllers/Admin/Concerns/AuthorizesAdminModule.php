<?php

namespace App\Http\Controllers\Admin\Concerns;

trait AuthorizesAdminModule
{
    protected string $permissionModule = 'dashboard';

    protected function authorizeAdmin(?string $action = null): void
    {
        $action ??= $this->permissionActionFromRoute();

        if (! auth('admin')->user()?->hasPermission($this->permissionModule, $action)) {
            abort(403, 'You do not have permission to perform this action.');
        }
    }

    protected function permissionActionFromRoute(): string
    {
        $name = request()->route()?->getName() ?? '';

        if (str_contains($name, '.create') || str_contains($name, '.store')) {
            return 'create';
        }

        if (str_contains($name, '.edit') || str_contains($name, '.update')) {
            return 'edit';
        }

        if (str_contains($name, '.destroy')) {
            return 'delete';
        }

        if (preg_match('/\.(approve|reject|suspend|activate|resolve|close|mark-paid|mark-read|update-status|process)$/', $name)) {
            return 'edit';
        }

        return 'view';
    }

    protected function canAdmin(string $action): bool
    {
        return auth('admin')->user()?->hasPermission($this->permissionModule, $action) ?? false;
    }
}
