<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\PlatformSetting;
use App\Support\CodeGenerator;
use Illuminate\Database\Seeder;

class OrderFlowDemoSeeder extends Seeder
{
    public function run(): void
    {
        PlatformSetting::set('enable_cod', true, 'payment', 'boolean');

        Driver::query()->updateOrCreate(
            ['mobile' => '9898989898'],
            [
                'driver_code' => CodeGenerator::driverCode(),
                'name' => 'Demo Driver',
                'email' => 'driver-demo@justbookit.test',
                'city' => 'Mumbai',
                'vehicle_no' => 'MH01DEMO01',
                'status' => 'active',
                'is_verified' => true,
                'approved_at' => now(),
                'registered_at' => now(),
            ]
        );
    }
}
