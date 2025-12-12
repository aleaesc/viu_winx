<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SuperAdminController extends Controller
{
    /**
     * List all admin accounts (excluding superadmin)
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            // Debug logging
            \Log::info('SuperAdmin Index - User:', [
                'user_id' => $user ? $user->id : null,
                'username' => $user ? $user->username : null,
                'role' => $user ? $user->role : null,
                'has_role_attribute' => $user ? isset($user->role) : false
            ]);
            
            // Only superadmin can access this
            if (!$user || $user->role !== 'superadmin') {
                return response()->json([
                    'message' => 'Unauthorized',
                    'debug' => [
                        'has_user' => (bool)$user,
                        'user_role' => $user ? $user->role : null,
                        'expected' => 'superadmin'
                    ]
                ], 403);
            }

            $admins = User::where('role', 'admin')
                ->select('id', 'username', 'role', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($admins);
        } catch (\Throwable $e) {
            \Log::error('SuperAdmin Index Error:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Failed to load admin accounts',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Create a new admin account
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();
            
            // Debug logging
            \Log::info('SuperAdmin Store - User:', [
                'user_id' => $user ? $user->id : null,
                'username' => $user ? $user->username : null,
                'role' => $user ? $user->role : null,
            ]);
            
            // Only superadmin can create admins
            if (!$user || $user->role !== 'superadmin') {
                return response()->json([
                    'message' => 'Unauthorized',
                    'debug' => [
                        'has_user' => (bool)$user,
                        'user_role' => $user ? $user->role : null,
                        'expected' => 'superadmin'
                    ]
                ], 403);
            }

            $validated = $request->validate([
                'username' => ['required', 'string', 'min:3', 'max:50', 'unique:users,username'],
                'password' => ['required', 'string', 'min:6'],
            ]);

            // Users table requires non-null name and email; synthesize sensible defaults
            $defaultName = $validated['username'];
            $defaultEmail = strtolower(preg_replace('/[^a-zA-Z0-9._-]/', '', $validated['username'])) . '@local.viu';

            $admin = User::create([
                'username' => $validated['username'],
                'name' => $defaultName,
                'email' => $defaultEmail,
                'password' => Hash::make($validated['password']),
                'role' => 'admin',
            ]);

            return response()->json([
                'message' => 'Admin created successfully',
                'admin' => [
                    'id' => $admin->id,
                    'username' => $admin->username,
                    'role' => $admin->role,
                    'created_at' => $admin->created_at,
                ]
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('SuperAdmin Store Error:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Failed to create admin',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Update an admin account
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            // Only superadmin can update admins
            if (!$user || $user->role !== 'superadmin') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $admin = User::where('role', 'admin')->find($id);
            
            if (!$admin) {
                return response()->json(['message' => 'Admin not found'], 404);
            }

            $validated = $request->validate([
                'username' => ['nullable', 'string', 'min:3', 'max:50', Rule::unique('users', 'username')->ignore($admin->id)],
                'password' => ['nullable', 'string', 'min:6'],
            ]);

            if (!empty($validated['username'])) {
                $admin->username = $validated['username'];
            }

            if (!empty($validated['password'])) {
                $admin->password = Hash::make($validated['password']);
            }

            $admin->save();

            return response()->json([
                'message' => 'Admin updated successfully',
                'admin' => [
                    'id' => $admin->id,
                    'username' => $admin->username,
                    'role' => $admin->role,
                    'created_at' => $admin->created_at,
                ]
            ]);
        } catch (\Throwable $e) {
            \Log::error('SuperAdmin Update Error:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'message' => 'Failed to update admin',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Delete an admin account
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            // Only superadmin can delete admins
            if (!$user || $user->role !== 'superadmin') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $admin = User::where('role', 'admin')->find($id);
            
            if (!$admin) {
                return response()->json(['message' => 'Admin not found'], 404);
            }

            $admin->delete();

            return response()->json(['message' => 'Admin deleted successfully']);
        } catch (\Throwable $e) {
            \Log::error('SuperAdmin Delete Error:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'message' => 'Failed to delete admin',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }
}
