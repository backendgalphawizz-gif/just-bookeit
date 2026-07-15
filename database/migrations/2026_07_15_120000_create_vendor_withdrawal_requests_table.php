<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_code')->unique();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status', 30)->default('pending');
            $table->text('vendor_note')->nullable();
            $table->text('admin_note')->nullable();
            $table->string('payment_reference')->nullable();
            $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        Schema::table('vendor_wallet_transactions', function (Blueprint $table) {
            $table->foreignId('withdrawal_request_id')->nullable()->after('refund_id')->constrained('vendor_withdrawal_requests')->nullOnDelete();
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE vendor_wallet_transactions MODIFY COLUMN type ENUM('payment_credit','hold_release','refund_debit','refund_reversal','withdrawal_debit') NOT NULL");
        }
    }

    public function down(): void
    {
        Schema::table('vendor_wallet_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('withdrawal_request_id');
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE vendor_wallet_transactions MODIFY COLUMN type ENUM('payment_credit','hold_release','refund_debit','refund_reversal') NOT NULL");
        }

        Schema::dropIfExists('vendor_withdrawal_requests');
    }
};
