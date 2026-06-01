<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$role = App\Models\Role::query()->where('slug', 'vendor_management_admin')->with('permissions')->first();
echo "Role: {$role->name}\n";
foreach ($role->permissions as $p) {
    echo "  {$p->slug}: view=".($p->pivot->can_view?'1':'0')." edit=".($p->pivot->can_edit?'1':'0')." create=".($p->pivot->can_create?'1':'0')."\n";
}
echo "hasPermission vendors view: ".($role->hasPermission('vendors','view')?'yes':'no')."\n\n";

foreach (App\Models\Admin::query()->with(['role.permissions', 'assignedCities'])->get() as $admin) {
    echo "Admin: {$admin->username} ({$admin->name}) role={$admin->role?->slug}\n";
    echo "  vendors menu: ".($admin->hasPermission('vendors','view')?'yes':'no')."\n";
    echo "  city: ".($admin->assignedCity() ?? 'none')."\n";
}
