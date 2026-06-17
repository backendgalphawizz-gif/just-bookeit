<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            // Expand ENUM first, then convert, then shrink
            DB::statement("ALTER TABLE drivers MODIFY status ENUM('pending','active','suspended','inactive','rejected') NOT NULL DEFAULT 'pending'");
            DB::table('drivers')->where('status', 'suspended')->update(['status' => 'inactive']);
            DB::statement("ALTER TABLE drivers MODIFY status ENUM('pending','active','inactive','rejected') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE drivers MODIFY status ENUM('pending','active','suspended','rejected') NOT NULL DEFAULT 'pending'");
        }
    }
};
