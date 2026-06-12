<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->decimal('wallet_balance', 12, 2)->default(0)->after('registered_at');
            $table->decimal('total_earnings', 12, 2)->default(0)->after('wallet_balance');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->enum('driver_delivery_status', ['accepted', 'picked_up', 'out_for_delivery'])
                ->nullable()
                ->after('driver_id');
            $table->timestamp('driver_assigned_at')->nullable()->after('driver_delivery_status');
            $table->timestamp('driver_pickup_at')->nullable()->after('driver_assigned_at');
            $table->timestamp('driver_delivered_at')->nullable()->after('driver_pickup_at');
            $table->decimal('driver_earning', 12, 2)->nullable()->after('driver_delivered_at');
        });

        Schema::create('driver_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code')->unique();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['delivery_credit', 'withdrawal_debit']);
            $table->enum('direction', ['credit', 'debit']);
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('description');
            $table->timestamps();

            $table->index(['driver_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_wallet_transactions');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'driver_delivery_status',
                'driver_assigned_at',
                'driver_pickup_at',
                'driver_delivered_at',
                'driver_earning',
            ]);
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['wallet_balance', 'total_earnings']);
        });
    }
};
