<?php

namespace App\Support;

use Carbon\CarbonInterface;

class AdminDateTime
{
    public static function timezone(): string
    {
        return (string) config('app.admin_timezone', 'Asia/Kolkata');
    }

    public static function format(?CarbonInterface $date, string $format = 'M d, Y · h:i A'): string
    {
        if (! $date) {
            return '—';
        }

        return $date->copy()->timezone(self::timezone())->format($format);
    }
}
