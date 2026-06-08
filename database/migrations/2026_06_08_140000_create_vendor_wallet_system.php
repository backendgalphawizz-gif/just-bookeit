<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->decimal('digital_wallet_balance', 12, 2)->default(0)->after('earnings');
            $table->decimal('wallet_balance', 12, 2)->default(0)->after('digital_wallet_balance');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('payment_status');
            $table->timestamp('wallet_release_at')->nullable()->after('paid_at');
            $table->timestamp('wallet_settled_at')->nullable()->after('wallet_release_at');
            $table->decimal('vendor_net_amount', 12, 2)->nullable()->after('wallet_settled_at');
            $table->decimal('vendor_wallet_held_amount', 12, 2)->default(0)->after('vendor_net_amount');
            $table->enum('wallet_hold_status', ['none', 'held', 'released', 'refunded'])->default('none')->after('vendor_wallet_held_amount');
        });

        Schema::create('vendor_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('refund_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['payment_credit', 'hold_release', 'refund_debit', 'refund_reversal']);
            $table->enum('wallet', ['digital', 'actual']);
            $table->enum('direction', ['credit', 'debit']);
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('description');
            $table->timestamps();

            $table->index(['vendor_id', 'created_at']);
            $table->index(['order_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_wallet_transactions');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'paid_at',
                'wallet_release_at',
                'wallet_settled_at',
                'vendor_net_amount',
                'vendor_wallet_held_amount',
                'wallet_hold_status',
            ]);
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['digital_wallet_balance', 'wallet_balance']);
        });
    }
};
