<?php

namespace App\Support;

use App\Models\Admin;
use App\Models\AdminCity;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AdminCityScope
{
    /** @var list<string> */
    private const DEFAULT_CITIES = [
        'Bengaluru',
        'Chennai',
        'Delhi',
        'Hyderabad',
        'Indore',
        'Mumbai',
        'Pune',
    ];

    public static function admin(): ?Admin
    {
        return auth('admin')->user();
    }

    public static function isUnrestricted(?Admin $admin = null): bool
    {
        $admin ??= self::admin();

        return ! $admin || $admin->isSuperAdmin();
    }

    /** @return list<string> */
    public static function cities(?Admin $admin = null): array
    {
        $admin ??= self::admin();

        if (! $admin || $admin->isSuperAdmin()) {
            return [];
        }

        return $admin->assignedCities()
            ->orderBy('city')
            ->pluck('city')
            ->all();
    }

    public static function adminCanAccessCity(?string $city, ?Admin $admin = null): bool
    {
        if (self::isUnrestricted($admin)) {
            return true;
        }

        if (! filled($city)) {
            return false;
        }

        return in_array($city, self::cities($admin), true);
    }

    /** @param Builder<\Illuminate\Database\Eloquent\Model> $query */
    public static function scopeByCity(Builder $query, ?Admin $admin = null): Builder
    {
        if (self::isUnrestricted($admin)) {
            return $query;
        }

        $cities = self::cities($admin);

        if ($cities === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn('city', $cities);
    }

    /** @param Builder<Order> $query */
    public static function scopeOrders(Builder $query, ?Admin $admin = null): Builder
    {
        if (self::isUnrestricted($admin)) {
            return $query;
        }

        $cities = self::cities($admin);

        if ($cities === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where(function (Builder $q) use ($cities) {
            $q->whereIn('city', $cities)
                ->orWhereHas('vendor', fn (Builder $vendorQuery) => $vendorQuery->whereIn('city', $cities));
        });
    }

    /** @param Builder<Vendor> $query */
    public static function scopeVendors(Builder $query, ?Admin $admin = null): Builder
    {
        return self::scopeByCity($query, $admin);
    }

    /** @param Builder<Driver> $query */
    public static function scopeDrivers(Builder $query, ?Admin $admin = null): Builder
    {
        return self::scopeByCity($query, $admin);
    }

    /** @param Builder<Customer> $query */
    public static function scopeCustomers(Builder $query, ?Admin $admin = null): Builder
    {
        return self::scopeByCity($query, $admin);
    }

    /** @return Collection<int, string> */
    public static function availableCities(): Collection
    {
        $platformCities = collect([
            Vendor::query()->whereNotNull('city')->where('city', '!=', '')->distinct()->orderBy('city')->pluck('city'),
            Driver::query()->whereNotNull('city')->where('city', '!=', '')->distinct()->orderBy('city')->pluck('city'),
            Customer::query()->whereNotNull('city')->where('city', '!=', '')->distinct()->orderBy('city')->pluck('city'),
            Order::query()->whereNotNull('city')->where('city', '!=', '')->distinct()->orderBy('city')->pluck('city'),
            AdminCity::query()->distinct()->orderBy('city')->pluck('city'),
        ])->flatten();

        return collect(self::DEFAULT_CITIES)
            ->merge($platformCities)
            ->map(fn (mixed $city) => trim((string) $city))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }
}
