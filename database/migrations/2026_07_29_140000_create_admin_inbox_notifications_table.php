<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_inbox_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->foreignId('portfolio_item_id')->nullable()->constrained('portfolio_items')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('action_url');
            $table->timestamp('read_at')->nullable();
            $table->foreignId('read_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['read_at', 'created_at']);
            $table->index(['portfolio_item_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_inbox_notifications');
    }
};
