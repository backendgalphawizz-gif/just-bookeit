<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $country = Country::query()->firstOrCreate(
            ['name' => 'India'],
            ['is_active' => true]
        );

        $locations = [
            'Maharashtra' => ['Mumbai', 'Pune', 'Nagpur'],
            'Delhi' => ['Delhi', 'New Delhi'],
            'Karnataka' => ['Bengaluru', 'Mysuru', 'Mangaluru'],
            'Telangana' => ['Hyderabad', 'Warangal'],
            'Tamil Nadu' => ['Chennai', 'Coimbatore', 'Madurai'],
            'Gujarat' => ['Ahmedabad', 'Surat', 'Vadodara'],
            'Rajasthan' => ['Jaipur', 'Udaipur', 'Jodhpur'],
            'Uttar Pradesh' => ['Lucknow', 'Noida', 'Kanpur'],
            'West Bengal' => ['Kolkata', 'Howrah'],
            'Madhya Pradesh' => ['Indore', 'Bhopal'],
        ];

        foreach ($locations as $stateName => $cities) {
            $state = State::query()->firstOrCreate(
                ['country_id' => $country->id, 'name' => $stateName],
                ['is_active' => true]
            );

            foreach ($cities as $cityName) {
                City::query()->firstOrCreate(
                    ['state_id' => $state->id, 'name' => $cityName],
                    ['is_active' => true]
                );
            }
        }
    }
}
