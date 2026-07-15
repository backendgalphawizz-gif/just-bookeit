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

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        // Safe to re-run: TEXT can hold approve/reject notes without SQLSTATE 22001 crashes.
        DB::statement('ALTER TABLE vendor_withdrawal_requests MODIFY vendor_note TEXT NULL');
        DB::statement('ALTER TABLE vendor_withdrawal_requests MODIFY admin_note TEXT NULL');
    }

    public function down(): void
    {
        // Keep TEXT on rollback — shortening can truncate existing notes.
    }
};
