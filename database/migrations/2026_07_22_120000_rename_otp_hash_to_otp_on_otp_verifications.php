<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('otp_verifications', 'otp_hash') && ! Schema::hasColumn('otp_verifications', 'otp')) {
            Schema::table('otp_verifications', function (Blueprint $table) {
                $table->renameColumn('otp_hash', 'otp');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('otp_verifications', 'otp') && ! Schema::hasColumn('otp_verifications', 'otp_hash')) {
            Schema::table('otp_verifications', function (Blueprint $table) {
                $table->renameColumn('otp', 'otp_hash');
            });
        }
    }
};
