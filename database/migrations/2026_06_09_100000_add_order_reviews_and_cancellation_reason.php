<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('cancellation_reason')->nullable()->after('customer_notes');
        });

        Schema::create('order_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->decimal('rating', 3, 1);
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'created_at']);
            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_reviews');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('cancellation_reason');
        });
    }
};
