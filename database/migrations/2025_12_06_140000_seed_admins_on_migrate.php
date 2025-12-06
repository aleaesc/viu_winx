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
            $generated = Str::random(16);
            Log::warning('Admin seed migration: No admins.json found; seeding default admin with generated password');
            // Best-effort console output
            try { fwrite(STDOUT, "Default admin seeded. username: admin, password: {$generated}\n"); } catch (\Throwable $e) {}
            $records = [[
                'username' => env('DEFAULT_ADMIN_USERNAME', 'admin'),
                'role' => 'admin',
                'name' => 'Admin',
                'email' => 'admin@example.local',
                'country_iso' => 'PH',
                'password' => $generated,
            ]];
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
