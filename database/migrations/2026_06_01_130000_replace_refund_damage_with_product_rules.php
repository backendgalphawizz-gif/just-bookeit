<?php

use App\Models\PlatformSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $legacyPercent = PlatformSetting::get('refund_rental_max_damage_percent', '100');

        $defaults = [
            ['product_type' => 'Jewellery', 'max_percent' => 100],
            ['product_type' => 'Cloth', 'max_percent' => 50],
            ['product_type' => 'Other', 'max_percent' => (float) $legacyPercent],
        ];

        PlatformSetting::set(
            'refund_damage_deduction_rules',
            json_encode($defaults),
            'refund_rules',
            'json'
        );

        PlatformSetting::query()->where('key', 'refund_rental_max_damage_percent')->delete();
    }

    public function down(): void
    {
        $rules = PlatformSetting::get('refund_damage_deduction_rules', '[]');
        $decoded = is_string($rules) ? json_decode($rules, true) : $rules;
        $fallback = '100';

        if (is_array($decoded)) {
            foreach ($decoded as $rule) {
                if (strtolower($rule['product_type'] ?? '') === 'other') {
                    $fallback = (string) ($rule['max_percent'] ?? '100');
                    break;
                }
            }
        }

        PlatformSetting::set('refund_rental_max_damage_percent', $fallback, 'refund_rules');
        PlatformSetting::query()->where('key', 'refund_damage_deduction_rules')->delete();
    }
};
