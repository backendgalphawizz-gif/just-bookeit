<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $modules = [
            'sizes' => 'Product Sizes',
            'colors' => 'Product Colors',
        ];

        foreach ($modules as $slug => $name) {
            $permission = Permission::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'module' => $slug,
                    'name' => $name,
                ]
            );

            $super = Role::query()->where('slug', 'super_admin')->first();
            if ($super) {
                $super->permissions()->syncWithoutDetaching([
                    $permission->id => [
                        'can_view' => true,
                        'can_create' => true,
                        'can_edit' => true,
                        'can_delete' => true,
                        'can_export' => true,
                    ],
                ]);
            }

            $moderator = Role::query()->where('slug', 'content_moderator')->first();
            if ($moderator) {
                $moderator->permissions()->syncWithoutDetaching([
                    $permission->id => [
                        'can_view' => true,
                        'can_create' => true,
                        'can_edit' => true,
                        'can_delete' => true,
                        'can_export' => false,
                    ],
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach (['sizes', 'colors'] as $slug) {
            $permission = Permission::query()->where('slug', $slug)->first();
            if (! $permission) {
                continue;
            }

            $permission->roles()->detach();
            $permission->delete();
        }
    }
};
