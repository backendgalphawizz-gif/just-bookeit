<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            // Expand ENUM first to allow 'inactive', then convert old values, then shrink
            DB::statement("ALTER TABLE vendors MODIFY status ENUM('pending','active','suspended','blocked','inactive','rejected') NOT NULL DEFAULT 'pending'");
            DB::table('vendors')->whereIn('status', ['suspended', 'blocked'])->update(['status' => 'inactive']);
            DB::statement("ALTER TABLE vendors MODIFY status ENUM('pending','active','inactive','rejected') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE vendors MODIFY status ENUM('pending','active','suspended','blocked','rejected') NOT NULL DEFAULT 'pending'");
        }
    }
};
