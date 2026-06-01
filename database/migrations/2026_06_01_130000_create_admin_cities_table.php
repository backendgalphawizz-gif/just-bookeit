<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->string('city', 100);
            $table->timestamps();

            $table->unique(['admin_id', 'city']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_cities');
    }
};
