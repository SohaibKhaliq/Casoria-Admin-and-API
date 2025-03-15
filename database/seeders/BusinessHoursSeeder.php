<?php

namespace Database\Seeders;

use App\Models\Business;
use Illuminate\Database\Seeder;
use Modules\BussinessHour\Models\BussinessHour;

class BusinessHourSeeder extends Seeder
{
    public function run()
    {
        // if (env('IS_DUMMY_DATA')) {
        //     $businessHours = [
        //         [
        //             'day' => 'Monday',
        //             'start_time' => '09:00:00',
        //             'end_time' => '18:00:00',
        //             'is_holiday' => 0,
        //             'breaks' => '',
        //         ],
        //         // Add more business hour data here
        //     ];

        //     $businesses = Business::pluck('id'); // Get an array of all business IDs

        //     foreach ($businessHours as $businessHour) {
        //         $businessHour['business_id'] = $businesses->random(); // Randomly select a business ID

        //         BussinessHour::create($businessHour);
        //     }
        // }
    }
}
