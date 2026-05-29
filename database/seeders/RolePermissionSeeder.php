<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            'dashboard' => 'Dashboard',
            'customers' => 'Customer Management',
            'vendors' => 'Vendor Management',
            'drivers' => 'Driver Management',
            'portfolio' => 'Portfolio Moderation',
            'categories' => 'Category Management',
            'orders' => 'Booking & Orders',
            'chat' => 'Chat & Communication',
            'video_calls' => 'Video Call Monitoring',
            'payments' => 'Payment Management',
            'refunds' => 'Refund Management',
            'payouts' => 'Vendor Payouts',
            'commissions' => 'Commission Management',
            'banners' => 'Banner & CMS',
            'faqs' => 'FAQ Management',
            'notifications' => 'Notifications',
            'reports' => 'Reports & Analytics',
            'disputes' => 'Dispute Management',
            'settings' => 'System Settings',
            'admins' => 'Admin & RBAC',
        ];

        foreach ($modules as $slug => $name) {
            Permission::query()->updateOrCreate(
                ['slug' => $slug],
                ['module' => $slug, 'name' => $name]
            );
        }

        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Full platform access'],
            ['name' => 'Support Admin', 'slug' => 'support_admin', 'description' => 'Customer support and disputes'],
            ['name' => 'Finance Admin', 'slug' => 'finance_admin', 'description' => 'Payments, refunds, and payouts'],
            ['name' => 'Vendor Management Admin', 'slug' => 'vendor_management_admin', 'description' => 'Vendor onboarding and operations'],
            ['name' => 'Content Moderator', 'slug' => 'content_moderator', 'description' => 'Portfolio and CMS moderation'],
        ];

        foreach ($roles as $roleData) {
            Role::query()->updateOrCreate(['slug' => $roleData['slug']], $roleData);
        }

        $support = Role::query()->where('slug', 'support_admin')->first();
        $supportPermissions = ['dashboard', 'customers', 'orders', 'chat', 'disputes', 'refunds'];

        $this->attachPermissions($support, array_merge($supportPermissions, ['drivers']), edit: true, create: false);

        $finance = Role::query()->where('slug', 'finance_admin')->first();
        $this->attachPermissions($finance, ['dashboard', 'payments', 'refunds', 'payouts', 'commissions', 'orders'], edit: true, create: true);

        $vendorAdmin = Role::query()->where('slug', 'vendor_management_admin')->first();
        $this->attachPermissions($vendorAdmin, ['dashboard', 'vendors', 'drivers', 'portfolio', 'categories', 'orders'], edit: true, create: true);

        $moderator = Role::query()->where('slug', 'content_moderator')->first();
        $this->attachPermissions($moderator, ['dashboard', 'portfolio', 'banners', 'categories', 'faqs'], edit: true, create: true);

        $super = Role::query()->where('slug', 'super_admin')->first();
        if ($super) {
            $sync = [];
            foreach (Permission::query()->get() as $permission) {
                $sync[$permission->id] = [
                    'can_view' => true,
                    'can_create' => true,
                    'can_edit' => true,
                    'can_delete' => true,
                    'can_export' => true,
                ];
            }
            $super->permissions()->sync($sync);
        }
    }

    protected function attachPermissions(?Role $role, array $slugs, bool $edit = false, bool $create = false): void
    {
        if (! $role) {
            return;
        }

        foreach ($slugs as $slug) {
            $permission = Permission::query()->where('slug', $slug)->first();
            if ($permission) {
                $role->permissions()->syncWithoutDetaching([
                    $permission->id => [
                        'can_view' => true,
                        'can_create' => $create,
                        'can_edit' => $edit,
                        'can_delete' => false,
                        'can_export' => true,
                    ],
                ]);
            }
        }
    }
}
