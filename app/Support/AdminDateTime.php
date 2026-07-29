<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;

class AdminDateTime
{
    public static function timezone(): string
    {
        return (string) config('app.admin_timezone', config('app.timezone', 'Asia/Kolkata'));
    }

    /**
     * Format a stored timestamp for the admin UI in India local time (or APP_ADMIN_TIMEZONE).
     */
    public static function format(DateTimeInterface|string|null $date, string $format = 'M d, Y · h:i A'): string
    {
        $carbon = self::parse($date);

        if (! $carbon) {
            return '—';
        }

        return $carbon->timezone(self::timezone())->format($format);
    }

    /**
     * Date-only display (still shifted to admin timezone so the calendar day matches local time).
     */
    public static function formatDate(DateTimeInterface|string|null $date, string $format = 'M d, Y'): string
    {
        return self::format($date, $format);
    }

    public static function parse(DateTimeInterface|string|null $date): ?CarbonInterface
    {
        if ($date === null || $date === '') {
            return null;
        }

        if ($date instanceof CarbonInterface) {
            return $date->copy();
        }

        if ($date instanceof DateTimeInterface) {
            return Carbon::instance($date);
        }

        try {
            return Carbon::parse($date, config('app.timezone', 'UTC'));
        } catch (\Throwable) {
            return null;
        }
    }
}
