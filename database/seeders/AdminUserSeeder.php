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

        $users = [
            [
                'username' => 'superadminaleaa',
                'role' => 'superadmin',
                'name' => 'Admin Alea',
                'email' => 'adminalea@viu.com',
                'country_id' => $ph?->id,
                'password' => Hash::make('alea12345'),
            ],
            [
                'username' => 'admineya',
                'role' => 'admin',
                'name' => 'admineya',
                'email' => 'admineya@local.viu',
                'country_id' => $ph?->id,
                'password' => Hash::make('eya12345'),
            ],
            [
                'username' => 'adminwinx',
                'role' => 'admin',
                'name' => 'adminwinx',
                'email' => 'adminwinx@local.viu',
                'country_id' => $ph?->id,
                'password' => Hash::make('winx12345'),
            ],
            [
                'username' => 'adminviu',
                'role' => 'admin',
                'name' => 'adminviu',
                'email' => 'adminviu@local.viu',
                'country_id' => $ph?->id,
                'password' => Hash::make('viu12345'),
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['username' => $data['username']],
                $data
            );
        }
    }
}
