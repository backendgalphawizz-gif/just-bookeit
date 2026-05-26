<?php

namespace App\Services\Admin;

use App\Models\Admin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class AdminMenuBuilder
{
    public function __construct(
        protected DashboardService $dashboard
    ) {}

    public function build(Admin $admin): Collection
    {
        $badges = $this->dashboard->badgeCounts();
        $groups = collect();

        foreach (config('admin_menu.groups', []) as $groupName => $items) {
            $menuItems = collect($items)
                ->filter(fn (array $item) => $admin->hasPermission($item['permission'], 'view'))
                ->map(function (array $item) use ($badges) {
                    $badgeKey = $item['badge'] ?? null;
                    $count = $badgeKey ? ($badges[$badgeKey] ?? 0) : 0;
                    $routeName = $item['route'] ?? null;

                    return [
                        'label' => $item['label'],
                        'route' => $routeName,
                        'icon' => $item['icon'] ?? 'dot',
                        'active' => $routeName && request()->routeIs($item['route_is'] ?? $routeName),
                        'badge' => $count > 0 ? $count : null,
                        'enabled' => $routeName && Route::has($routeName),
                    ];
                })
                ->values();

            if ($menuItems->isNotEmpty()) {
                $groups->push([
                    'name' => $groupName,
                    'items' => $menuItems,
                ]);
            }
        }

        return $groups;
    }
}
