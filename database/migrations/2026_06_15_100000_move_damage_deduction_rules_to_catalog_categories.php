<?php

use App\Models\PlatformSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        PlatformSetting::query()
            ->where('key', 'refund_damage_deduction_rules')
            ->update(['group' => 'damage_deduction']);
    }

    public function down(): void
    {
        PlatformSetting::query()
            ->where('key', 'refund_damage_deduction_rules')
            ->update(['group' => 'refund_rules']);
    }
};