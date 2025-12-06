<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Country;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $ph = Country::where('iso_code', 'PH')->first();

        $superadminPassword = env('SUPERADMIN_PASSWORD');
        $defaultAdminPassword = env('DEFAULT_ADMIN_PASSWORD');

        if (!$superadminPassword || !$defaultAdminPassword) {
            Log::warning('AdminUserSeeder: Missing SUPERADMIN_PASSWORD or DEFAULT_ADMIN_PASSWORD in .env. Generating temporary random passwords.');
            $superadminPassword = $superadminPassword ?: Str::random(16);
            $defaultAdminPassword = $defaultAdminPassword ?: Str::random(16);
            // Output to console when running seeder so developers can see the generated passwords immediately.
            if (property_exists($this, 'command') && $this->command) {
                $this->command->warn('AdminUserSeeder: Generated temporary passwords');
                $this->command->line('  superadmin: ' . $superadminPassword);
                $this->command->line('  admin:      ' . $defaultAdminPassword);
            }
            Log::warning('AdminUserSeeder: Generated passwords (store securely, rotate in production).');
        }

        $users = [
            [
                'username' => 'superadminaleaa',
                'role' => 'superadmin',
                'name' => 'Admin Alea',
                'email' => 'adminalea@viu.com',
                'country_id' => $ph?->id,
                'password' => Hash::make($superadminPassword),
            ],
            [
                'username' => 'admineya',
                'role' => 'admin',
                'name' => 'admineya',
                'email' => 'admineya@local.viu',
                'country_id' => $ph?->id,
                'password' => Hash::make($defaultAdminPassword),
            ],
            [
                'username' => 'adminwinx',
                'role' => 'admin',
                'name' => 'adminwinx',
                'email' => 'adminwinx@local.viu',
                'country_id' => $ph?->id,
                'password' => Hash::make($defaultAdminPassword),
            ],
            [
                'username' => 'adminviu',
                'role' => 'admin',
                'name' => 'adminviu',
                'email' => 'adminviu@local.viu',
                'country_id' => $ph?->id,
                'password' => Hash::make($defaultAdminPassword),
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
