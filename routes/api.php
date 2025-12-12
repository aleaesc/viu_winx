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

// Chatbot endpoint - responds to common questions
Route::post('/chatbot/ask', function (Request $request) {
    $question = strtolower($request->input('question', ''));
    
    // Local knowledge base responses
    if (preg_match('/\b(hi|hello|hey|kamusta|kumusta)\b/i', $question)) {
        $answer = "Hi, Viu Fam! 👋 I'm here to help! Ask me about subscriptions, downloads, devices, or anything about Viu!";
    } elseif (preg_match('/\b(subscribe|subscription|mag.?subscribe|plan)\b/i', $question)) {
        $answer = "To subscribe to Viu Premium: Open the Viu app → Go to 'Premium' → Choose your plan → Complete payment. You'll get access to exclusive content and ad-free viewing! 🌟";
    } elseif (preg_match('/\b(price|magkano|cost|how much)\b/i', $question)) {
        $answer = "Premium pricing varies by region. Check the Viu app for the current rates available in your country. We offer monthly and yearly plans! 💰";
    } elseif (preg_match('/\b(download|offline)\b/i', $question)) {
        $answer = "To download shows: Tap the download icon on any episode in the Viu app. Watch offline anytime! Download quality depends on your subscription plan. 📱";
    } elseif (preg_match('/\b(cancel|unsubscribe)\b/i', $question)) {
        $answer = "To cancel your subscription: Go to Account → Subscription → Choose 'Cancel'. You'll keep access until your billing period ends. No worries, you can always come back! 😊";
    } elseif (preg_match('/\b(device|devices|how many)\b/i', $question)) {
        $answer = "You can use Viu on multiple devices! Log in with your account on any device. Simultaneous streaming limits may apply depending on your plan. 📺";
    } elseif (preg_match('/\b(korean|kdrama|k.?drama)\b/i', $question)) {
        $answer = "Yes! We have tons of Korean dramas and variety shows! From latest releases to classic favorites. Check out the K-Drama section in the app! 🇰🇷";
    } elseif (preg_match('/\b(genre|categories|type)\b/i', $question)) {
        $answer = "We have K-dramas, C-dramas, anime, variety shows, movies, and more! Browse by genre in the app to find your favorites. 🎬";
    } elseif (preg_match('/\b(password|forgot|reset)\b/i', $question)) {
        $answer = "To change password: Account Settings → Security → Change Password. If you forgot it, use 'Forgot Password' on the login page to reset via email. 🔐";
    } elseif (preg_match('/\b(subtitle|subtitles|dub)\b/i', $question)) {
        $answer = "Most shows have multiple subtitle languages! Some also have dubbed versions. Check the settings icon while watching to change subtitle language. 🗣️";
    } elseif (preg_match('/\b(quality|hd|4k|resolution)\b/i', $question)) {
        $answer = "Streaming quality depends on your internet speed and subscription plan. Premium members get access to HD quality! 📺";
    } elseif (preg_match('/\b(thank|thanks)\b/i', $question)) {
        $answer = "You're welcome, Viu Fam! Anything else I can help with? 😊";
    } else {
        $answer = "I'm here to help with questions about Viu subscriptions, downloads, devices, content, and account settings! What would you like to know? 🤔";
    }
    
    return response()->json([
        'success' => true,
        'answer' => $answer,
        'data' => ['answer' => $answer],
        'conversation_id' => $request->input('conversation_id', 'chat-' . time())
    ], 200);
});

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
