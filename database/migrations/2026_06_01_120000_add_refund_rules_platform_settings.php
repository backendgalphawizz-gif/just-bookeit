<?php

use App\Models\PlatformSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            ['group' => 'refund_rules', 'key' => 'refund_policy_user', 'value' => 'Refunds are processed per the rental or purchase terms agreed at checkout. Security deposits are returned after the outfit is inspected on return.', 'type' => 'html'],
            ['group' => 'refund_rules', 'key' => 'return_policy_user', 'value' => 'Rented outfits must be returned on or before the due date in the same condition. Late returns may incur daily charges. Purchased items may be returned only within the allowed window if tags are intact.', 'type' => 'html'],
            ['group' => 'refund_rules', 'key' => 'refund_enable_rental', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'refund_rules', 'key' => 'refund_enable_sale', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'refund_rules', 'key' => 'refund_rental_cancel_days', 'value' => '3', 'type' => 'text'],
            ['group' => 'refund_rules', 'key' => 'refund_rental_late_fee_per_day', 'value' => '500', 'type' => 'text'],
            ['group' => 'refund_rules', 'key' => 'refund_damage_deduction_rules', 'value' => json_encode([
                ['product_type' => 'Jewellery', 'max_percent' => 100],
                ['product_type' => 'Cloth', 'max_percent' => 50],
                ['product_type' => 'Other', 'max_percent' => 100],
            ]), 'type' => 'json'],
            ['group' => 'refund_rules', 'key' => 'refund_rental_deposit_days', 'value' => '7', 'type' => 'text'],
            ['group' => 'refund_rules', 'key' => 'refund_sale_window_days', 'value' => '7', 'type' => 'text'],
            ['group' => 'refund_rules', 'key' => 'refund_sale_return_days', 'value' => '14', 'type' => 'text'],
            ['group' => 'refund_rules', 'key' => 'refund_sale_restocking_percent', 'value' => '10', 'type' => 'text'],
        ];

        foreach ($defaults as $row) {
            PlatformSetting::query()->firstOrCreate(['key' => $row['key']], $row);
        }
    }

    public function down(): void
    {
        PlatformSetting::query()->whereIn('key', [
            'refund_policy_user',
            'return_policy_user',
            'refund_enable_rental',
            'refund_enable_sale',
            'refund_rental_cancel_days',
            'refund_rental_late_fee_per_day',
            'refund_damage_deduction_rules',
            'refund_rental_deposit_days',
            'refund_sale_window_days',
            'refund_sale_return_days',
            'refund_sale_restocking_percent',
        ])->delete();
    }
};
