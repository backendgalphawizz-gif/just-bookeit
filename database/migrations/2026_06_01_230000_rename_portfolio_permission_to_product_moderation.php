<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')
            ->where('slug', 'portfolio')
            ->update(['name' => 'Product Moderation']);

        DB::table('roles')
            ->where('slug', 'content_moderator')
            ->update(['description' => 'Product and CMS moderation']);
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('slug', 'portfolio')
            ->update(['name' => 'Portfolio Moderation']);

        DB::table('roles')
            ->where('slug', 'content_moderator')
            ->update(['description' => 'Portfolio and CMS moderation']);
    }
};
