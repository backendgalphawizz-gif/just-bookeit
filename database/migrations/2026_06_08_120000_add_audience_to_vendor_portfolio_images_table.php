<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('vendor_portfolio_images', 'audience')) {
            return;
        }

        Schema::table('vendor_portfolio_images', function (Blueprint $table) {
            $table->enum('audience', ['women', 'men', 'kids'])->default('women')->after('vendor_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('vendor_portfolio_images', 'audience')) {
            return;
        }

        Schema::table('vendor_portfolio_images', function (Blueprint $table) {
            $table->dropColumn('audience');
        });
    }
};
