<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['main', 'service']);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code')->unique();
            $table->string('name');
            $table->string('mobile', 20);
            $table->string('email')->nullable();
            $table->string('city')->nullable();
            $table->enum('status', ['active', 'suspended', 'blocked'])->default('active');
            $table->boolean('is_verified')->default(false);
            $table->unsignedInteger('total_orders')->default(0);
            $table->timestamp('registered_at');
            $table->timestamps();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_code')->unique();
            $table->string('brand_name');
            $table->string('owner_name');
            $table->string('mobile', 20);
            $table->string('email');
            $table->string('city')->nullable();
            $table->json('categories')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->unsignedInteger('orders_completed')->default(0);
            $table->decimal('earnings', 12, 2)->default(0);
            $table->enum('status', ['pending', 'active', 'suspended', 'rejected'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->constrained();
            $table->decimal('amount', 12, 2);
            $table->enum('payment_status', ['pending', 'success', 'failed', 'refunded'])->default('pending');
            $table->enum('status', [
                'new',
                'pending_acceptance',
                'accepted',
                'in_progress',
                'in_transit',
                'delivered',
                'cancelled',
                'refunded',
            ])->default('new');
            $table->timestamps();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained();
            $table->foreignId('customer_id')->constrained();
            $table->decimal('amount', 12, 2);
            $table->string('reason')->nullable();
            $table->enum('status', ['requested', 'under_review', 'approved', 'rejected', 'processed'])->default('requested');
            $table->timestamps();
        });

        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained();
            $table->enum('raised_by', ['customer', 'vendor']);
            $table->string('subject');
            $table->enum('status', ['raised', 'under_review', 'resolved', 'closed'])->default('raised');
            $table->timestamps();
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('redirect_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
        Schema::dropIfExists('disputes');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('categories');
    }
};
