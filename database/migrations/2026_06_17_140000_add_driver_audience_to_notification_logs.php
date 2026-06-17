<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE notification_logs MODIFY COLUMN audience ENUM(
            'all_customers',
            'all_vendors',
            'customers',
            'vendors',
            'all_drivers',
            'drivers'
        ) NOT NULL DEFAULT 'all_customers'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE notification_logs MODIFY COLUMN audience ENUM(
            'all_customers',
            'all_vendors',
            'customers',
            'vendors'
        ) NOT NULL DEFAULT 'all_customers'");
    }
};
