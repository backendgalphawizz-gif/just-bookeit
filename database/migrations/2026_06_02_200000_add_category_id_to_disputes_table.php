<?php

use App\Models\Dispute;
use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disputes', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('order_id')->constrained()->nullOnDelete();
        });

        Dispute::query()->with('order:id,category_id')->each(function (Dispute $dispute) {
            if ($dispute->order?->category_id) {
                $dispute->update(['category_id' => $dispute->order->category_id]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('disputes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
