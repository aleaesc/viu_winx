<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Country;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $ph = Country::where('iso_code', 'PH')->first();
        $user = User::updateOrCreate(
            ['username' => 'adminalea'],
            [
                'name' => 'Admin Alea',
                'optional_name' => null,
                'email' => 'adminalea@viu.com',
                'country_id' => $ph?->id,
                'password' => Hash::make('alea1234'),
            ]
        );
    }
}
