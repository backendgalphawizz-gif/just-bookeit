<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('aadhar_front_path')->nullable()->after('city');
            $table->string('aadhar_back_path')->nullable()->after('aadhar_front_path');
            $table->string('driving_licence_path')->nullable()->after('aadhar_back_path');
            $table->string('profile_image_path')->nullable()->after('driving_licence_path');
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn([
                'aadhar_front_path',
                'aadhar_back_path',
                'driving_licence_path',
                'profile_image_path',
            ]);
        });
    }
};
