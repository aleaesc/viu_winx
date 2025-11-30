<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminSettingsController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();
        // Only allow admin role if roles exist; otherwise allow any authenticated user
        // If you have a role/permission system, add checks here.

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_username' => ['required', 'string', 'min:3', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'new_password' => ['required', 'string', 'min:8'],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 422);
        }

        $user->username = $validated['new_username'];
        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return response()->json(['message' => 'Credentials updated successfully']);
    }
}
