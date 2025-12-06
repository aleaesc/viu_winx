<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Country;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $ph = Country::where('iso_code', 'PH')->first();

        // Use fixed, precomputed bcrypt hashes so credentials are consistent across environments
        // and no plain-text passwords appear in source. These should match the desired usernames.
        // Note: rotate these hashes when changing the canonical passwords.
        $hashes = [
            'superadmin' => '$2y$12$vsasqSeLsM6SeztRhNqreuAXYNfNs0J...REPLACE',
            'admin_default' => '$2y$12$eN6.LY7G4qbKZwvShBqmOstc3EdFinN...REPLACE',
        ];

        $users = [
            [
                'username' => 'superadminaleaa',
                'role' => 'superadmin',
                'name' => 'Admin Alea',
                'email' => 'adminalea@viu.com',
                'country_id' => $ph?->id,
                'password' => $hashes['superadmin'],
            ],
            [
                'username' => 'admineya',
                'role' => 'admin',
                'name' => 'admineya',
                'email' => 'admineya@local.viu',
                'country_id' => $ph?->id,
                'password' => $hashes['admin_default'],
            ],
            [
                'username' => 'adminwinx',
                'role' => 'admin',
                'name' => 'adminwinx',
                'email' => 'adminwinx@local.viu',
                'country_id' => $ph?->id,
                'password' => $hashes['admin_default'],
            ],
            [
                'username' => 'adminviu',
                'role' => 'admin',
                'name' => 'adminviu',
                'email' => 'adminviu@local.viu',
                'country_id' => $ph?->id,
                'password' => $hashes['admin_default'],
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
