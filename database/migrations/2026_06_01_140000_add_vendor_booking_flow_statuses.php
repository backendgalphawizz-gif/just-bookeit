<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE orders MODIFY status ENUM(
            'new',
            'pending_acceptance',
            'accepted',
            'in_progress',
            'delivered',
            'returned',
            'rework',
            're_intransit',
            're_delivered',
            'cancelled',
            'refunded'
        ) NOT NULL DEFAULT 'new'");
    }

    public function down(): void
    {
        DB::statement("UPDATE orders SET status = 'delivered' WHERE status IN ('returned', 'rework', 're_intransit', 're_delivered')");

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
};
