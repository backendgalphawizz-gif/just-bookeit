<?php

namespace App\Support\Api;

use App\Models\Driver;
use App\Models\DriverDeliverySkip;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class DriverDeliveryTab
{
    public const TAB_NEW = 'new';

    public const TAB_ACCEPTED = 'accepted';

    public const TAB_PICKUP = 'pickup';

    public const TAB_DISPATCHED = 'dispatched';

    public const TAB_DELIVERED = 'delivered';

    public const TAB_CANCELLED = 'cancelled';

    public const TAB_RESCHEDULED = 'rescheduled';

    /** @return list<string> */
    public static function tabs(): array
    {
        return [
            self::TAB_NEW,
            self::TAB_ACCEPTED,
            self::TAB_PICKUP,
            self::TAB_DISPATCHED,
            self::TAB_DELIVERED,
            self::TAB_CANCELLED,
            self::TAB_RESCHEDULED,
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
        $normalizedTab = self::normalizeTab($tab);

        return match ($normalizedTab) {
            self::TAB_NEW => $query
                ->whereIn('status', $dispatchStatuses)
                ->whereNull('driver_id')
                ->whereNotIn('id', DriverDeliverySkip::query()
                    ->where('driver_id', $driver->id)
                    ->select('order_id')),
            self::TAB_ACCEPTED => $query
                ->where('driver_id', $driver->id)
                ->whereIn('status', $dispatchStatuses)
                ->where('driver_delivery_status', Order::DRIVER_STATUS_ACCEPTED),
            self::TAB_PICKUP => $query
                ->where('driver_id', $driver->id)
                ->whereIn('status', $dispatchStatuses)
                ->where('driver_delivery_status', Order::DRIVER_STATUS_PICKED_UP),
            self::TAB_DISPATCHED => $query
                ->where('driver_id', $driver->id)
                ->whereIn('status', $dispatchStatuses)
                ->where('driver_delivery_status', Order::DRIVER_STATUS_OUT_FOR_DELIVERY),
            self::TAB_DELIVERED => $query
                ->where('driver_id', $driver->id)
                ->whereIn('status', ['delivered', 're_delivered']),
            self::TAB_CANCELLED => $query
                ->where('driver_id', $driver->id)
                ->where('status', 'cancelled'),
            self::TAB_RESCHEDULED => $query
                ->where('driver_id', $driver->id)
                ->whereIn('status', $dispatchStatuses)
                ->where('driver_delivery_status', Order::DRIVER_STATUS_RESCHEDULED),
            default => $query->where(function (Builder $builder) use ($driver, $dispatchStatuses) {
                $builder->where(function (Builder $available) use ($driver, $dispatchStatuses) {
                    $available->whereIn('status', $dispatchStatuses)
                        ->whereNull('driver_id')
                        ->whereNotIn('id', DriverDeliverySkip::query()
                            ->where('driver_id', $driver->id)
                            ->select('order_id'));
                })->orWhere('driver_id', $driver->id);
            }),
        };
    }

    protected static function normalizeTab(?string $tab): ?string
    {
        if ($tab === null || trim($tab) === '') {
            return null;
        }

        $key = strtolower(str_replace('_', '-', trim($tab)));

        return match ($key) {
            'new' => self::TAB_NEW,
            'accepted' => self::TAB_ACCEPTED,
            'pickup' => self::TAB_PICKUP,
            'dispatched', 'out-for-delivery', 'out_for_delivery' => self::TAB_DISPATCHED,
            'delivered', 'completed' => self::TAB_DELIVERED,
            'cancelled', 'canceled' => self::TAB_CANCELLED,
            'rescheduled' => self::TAB_RESCHEDULED,
            default => $key,
        };
    }
}
