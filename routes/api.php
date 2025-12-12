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

// Super simple test endpoint
Route::get('/ping', function () {
    return response()->json(['pong' => time()], 200);
});

// Test POST endpoint
Route::post('/test-post', function () {
    return response()->json(['status' => 'POST works', 'time' => time()], 200);
});

// Simple hello endpoint (sanity)
Route::get('/hello', function () {
    return response()->json(['hello' => 'world'], 200);
});

// Temp: cache clear endpoint (remove after deploy)
Route::get('/cache/clear', function (Illuminate\Http\Request $request) {
    if ($request->query('secret') !== 'viu2025clear') {
        return response()->json(['error' => 'unauthorized'], 403);
    }
    try {
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        \Illuminate\Support\Facades\Artisan::call('config:cache');
        return response()->json(['ok' => true, 'message' => 'caches cleared'], 200);
    } catch (\Throwable $e) {
        return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
    }
});

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

// Check if logging is writable in production
Route::get('/health/log', function () {
    try {
        \Illuminate\Support\Facades\Log::info('health-log test write');
        return response()->json(['ok' => true]);
    } catch (\Throwable $e) {
        return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
    }
});

Route::post('/register', [AuthController::class, 'register'])
    ->withoutMiddleware([VerifyCsrfToken::class, EnsureFrontendRequestsAreStateful::class]);
Route::post('/login', [AuthController::class, 'login'])
    ->withoutMiddleware([VerifyCsrfToken::class, EnsureFrontendRequestsAreStateful::class]);

Route::post('/chatbot/ask', [ChatbotController::class, 'ask'])
    ->withoutMiddleware([VerifyCsrfToken::class, EnsureFrontendRequestsAreStateful::class]);

// Diagnostics: check chatbot provider & health
Route::get('/chatbot/diag', function () {
    try {
        $svc = new \App\Services\ChatbotService();
        $diag = $svc->getDiagnostics();
        return response()->json(['ok' => true, 'diag' => $diag], 200);
    } catch (\Throwable $e) {
        return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
    }
})->withoutMiddleware([VerifyCsrfToken::class, EnsureFrontendRequestsAreStateful::class]);

// Minimal probe without logging or service instantiation
Route::get('/chatbot/probe', function () {
    $groqKeys = array_filter([
        env('GROQ_API_KEY_1'),
        env('GROQ_API_KEY_2'),
        env('GROQ_API_KEY_3'),
        env('GROQ_API_KEY_4'),
    ]);
    return response()->json([
        'ok' => true,
        'groq_keys' => count($groqKeys),
        'has_openai' => (bool) env('OPENAI_API_KEY'),
        'has_gemini' => (bool) env('GEMINI_API_KEY'),
    ], 200);
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

// SuperAdmin: manage admin accounts
Route::get('/superadmin/admins', function (Request $request) {
    $token = $request->bearerToken();
    if (!$token) {
        return response()->json(['message' => 'No token'], 401);
    }
    
    $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
    if (!$tokenModel || !$tokenModel->tokenable) {
        return response()->json(['message' => 'Invalid token'], 401);
    }
    
    $user = $tokenModel->tokenable;
    if ($user->role !== 'superadmin') {
        return response()->json(['message' => 'Forbidden'], 403);
    }
    
    $admins = \App\Models\User::where('role', 'admin')
        ->select('id', 'username', 'role', 'created_at')
        ->orderBy('created_at', 'desc')
        ->get();
        
    return response()->json($admins);
});

Route::post('/superadmin/admins', function (Request $request) {
    $token = $request->bearerToken();
    if (!$token) {
        return response()->json(['message' => 'No token'], 401);
    }
    
    $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
    if (!$tokenModel || !$tokenModel->tokenable) {
        return response()->json(['message' => 'Invalid token'], 401);
    }
    
    $user = $tokenModel->tokenable;
    if ($user->role !== 'superadmin') {
        return response()->json(['message' => 'Forbidden'], 403);
    }
    
    $username = $request->input('username');
    $password = $request->input('password');
    
    if (!$username || !$password || strlen($username) < 3 || strlen($password) < 6) {
        return response()->json(['message' => 'Invalid input'], 422);
    }
    
    if (\App\Models\User::where('username', $username)->exists()) {
        return response()->json(['message' => 'Username already exists'], 422);
    }
    
    $admin = \App\Models\User::create([
        'username' => $username,
        'name' => $username,
        'email' => strtolower(preg_replace('/[^a-zA-Z0-9._-]/', '', $username)) . '@local.viu',
        'password' => \Illuminate\Support\Facades\Hash::make($password),
        'role' => 'admin',
    ]);
    
    return response()->json([
        'message' => 'Admin created',
        'admin' => [
            'id' => $admin->id,
            'username' => $admin->username,
            'role' => $admin->role,
            'created_at' => $admin->created_at,
        ]
    ], 201);
});
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
