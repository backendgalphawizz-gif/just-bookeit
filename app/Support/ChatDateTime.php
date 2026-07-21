<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Display helpers for chat timestamps.
 * DB stays UTC; labels use the app display timezone (Asia/Kolkata by default).
 */
class ChatDateTime
{
    public static function timezone(): string
    {
        return AdminDateTime::timezone();
    }

    public static function local(?CarbonInterface $date): ?CarbonInterface
    {
        return $date?->copy()->timezone(self::timezone());
    }

    public static function clock(?CarbonInterface $date): string
    {
        $local = self::local($date);

        return $local ? $local->format('g:i A') : '';
    }

    public static function threadTime(?CarbonInterface $at): string
    {
        $local = self::local($at);

        if (! $local) {
            return '';
        }

        if ($local->isToday()) {
            return $local->format('g:i A');
        }

        if ($local->isYesterday()) {
            return 'Yesterday';
        }

        if ($local->isCurrentYear()) {
            return $local->format('M j');
        }

        return $local->format('M j, Y');
    }

    public static function dateLabel(?CarbonInterface $date): ?string
    {
        $local = self::local($date);

        if (! $local) {
            return null;
        }

        return $local->isToday() ? 'TODAY' : $local->format('M d, Y');
    }

    public static function relative(?CarbonInterface $date): ?string
    {
        $local = self::local($date);

        return $local?->diffForHumans();
    }
}
