<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->enum('status', [
                'new',
                'pending_acceptance',
                'processing',
                'partially_delivered',
                'completed',
                'partially_cancelled',
                'cancelled',
                'refunded',
            ])->default('new');
            $table->enum('payment_status', ['pending', 'success', 'failed', 'refunded', 'partially_refunded'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('amount_refunded', 12, 2)->default(0);
            $table->string('delivery_address', 500);
            $table->string('billing_address', 500)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->date('rental_start_date')->nullable();
            $table->date('rental_end_date')->nullable();
            $table->text('customer_notes')->nullable();
            $table->decimal('measure_height_cm', 8, 2)->nullable();
            $table->decimal('measure_chest_cm', 8, 2)->nullable();
            $table->decimal('measure_waist_cm', 8, 2)->nullable();
            $table->string('measurement_type', 50)->nullable();
            $table->json('measure_extra')->nullable();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('checkout_order_id')->nullable()->after('id')->constrained('checkout_orders')->nullOnDelete();
            $table->string('sub_order_number')->nullable()->unique()->after('order_number');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('portfolio_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('line_amount', 12, 2)->default(0);
            $table->json('item_snapshot')->nullable();
            $table->timestamps();
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->foreignId('checkout_order_id')->nullable()->after('order_id')->constrained('checkout_orders')->nullOnDelete();
            $table->string('source', 40)->default('manual')->after('reason');
            $table->boolean('auto_processed')->default(false)->after('source');
            $table->timestamp('processed_at')->nullable()->after('auto_processed');
        });

        Schema::create('refund_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refund_id')->constrained()->cascadeOnDelete();
            $table->string('status', 40);
            $table->text('note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_histories');

        Schema::table('refunds', function (Blueprint $table) {
            $table->dropConstrainedForeignId('checkout_order_id');
            $table->dropColumn(['source', 'auto_processed', 'processed_at']);
        });

        Schema::dropIfExists('order_items');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('checkout_order_id');
            $table->dropColumn('sub_order_number');
        });

        Schema::dropIfExists('checkout_orders');
    }
};
