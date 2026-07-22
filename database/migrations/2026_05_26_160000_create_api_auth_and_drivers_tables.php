<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('actor_type', 20);
            $table->string('mobile', 20);
            $table->string('otp');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['actor_type', 'mobile']);
        });

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('driver_code')->unique();
            $table->string('name');
            $table->string('mobile', 20)->unique();
            $table->string('email')->nullable();
            $table->string('city')->nullable();
            $table->string('aadhar_path')->nullable();
            $table->enum('status', ['pending', 'active', 'suspended', 'rejected'])->default('pending');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamps();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('is_guest')->default(false)->after('is_verified');
            $table->unique('mobile');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->unique('mobile');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('driver_id')->nullable()->after('vendor_id')->constrained('drivers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('driver_id');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropUnique(['mobile']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['mobile']);
            $table->dropColumn('is_guest');
        });

        Schema::dropIfExists('drivers');
        Schema::dropIfExists('otp_verifications');
    }
};
