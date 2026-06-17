<?php

namespace App\Support\Api;

use App\Models\Driver;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class DriverDeliveryTab
{
    public const TAB_NEW = 'new';

    public const TAB_ACCEPTED = 'accepted';

    public const TAB_OUT_FOR_DELIVERY = 'out_for_delivery';

    public const TAB_COMPLETED = 'completed';

    public const TAB_CANCELLED = 'cancelled';

    /** @return list<string> */
    public static function tabs(): array
    {
        return [
            self::TAB_NEW,
            self::TAB_ACCEPTED,
            self::TAB_OUT_FOR_DELIVERY,
            self::TAB_COMPLETED,
            self::TAB_CANCELLED,
        ];
    }

    public static function validationRule(): array
    {
        return ['nullable', 'string', Rule::in(self::tabs())];
    }

    /** @return list<string> */
    public static function activeDeliveryStatuses(): array
    {
        return ['in_progress', 're_intransit'];
    }

    public static function applyToQuery(Builder $query, Driver $driver, ?string $tab): Builder
    {
        $dispatchStatuses = self::activeDeliveryStatuses();

        return match ($tab) {
            self::TAB_NEW => $query
                ->whereIn('status', $dispatchStatuses)
                ->whereNull('driver_id'),
            self::TAB_ACCEPTED => $query
                ->where('driver_id', $driver->id)
                ->whereIn('status', $dispatchStatuses)
                ->where('driver_delivery_status', Order::DRIVER_STATUS_ACCEPTED),
            self::TAB_OUT_FOR_DELIVERY => $query
                ->where('driver_id', $driver->id)
                ->whereIn('status', $dispatchStatuses)
                ->whereIn('driver_delivery_status', [
                    Order::DRIVER_STATUS_PICKED_UP,
                    Order::DRIVER_STATUS_OUT_FOR_DELIVERY,
                ]),
            self::TAB_COMPLETED => $query
                ->where('driver_id', $driver->id)
                ->whereIn('status', ['delivered', 're_delivered']),
            self::TAB_CANCELLED => $query
                ->where('driver_id', $driver->id)
                ->where('status', 'cancelled'),
            default => $query->where(function (Builder $builder) use ($driver, $dispatchStatuses) {
                $builder->where(function (Builder $available) use ($dispatchStatuses) {
                    $available->whereIn('status', $dispatchStatuses)->whereNull('driver_id');
                })->orWhere('driver_id', $driver->id);
            }),
        };
    }
}
