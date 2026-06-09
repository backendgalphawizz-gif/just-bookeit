<?php

use App\Models\Vendor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendor_shop_logos') && ! Schema::hasTable('vendor_shop_images')) {
            Schema::rename('vendor_shop_logos', 'vendor_shop_images');
        }

        if (! Schema::hasTable('vendor_shop_images')) {
            Schema::create('vendor_shop_images', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
                $table->string('image_path');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        Vendor::query()
            ->whereNotNull('shop_logo_path')
            ->where('shop_logo_path', '!=', '')
            ->each(function (Vendor $vendor) {
                DB::table('vendor_shop_images')
                    ->where('vendor_id', $vendor->id)
                    ->where('image_path', $vendor->shop_logo_path)
                    ->delete();
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('vendor_shop_images') && ! Schema::hasTable('vendor_shop_logos')) {
            Schema::rename('vendor_shop_images', 'vendor_shop_logos');
        }
    }
};
