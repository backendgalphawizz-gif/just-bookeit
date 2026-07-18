<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::query()->updateOrCreate(
            ['slug' => 'contact_messages'],
            [
                'module' => 'contact_messages',
                'name' => 'Contact Messages',
            ]
        );

        $super = Role::query()->where('slug', 'super_admin')->first();
        if ($super) {
            $super->permissions()->syncWithoutDetaching([
                $permission->id => [
                    'can_view' => true,
                    'can_create' => false,
                    'can_edit' => true,
                    'can_delete' => true,
                    'can_export' => true,
                ],
            ]);
        }

        $support = Role::query()->where('slug', 'support_admin')->first();
        if ($support) {
            $support->permissions()->syncWithoutDetaching([
                $permission->id => [
                    'can_view' => true,
                    'can_create' => false,
                    'can_edit' => true,
                    'can_delete' => false,
                    'can_export' => true,
                ],
            ]);
        }
    }

    public function down(): void
    {
        $permission = Permission::query()->where('slug', 'contact_messages')->first();
        if (! $permission) {
            return;
        }

        $permission->roles()->detach();
        $permission->delete();
    }
};
