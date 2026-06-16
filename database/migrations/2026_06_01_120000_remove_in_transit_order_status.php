<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')
            ->where('status', 'in_transit')
            ->update(['status' => 'in_progress']);

        DB::statement("ALTER TABLE orders MODIFY status ENUM(
            'new',
            'pending_acceptance',
            'accepted',
            'in_progress',
            'delivered',
            'cancelled',
            'refunded'
        ) NOT NULL DEFAULT 'new'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY status ENUM(
            'new',
            'pending_acceptance',
            'accepted',
            'in_progress',
            'in_transit',
            'delivered',
            'cancelled',
            'refunded'
        ) NOT NULL DEFAULT 'new'");
    }
};
