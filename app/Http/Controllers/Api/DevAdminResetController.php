<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DevAdminResetController extends Controller
{
    public function reset(Request $request)
    {
        // Simple validation
        $data = $request->validate([
            'username' => ['required','string','max:255'],
            'password' => ['required','string','min:6','max:255'],
        ]);

        // Try to locate by name first, fallback to email if provided
        $user = User::where('name', $data['username'])->first();
        if (!$user && $request->filled('email')) {
            $user = User::where('email', $request->input('email'))->first();
        }

        if (!$user) {
            // Create if missing, set a default email
            $user = User::create([
                'name' => $data['username'],
                'email' => $request->input('email', 'admin@example.com'),
                'password' => Hash::make($data['password']),
            ]);
        } else {
            // Update username and password
            $user->name = $data['username'];
            $user->password = Hash::make($data['password']);
            $user->save();
        }

        return response()->json(['ok' => true, 'user_id' => $user->id]);
    }
}
