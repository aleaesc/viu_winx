<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        $now = Carbon::now();

        $records = [];
        $seedPath = storage_path('seed/admins.json');
        if (File::exists($seedPath)) {
            $json = File::get($seedPath);
            $parsed = json_decode($json, true);
            if (is_array($parsed)) {
                $records = $parsed;
            } else {
                Log::warning('Admin seed migration: Invalid JSON in '.$seedPath);
            }
        }

        if (empty($records)) {
            // Embed exact four admins using precomputed bcrypt hashes (no plaintext in repo)
            $records = [
                [
                    'username' => 'superadminaleaa',
                    'role' => 'superadmin',
                    'name' => 'Admin Alea',
                    'email' => 'adminalea@viu.com',
                    'country_iso' => 'PH',
                    'password_hash' => '$2y$12$j2/UC5x.SgoJUbLA6pJxTenu6gy/9IkISdwH2aWREh0u5g5TQIkIa', // alea12345
                ],
                [
                    'username' => 'admineya',
                    'role' => 'admin',
                    'name' => 'admineya',
                    'email' => 'admineya@local.viu',
                    'country_iso' => 'PH',
                    'password_hash' => '$2y$12$0KsIf8xPOJc.t3ykJU9BU.wmae/Yzqw6CsM8k/z4y74kImgaSNQJi', // eya12345
                ],
                [
                    'username' => 'adminwinx',
                    'role' => 'admin',
                    'name' => 'adminwinx',
                    'email' => 'adminwinx@local.viu',
                    'country_iso' => 'PH',
                    'password_hash' => '$2y$12$pxcKpZJf3mXCG/reWZphfOT24p1JpFnBIWkQPDI/tSqShfTtAjHdK', // winx12345
                ],
                [
                    'username' => 'adminviu',
                    'role' => 'admin',
                    'name' => 'adminviu',
                    'email' => 'adminviu@local.viu',
                    'country_iso' => 'PH',
                    'password_hash' => '$2y$12$Mljw6FO84rubBsGfvHCJbuFHrw12hBKDYTkmn1dTQqAm2yXUb89Qq', // viu12345
                ],
            ];
        }

        // Map ISO -> id once
        $countryMap = [];
        if (Schema::hasTable('countries')) {
            foreach (DB::table('countries')->select('id','iso_code')->get() as $row) {
                $countryMap[strtoupper($row->iso_code)] = $row->id;
            }
        }

        foreach ($records as $row) {
            $username = $row['username'] ?? null;
            if (!$username) { continue; }
            $countryId = null;
            if (!empty($row['country_iso'])) {
                $iso = strtoupper($row['country_iso']);
                $countryId = $countryMap[$iso] ?? null;
            }

            $data = [
                'username' => $username,
                'role' => $row['role'] ?? 'admin',
                'name' => $row['name'] ?? $username,
                'email' => $row['email'] ?? ($username.'@example.local'),
                'country_id' => $countryId,
                'updated_at' => $now,
            ];
            if (!empty($row['password_hash'])) {
                $data['password'] = $row['password_hash'];
            } elseif (!empty($row['password'])) {
                $data['password'] = Hash::make($row['password']);
            }

            // Upsert by username
            $exists = DB::table('users')->where('username', $username)->first();
            if ($exists) {
                DB::table('users')->where('id', $exists->id)->update($data);
            } else {
                $data['created_at'] = $now;
                DB::table('users')->insert($data);
            }
        }
    }

    public function down(): void
    {
        // No-op: do not remove admin users on rollback.
    }
};
