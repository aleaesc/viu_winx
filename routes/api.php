<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\SurveyController;
use App\Http\Controllers\Api\SurveyAnswerController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\AdminSettingsController;
use App\Http\Controllers\Api\PublicSurveyResponseController;
use App\Http\Controllers\Api\SurveyQuestionController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\DevAdminResetController;
use App\Http\Controllers\Api\SuperAdminController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Illuminate\Support\Facades\DB;

Route::get('/countries', [CountryController::class, 'index']);

// Lightweight health checks (to debug deploy issues)
Route::get('/health/db', function () {
    try {
        DB::select('SELECT 1');
        return response()->json(['ok' => true]);
    } catch (\Throwable $e) {
        return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
    }
});
Route::get('/health/users', function () {
    try {
        $count = DB::table('users')->count();
        return response()->json(['ok' => true, 'users' => $count]);
    } catch (\Throwable $e) {
        return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
    }
});

Route::post('/register', [AuthController::class, 'register'])
    ->withoutMiddleware([VerifyCsrfToken::class, EnsureFrontendRequestsAreStateful::class]);
Route::post('/login', [AuthController::class, 'login'])
    ->withoutMiddleware([VerifyCsrfToken::class, EnsureFrontendRequestsAreStateful::class]);

// Chatbot endpoint - using closure to avoid any controller autoload issues
Route::post('/chatbot/ask', function (Request $request) {
    try {
        \Log::info('Chatbot closure reached', ['ip' => $request->ip()]);
        $answer = 'Viu Fam, our assistant is warming up. Try again in a moment — or check the survey for now 😊';
        $cid = $request->input('conversation_id') ?? ('chat-' . time());
        return response()->json([
            'success' => true,
            'answer' => $answer,
            'data' => ['answer' => $answer],
            'conversation_id' => $cid
        ], 200);
    } catch (\Throwable $e) {
        \Log::error('Chatbot closure failed', ['error' => $e->getMessage()]);
        return response()->json([
            'success' => true,
            'answer' => 'Viu Fam, our assistant is warming up. Try again in a moment — or check the survey for now 😊',
            'data' => ['answer' => 'Viu Fam, our assistant is warming up. Try again in a moment — or check the survey for now 😊'],
            'conversation_id' => 'chat-' . time()
        ], 200);
    }
})->withoutMiddleware([VerifyCsrfToken::class, EnsureFrontendRequestsAreStateful::class]);

// Public survey responses (no auth, stateless)
Route::get('/public/responses', [PublicSurveyResponseController::class, 'index'])
    ->withoutMiddleware([VerifyCsrfToken::class, EnsureFrontendRequestsAreStateful::class]);
Route::post('/public/responses', [PublicSurveyResponseController::class, 'store'])
    ->withoutMiddleware([VerifyCsrfToken::class, EnsureFrontendRequestsAreStateful::class]);
Route::delete('/public/responses/{id}', [PublicSurveyResponseController::class, 'destroy'])
    ->withoutMiddleware([VerifyCsrfToken::class, EnsureFrontendRequestsAreStateful::class]);

// Public: active survey questions
Route::get('/questions', [SurveyQuestionController::class, 'index']);

// SuperAdmin: manage admin accounts (using closures to bypass autoload issues)
Route::get('/superadmin/admins', function (Request $request) {
    try {
        \Log::info('SuperAdmin GET closure reached');
        
        // Manual Bearer token authentication
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['message' => 'No authentication token provided'], 401);
        }
        
        $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        if (!$tokenModel) {
            return response()->json(['message' => 'Invalid or expired token'], 401);
        }
        
        $user = $tokenModel->tokenable;
        if (!$user || $user->role !== 'superadmin') {
            return response()->json(['message' => 'Unauthorized - superadmin role required'], 403);
        }
        
        $admins = \App\Models\User::where('role', 'admin')
            ->select('id', 'username', 'role', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($admins);
    } catch (\Throwable $e) {
        \Log::error('SuperAdmin GET failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        return response()->json(['message' => 'Failed to load admins', 'error' => $e->getMessage()], 500);
    }
})->withoutMiddleware([VerifyCsrfToken::class, EnsureFrontendRequestsAreStateful::class]);

Route::post('/superadmin/admins', function (Request $request) {
    try {
        \Log::info('SuperAdmin POST closure reached');
        
        // Manual Bearer token authentication
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['message' => 'No authentication token provided'], 401);
        }
        
        $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        if (!$tokenModel) {
            return response()->json(['message' => 'Invalid or expired token'], 401);
        }
        
        $user = $tokenModel->tokenable;
        if (!$user || $user->role !== 'superadmin') {
            return response()->json(['message' => 'Unauthorized - superadmin role required'], 403);
        }
        
        $validated = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:50', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6'],
        ]);
        
        $defaultName = $validated['username'];
        $defaultEmail = strtolower(preg_replace('/[^a-zA-Z0-9._-]/', '', $validated['username'])) . '@local.viu';
        
        $admin = \App\Models\User::create([
            'username' => $validated['username'],
            'name' => $defaultName,
            'email' => $defaultEmail,
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
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
        return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
    } catch (\Throwable $e) {
        \Log::error('SuperAdmin POST failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        return response()->json(['message' => 'Failed to create admin', 'error' => $e->getMessage()], 500);
    }
})->withoutMiddleware([VerifyCsrfToken::class, EnsureFrontendRequestsAreStateful::class]);
Route::put('/superadmin/admins/{id}', [SuperAdminController::class, 'update'])
    ->withoutMiddleware([VerifyCsrfToken::class, EnsureFrontendRequestsAreStateful::class]);
Route::delete('/superadmin/admins/{id}', [SuperAdminController::class, 'destroy'])
    ->withoutMiddleware([VerifyCsrfToken::class, EnsureFrontendRequestsAreStateful::class]);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/surveys', [SurveyController::class, 'index']);
    Route::get('/surveys/{survey}', [SurveyController::class, 'show']);
    Route::post('/surveys', [SurveyController::class, 'store'])->middleware('can:isAdmin');

    Route::get('/answers/mine', [SurveyAnswerController::class, 'mine']);
    Route::post('/answers', [SurveyAnswerController::class, 'store']);

    // Admin settings: update username/password (requires current password)
    Route::put('/admin/settings', [AdminSettingsController::class, 'update'])
        ->withoutMiddleware([VerifyCsrfToken::class, EnsureFrontendRequestsAreStateful::class]);

    // Admin: upsert survey questions
    Route::put('/admin/questions', [SurveyQuestionController::class, 'upsert']);

    // Admin: stats endpoint
    Route::get('/admin/stats', [StatsController::class, 'index']);

});

// Local-only temporary admin reset route
if (app()->environment('local')) {
    Route::post('/dev/reset-admin', [DevAdminResetController::class, 'reset'])
        ->withoutMiddleware([VerifyCsrfToken::class, EnsureFrontendRequestsAreStateful::class]);
}
