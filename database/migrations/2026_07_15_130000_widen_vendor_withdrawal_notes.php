<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vendor_withdrawal_requests')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE vendor_withdrawal_requests MODIFY vendor_note TEXT NULL');
            DB::statement('ALTER TABLE vendor_withdrawal_requests MODIFY admin_note TEXT NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('vendor_withdrawal_requests')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE vendor_withdrawal_requests MODIFY vendor_note VARCHAR(255) NULL');
            DB::statement('ALTER TABLE vendor_withdrawal_requests MODIFY admin_note VARCHAR(255) NULL');
        }
    }
};
