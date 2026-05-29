<?php

use App\Models\PlatformSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        PlatformSetting::query()->whereIn('key', [
            'faq_user',
            'faq_vendor',
            'faq_driver',
        ])->delete();
    }

    public function down(): void
    {
        $rows = [
            ['group' => 'legal', 'key' => 'faq_user', 'value' => '', 'type' => 'textarea'],
            ['group' => 'legal', 'key' => 'faq_vendor', 'value' => '', 'type' => 'textarea'],
            ['group' => 'legal', 'key' => 'faq_driver', 'value' => '', 'type' => 'textarea'],
        ];

        foreach ($rows as $row) {
            PlatformSetting::query()->firstOrCreate(['key' => $row['key']], $row);
        }
    }
};
