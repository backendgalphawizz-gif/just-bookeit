<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminAccessSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $accounts = [
            [
                'username' => 'superadmin',
                'email' => 'admin@justbookit.com',
                'name' => 'Super Admin',
                'role_slug' => 'super_admin',
            ],
            [
                'username' => 'finance',
                'email' => 'finance@justbookit.com',
                'name' => 'Finance Admin',
                'role_slug' => 'finance_admin',
            ],
            [
                'username' => 'support',
                'email' => 'support@justbookit.com',
                'name' => 'Support Admin',
                'role_slug' => 'support_admin',
            ],
            [
                'username' => 'vendoradmin',
                'email' => 'vendor@justbookit.com',
                'name' => 'Vendor Admin',
                'role_slug' => 'vendor_management_admin',
            ],
            [
                'username' => 'moderator',
                'email' => 'content@justbookit.com',
                'name' => 'Content Moderator',
                'role_slug' => 'content_moderator',
            ],
        ];

        foreach ($accounts as $account) {
            $role = Role::query()->where('slug', $account['role_slug'])->first();
            if (! $role) {
                continue;
            }

            Admin::query()->updateOrCreate(
                ['username' => $account['username']],
                [
                    'role_id' => $role->id,
                    'email' => $account['email'],
                    'name' => $account['name'],
                    'password' => Hash::make('password'),
                    'status' => 'active',
                ]
            );
        }
    }
}
