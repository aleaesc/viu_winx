<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Country;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $country = null;
        if (!empty($data['country_iso'])) {
            $country = Country::where('iso_code', strtoupper($data['country_iso']))->first();
        }
        $user = User::create([
            'username' => $data['username'],
            'name' => $data['name'],
            'optional_name' => $data['optional_name'] ?? null,
            'email' => $data['email'],
            'country_id' => $country?->id,
            'password' => Hash::make($data['password']),
        ]);
        $token = $user->createToken('auth')->plainTextToken;
        return response()->json(['user' => new UserResource($user), 'token' => $token], 201);
    }

    public function login(LoginRequest $request)
    {
        $data = $request->validated();
        $user = User::where('username', $data['username'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        $token = $user->createToken('auth')->plainTextToken;
        return response()->json(['user' => new UserResource($user), 'token' => $token]);
    }

    public function me(Request $request)
    {
        return new UserResource($request->user()->load('country'));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}
