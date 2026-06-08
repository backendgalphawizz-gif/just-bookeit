<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->text('bio')->nullable()->after('profile_image_path');
            $table->string('cover_image_path')->nullable()->after('bio');
            $table->string('password')->nullable()->after('cover_image_path');
            $table->boolean('is_listing_active')->default(true)->after('password');
        });

        Schema::table('portfolio_items', function (Blueprint $table) {
            $table->enum('audience', ['women', 'men', 'kids'])->default('women')->after('image_url');
        });
    }

    public function down(): void
    {
        Schema::table('portfolio_items', function (Blueprint $table) {
            $table->dropColumn('audience');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['bio', 'cover_image_path', 'password', 'is_listing_active']);
        });
    }
};
