<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use App\Models\Address;
use App\Models\Business;
use Modules\BussinessHour\Models\BussinessHour;
use Modules\Service\Models\ServiceBusinesses;

class BusinessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (env('IS_DUMMY_DATA')) {
            $days = [
                ['day' => 'monday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
                ['day' => 'tuesday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
                ['day' => 'wednesday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
                ['day' => 'thursday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
                ['day' => 'friday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
                ['day' => 'saturday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
                ['day' => 'sunday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => true, 'breaks' => []],
            ];
            $businesses = [
                [
                    'address' => [
                        'postal_code' => '544512',
                        'address_line_1' => '123 Main St',
                        'address_line_2' => '',
                        'city' => '10001',
                        'state' => '3924',
                        'country' => '231',
                        'latitude' => '37.7749',
                        'longitude' => '-122.4194',
                    ],
                    'name' => 'Glamour Cuts',
                    'manager_id' => null,
                    'feature_image' => public_path('/dummy-images/businesses/1.png'),
                    'contact_email' => '',
                    'contact_number' => '',
                    'payment_method' => ['cash', 'debit_card', 'credit_card', 'upi'],
                    'business_for' => 'unisex',
                    'contact_number' => '2012345678',
                    'contact_email' => 'info@glamourcuts.co.uk',
                ],
                [
                    'address' => [
                        'postal_code' => '56156',
                        'address_line_1' => '456 First Ave',
                        'address_line_2' => '',
                        'city' => '10004',
                        'state' => '3842',
                        'country' => '230',
                        'latitude' => '34.0522',
                        'longitude' => '-118.2437',
                    ],
                    'name' => 'Serene Styles',
                    'manager_id' => null,
                    'feature_image' => public_path('/dummy-images/businesses/2.png'),
                    'contact_email' => '',
                    'contact_number' => '',
                    'payment_method' => ['cash', 'debit_card', 'credit_card'],
                    'business_for' => 'male',
                    'contact_number' => '2987654321',
                    'contact_email' => 'hello@serenestyles.com.au',
                ],
                [
                    'address' => [
                        'postal_code' => '54165',
                        'address_line_1' => '789 Second St',
                        'address_line_2' => '',
                        'city' => '10003',
                        'state' => '3956',
                        'country' => '231',
                        'latitude' => '39.9526',
                        'longitude' => '-75.1652',
                    ],
                    'name' => 'Trendy Trims',
                    'manager_id' => null,
                    'feature_image' => public_path('/dummy-images/businesses/3.png'),
                    'contact_email' => '',
                    'contact_number' => '',
                    'payment_method' => ['cash', 'debit_card'],
                    'business_for' => 'male',
                    'contact_number' => ' 3987654325',
                    'contact_email' => 'info@trendytrims.com.au',
                ],
                [
                    'address' => [
                        'postal_code' => '54155',
                        'address_line_1' => '321 Third Ave',
                        'address_line_2' => '',
                        'city' => '10002',
                        'state' => '3924',
                        'country' => '231',
                        'latitude' => '29.7604',
                        'longitude' => '-95.3698',
                    ],
                    'name' => 'Chic Curls',
                    'manager_id' => null,
                    'feature_image' => public_path('/dummy-images/businesses/4.png'),
                    'contact_email' => '',
                    'contact_number' => '',
                    'payment_method' => ['cash', 'credit_card'],
                    'business_for' => 'female',
                    'contact_number' => '1612345678',
                    'contact_email' => 'hello@chiccurls.co.uk',
                ],
                [
                    'address' => [
                        'postal_code' => '75201',
                        'address_line_1' => '654 Fourth St',
                        'address_line_2' => '',
                        'city' => '10005',
                        'state' => '3842',
                        'country' => '230',
                        'latitude' => '40.7128',
                        'longitude' => '-74.0060',
                    ],
                    'name' => 'Style Hub',
                    'manager_id' => null,
                    'feature_image' => public_path('/dummy-images/businesses/5.png'),
                    'contact_email' => '',
                    'contact_number' => '',
                    'payment_method' => ['cash', 'debit_card', 'upi'],
                    'business_for' => 'unisex',
                    'contact_number' => ' 1212345678',
                    'contact_email' => 'info@stylehub.co.uk',
                ],
            ];

            foreach ($businesses as $business) {
                $address = $business['address'];
                $featureImage = $business['feature_image'] ?? null;
                $businessData = Arr::except($business, ['feature_image', 'address']);
                $br = Business::create($businessData);
                $this->attachFeatureImage($br, $featureImage);
                $br->address()->save(new Address($address));
                foreach ($days as $key => $val) {
                    $val['business_id'] = $br->id;
                    BussinessHour::create($val);
                }
            }
        }
    }

    private function attachFeatureImage($model, $publicPath)
    {
        if (! env('IS_DUMMY_DATA_IMAGE')) {
            return false;
        }

        $file = new \Illuminate\Http\File($publicPath);

        $media = $model->addMedia($file)->preservingOriginal()->toMediaCollection('feature_image');

        return $media;
    }
}
