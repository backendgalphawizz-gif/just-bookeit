<?php

use App\Models\Vendor;
use App\Models\VendorShopLogo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_shop_logos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Vendor::query()
            ->whereNotNull('shop_logo_path')
            ->where('shop_logo_path', '!=', '')
            ->each(function (Vendor $vendor) {
                VendorShopLogo::query()->create([
                    'vendor_id' => $vendor->id,
                    'image_path' => $vendor->shop_logo_path,
                    'sort_order' => 1,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_shop_logos');
    }
};
