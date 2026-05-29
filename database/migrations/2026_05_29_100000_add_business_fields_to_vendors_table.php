<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('shop_name')->nullable()->after('brand_name');
            $table->json('service_types')->nullable()->after('categories');
            $table->string('business_mobile', 20)->nullable()->after('mobile');
            $table->string('business_email')->nullable()->after('email');
            $table->string('gst_number')->nullable()->after('city');
            $table->text('address')->nullable()->after('gst_number');
            $table->string('country')->nullable()->after('address');
            $table->string('state')->nullable()->after('country');
            $table->string('pincode', 10)->nullable()->after('state');
            $table->string('shop_logo_path')->nullable()->after('aadhar_back_path');
            $table->string('pan_card_path')->nullable()->after('shop_logo_path');
            $table->string('account_name')->nullable()->after('pan_card_path');
            $table->string('account_number')->nullable()->after('account_name');
            $table->string('ifsc_code', 11)->nullable()->after('account_number');
            $table->string('bank_name')->nullable()->after('ifsc_code');
            $table->enum('account_type', ['savings', 'current'])->nullable()->after('bank_name');
            $table->string('profile_image_path')->nullable()->after('account_type');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'shop_name',
                'service_types',
                'business_mobile',
                'business_email',
                'gst_number',
                'address',
                'country',
                'state',
                'pincode',
                'shop_logo_path',
                'pan_card_path',
                'account_name',
                'account_number',
                'ifsc_code',
                'bank_name',
                'account_type',
                'profile_image_path',
            ]);
        });
    }
};
