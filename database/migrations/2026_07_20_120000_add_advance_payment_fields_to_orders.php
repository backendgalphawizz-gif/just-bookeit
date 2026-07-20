<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'advance_amount')) {
                $table->decimal('advance_amount', 12, 2)->default(0)->after('tax_amount');
            }
            if (! Schema::hasColumn('orders', 'amount_paid')) {
                $table->decimal('amount_paid', 12, 2)->default(0)->after('advance_amount');
            }
        });

        Schema::table('checkout_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('checkout_orders', 'advance_amount')) {
                $table->decimal('advance_amount', 12, 2)->default(0)->after('tax_amount');
            }
            if (! Schema::hasColumn('checkout_orders', 'amount_paid')) {
                $table->decimal('amount_paid', 12, 2)->default(0)->after('advance_amount');
            }
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY payment_status ENUM('pending','advance_paid','success','failed','refunded') NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE checkout_orders MODIFY payment_status ENUM('pending','advance_paid','success','failed','refunded','partially_refunded') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::table('orders')->where('payment_status', 'advance_paid')->update(['payment_status' => 'pending']);
            DB::table('checkout_orders')->where('payment_status', 'advance_paid')->update(['payment_status' => 'pending']);
            DB::statement("ALTER TABLE orders MODIFY payment_status ENUM('pending','success','failed','refunded') NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE checkout_orders MODIFY payment_status ENUM('pending','success','failed','refunded','partially_refunded') NOT NULL DEFAULT 'pending'");
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'amount_paid')) {
                $table->dropColumn('amount_paid');
            }
            if (Schema::hasColumn('orders', 'advance_amount')) {
                $table->dropColumn('advance_amount');
            }
        });

        Schema::table('checkout_orders', function (Blueprint $table) {
            if (Schema::hasColumn('checkout_orders', 'amount_paid')) {
                $table->dropColumn('amount_paid');
            }
            if (Schema::hasColumn('checkout_orders', 'advance_amount')) {
                $table->dropColumn('advance_amount');
            }
        });
    }
};
