<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['name' => 'Philippines', 'iso_code' => 'PH'],
            ['name' => 'United States', 'iso_code' => 'US'],
            ['name' => 'United Kingdom', 'iso_code' => 'GB'],
        ];
        foreach ($countries as $c) {
            Country::firstOrCreate(['iso_code' => $c['iso_code']], $c);
        }
    }
}
