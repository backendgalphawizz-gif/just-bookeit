<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_log_id')->constrained()->cascadeOnDelete();
            $table->string('recipient_type', 20);
            $table->unsignedBigInteger('recipient_id');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['notification_log_id', 'recipient_type', 'recipient_id'], 'notification_reads_recipient_unique');
            $table->index(['recipient_type', 'recipient_id', 'read_at'], 'notification_reads_recipient_read_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_reads');
    }
};
