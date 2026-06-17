<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'address')) {
                $table->string('address')->nullable()->after('city');
            }
            if (! Schema::hasColumn('customers', 'state')) {
                $table->string('state')->nullable()->after('address');
            }
            if (! Schema::hasColumn('customers', 'country')) {
                $table->string('country')->nullable()->after('state');
            }
            if (! Schema::hasColumn('customers', 'pincode')) {
                $table->string('pincode', 10)->nullable()->after('country');
            }
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            // Expand ENUM first to allow 'inactive', then convert old values, then shrink
            DB::statement("ALTER TABLE customers MODIFY status ENUM('active','suspended','blocked','inactive') NOT NULL DEFAULT 'active'");
            DB::table('customers')->whereIn('status', ['blocked', 'suspended'])->update(['status' => 'inactive']);
            DB::statement("ALTER TABLE customers MODIFY status ENUM('active','inactive') NOT NULL DEFAULT 'active'");
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['address', 'state', 'country', 'pincode']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE customers MODIFY status ENUM('active','suspended','blocked') NOT NULL DEFAULT 'active'");
        }
    }
};
