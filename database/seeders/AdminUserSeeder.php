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

        // If no secrets file, seed the known 4 accounts with fixed passwords
        if (empty($records)) {
            Log::warning('AdminUserSeeder: No admins.json found. Seeding default 4 accounts');
            $records = [
                [ 'username' => 'superadminalea', 'role' => 'superadmin', 'name' => 'Alea', 'email' => 'alea@example.local', 'country_iso' => 'PH', 'password' => 'alea12345' ],
                [ 'username' => 'adminwinx',      'role' => 'admin',      'name' => 'Winx', 'email' => 'winx@example.local', 'country_iso' => 'PH', 'password' => 'winx12345' ],
                [ 'username' => 'adminmicah',     'role' => 'admin',      'name' => 'Micah','email' => 'micah@example.local','country_iso' => 'PH', 'password' => 'micah12345' ],
                [ 'username' => 'admineya',       'role' => 'admin',      'name' => 'Eya',  'email' => 'eya@example.local',  'country_iso' => 'PH', 'password' => 'eya12345' ],
            ];
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
