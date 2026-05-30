<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('order_type', ['sale', 'rental'])->default('rental')->after('category_id');
            $table->string('item_title')->nullable()->after('order_type');
            $table->text('item_description')->nullable()->after('item_title');
            $table->string('size', 50)->nullable()->after('item_description');
            $table->string('color', 100)->nullable()->after('size');
            $table->unsignedSmallInteger('quantity')->default(1)->after('color');
            $table->date('event_date')->nullable()->after('quantity');
            $table->date('rental_start_date')->nullable()->after('event_date');
            $table->date('rental_end_date')->nullable()->after('rental_start_date');
            $table->date('return_due_date')->nullable()->after('rental_end_date');
            $table->text('delivery_address')->nullable()->after('return_due_date');
            $table->text('pickup_address')->nullable()->after('delivery_address');
            $table->string('city', 100)->nullable()->after('pickup_address');
            $table->string('pincode', 10)->nullable()->after('city');
            $table->decimal('security_deposit', 12, 2)->nullable()->after('amount');
            $table->decimal('delivery_fee', 12, 2)->nullable()->after('security_deposit');
            $table->text('customer_notes')->nullable()->after('delivery_fee');
            $table->text('admin_notes')->nullable()->after('customer_notes');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'order_type',
                'item_title',
                'item_description',
                'size',
                'color',
                'quantity',
                'event_date',
                'rental_start_date',
                'rental_end_date',
                'return_due_date',
                'delivery_address',
                'pickup_address',
                'city',
                'pincode',
                'security_deposit',
                'delivery_fee',
                'customer_notes',
                'admin_notes',
            ]);
        });
    }
};
