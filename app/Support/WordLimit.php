<?php

namespace App\Support;

use Closure;

class WordLimit
{
    public static function count(?string $value): int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 0;
        }

        return count(preg_split('/\s+/u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    public static function clamp(?string $value, int $maxWords): string
    {
        $value = (string) $value;
        if ($maxWords < 1 || trim($value) === '') {
            return $value;
        }

        $words = preg_split('/\s+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($words) <= $maxWords) {
            return trim($value);
        }

        return implode(' ', array_slice($words, 0, $maxWords));
    }

    /** @return Closure(string, mixed, Closure): void */
    public static function rule(int $maxWords): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($maxWords): void {
            if (! is_string($value) || trim($value) === '') {
                return;
            }

            if (self::count($value) > $maxWords) {
                $fail('The :attribute may not be greater than '.$maxWords.' words.');
            }
        };
    }
}
