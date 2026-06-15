<?php

namespace App\Support;

use App\Models\PlatformSetting;
use Illuminate\Validation\ValidationException;

class ProductDamageDeductionRules
{
    /** @param list<array<string, mixed>> $rules */
    public static function assertWithinCategoryLimit(?int $subcategoryId, ?int $serviceCategoryId, array $rules): void
    {
        $maxPercent = PlatformSetting::maxDamagePercentForPortfolioItem($subcategoryId, $serviceCategoryId);

        if ($maxPercent === null || $rules === []) {
            return;
        }

        $total = collect($rules)->sum(fn (array $rule) => (float) ($rule['percent'] ?? $rule['amount_percent'] ?? 0));

        if ($total > $maxPercent) {
            throw ValidationException::withMessages([
                'damage_deductions' => [
                    'Total damage deduction cannot exceed '.$maxPercent.'% for this category.',
                ],
            ]);
        }

        foreach ($rules as $index => $rule) {
            $percent = (float) ($rule['percent'] ?? $rule['amount_percent'] ?? 0);

            if ($percent > $maxPercent) {
                throw ValidationException::withMessages([
                    "damage_deductions.{$index}.percent" => [
                        'Each damage deduction cannot exceed '.$maxPercent.'% for this category.',
                    ],
                ]);
            }
        }
    }

    /** @param list<array<string, mixed>> $rules */
    /** @deprecated Use assertWithinCategoryLimit() */
    public static function assertWithinServiceCategoryLimit(int $serviceCategoryId, array $rules): void
    {
        static::assertWithinCategoryLimit(null, $serviceCategoryId, $rules);
    }
}
