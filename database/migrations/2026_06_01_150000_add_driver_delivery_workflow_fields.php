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
            $table->string('payment_method', 30)->nullable()->after('payment_status');
            $table->text('driver_rejection_reason')->nullable();
            $table->date('driver_scheduled_for')->nullable();
            $table->timestamp('driver_rescheduled_at')->nullable();
            $table->timestamp('cod_collected_at')->nullable();
        });

        if (Schema::getConnection()->getDriverName() === 'mysql' && Schema::hasColumn('orders', 'driver_delivery_status')) {
            DB::statement("ALTER TABLE orders MODIFY driver_delivery_status ENUM(
                'accepted',
                'picked_up',
                'out_for_delivery',
                'rescheduled'
            ) NULL");
        }

        Schema::create('driver_delivery_skips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->unique(['driver_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_delivery_skips');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'driver_rejection_reason',
                'driver_scheduled_for',
                'driver_rescheduled_at',
                'cod_collected_at',
            ]);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql' && Schema::hasColumn('orders', 'driver_delivery_status')) {
            DB::statement("ALTER TABLE orders MODIFY driver_delivery_status ENUM(
                'accepted',
                'picked_up',
                'out_for_delivery'
            ) NULL");
        }
    }
};
