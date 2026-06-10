<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_status_histories', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('action', 32);
            $table->string('previous_status', 32)->nullable();
            $table->string('new_status', 32)->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_status_histories');
    }
};
