<?php

namespace Database\Seeders;

use App\Models\OfficeLocation;
use Illuminate\Database\Seeder;

class DefaultOfficeLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        OfficeLocation::updateOrCreate(
            ['name' => 'Kanwil DJPb Provinsi Lampung'],
            [
                'address' => 'Jl. Teuku Umar No. 187, Kedaton, Kec. Kedaton, Kota Bandar Lampung, Lampung 35148',
                'latitude' => -5.3971400,
                'longitude' => 105.2667900,
                'radius_meters' => 100,
                'is_active' => true,
            ]
        );
    }
}
