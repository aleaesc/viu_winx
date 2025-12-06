<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Country;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $ph = Country::where('iso_code', 'PH')->first();

        // Load admin secrets from a local file not tracked by git
        // Path: storage/seed/admins.json (create locally). Example entry structure:
        // [{"username":"superadmin","role":"superadmin","name":"Alice","email":"alice@domain","country_iso":"PH","password":"plain or omit and use password_hash","password_hash":"$2y$..."}]
        $seedPath = storage_path('seed/admins.json');
        $records = [];
        if (File::exists($seedPath)) {
            $json = File::get($seedPath);
            $parsed = json_decode($json, true);
            if (is_array($parsed)) {
                $records = $parsed;
            } else {
                Log::warning('AdminUserSeeder: Invalid JSON in '.$seedPath);
            }
        }

        // If no secrets file, create a single default admin with a generated password
        if (empty($records)) {
            $generated = Str::random(16);
            if (property_exists($this, 'command') && $this->command) {
                $this->command->warn('AdminUserSeeder: No storage/seed/admins.json found. Seeding a default admin.');
                $this->command->line('  username: admin');
                $this->command->line('  password: '.$generated);
            }
            Log::warning('AdminUserSeeder: Seeding default admin with generated password');
            $records = [[
                'username' => env('DEFAULT_ADMIN_USERNAME', 'admin'),
                'role' => 'admin',
                'name' => 'Admin',
                'email' => 'admin@example.local',
                'country_iso' => 'PH',
                'password' => $generated,
            ]];
        }

        foreach ($records as $row) {
            $countryId = $ph?->id;
            if (!empty($row['country_iso'])) {
                $c = Country::where('iso_code', strtoupper($row['country_iso']))->first();
                $countryId = $c?->id ?: $countryId;
            }
            $payload = [
                'username' => $row['username'],
                'role' => $row['role'] ?? 'admin',
                'name' => $row['name'] ?? $row['username'],
                'email' => $row['email'] ?? ($row['username'].'@example.local'),
                'country_id' => $countryId,
            ];
            if (!empty($row['password_hash'])) {
                $payload['password'] = $row['password_hash'];
            } elseif (!empty($row['password'])) {
                $payload['password'] = Hash::make($row['password']);
            }

            User::updateOrCreate(
                ['username' => $payload['username']],
                $payload
            );
        }
    }
}
