<?php

use App\Models\PlatformSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            ['group' => 'legal', 'key' => 'terms_conditions_driver', 'value' => 'Terms and Conditions for delivery drivers on Just Book IT.', 'type' => 'textarea'],
            ['group' => 'legal', 'key' => 'privacy_policy_driver', 'value' => 'Privacy Policy for drivers.', 'type' => 'textarea'],
        ];

        foreach ($rows as $row) {
            PlatformSetting::query()->firstOrCreate(
                ['key' => $row['key']],
                $row
            );
        }
    }

    public function down(): void
    {
        PlatformSetting::query()->whereIn('key', [
            'terms_conditions_driver',
            'privacy_policy_driver',
        ])->delete();
    }
};
