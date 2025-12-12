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

// Chatbot endpoint: inline fallback to guarantee 200s in production
Route::post('/chatbot/ask', function (Request $request) {
    try {
        $raw = $request->input('question') ?? $request->input('message') ?? '';
        $q = is_string($raw) ? strtolower(trim($raw)) : '';
        $isTagalog = (bool) preg_match('/\b(kamusta|kumusta|paano|pano|magkano|presyo|salamat|saan|kelan|opo|po|oo)\b/i', $q);
        $reply = '';

        if (preg_match('/\b(hi|hello|hey|kamusta|kumusta|yo|sup)\b/i', $q)) {
            $reply = $isTagalog ? 'Kamusta, Viu Fam! 👋 Tanong ka lang sa akin tungkol sa Viu o survey!' : 'Hello, Viu Fam! 👋 Ask me anything about Viu or the survey!';
        } elseif (preg_match('/\b(subscribe|subscription|premium|plan|mag\s?subscribe)\b/i', $q)) {
            $reply = $isTagalog ? "Para mag-Premium: App → Premium → Piliin plan → Bayad. Walang ads, HD, at pwede download! ✨" : "To get Premium: App → Premium → Choose a plan → Pay. Enjoy ad-free, HD, and downloads! ✨";
        } elseif (preg_match('/\b(price|pricing|cost|magkano|presyo|how much)\b/i', $q)) {
            $reply = $isTagalog ? 'Presyo depende sa bansa. I-check sa Viu app ang latest. May monthly at yearly plans! 💰' : 'Pricing varies by region. Check the Viu app for current rates. Monthly and yearly plans available! 💰';
        } elseif (preg_match('/\b(download|offline|save)\b/i', $q)) {
            $reply = $isTagalog ? 'Para mag-download: Buksan ang episode → pindutin ang download icon. Premium ang best dito. 📱' : 'To download: Open an episode → tap the download icon. Premium gives best quality. 📱';
        } elseif (preg_match('/\b(cancel|unsubscribe|stop)\b/i', $q)) {
            $reply = $isTagalog ? 'Cancel: Profile → Subscription → Cancel. Magagamit pa rin hanggang end ng billing period. 😊' : 'Cancel: Profile → Subscription → Cancel. You keep access until the end of the billing period. 😊';
        } elseif (preg_match('/\b(device|devices|screens|how many)\b/i', $q)) {
            $reply = $isTagalog ? 'Pwede sa maraming devices. Log in lang sa iisang account. May limit sa sabay na streams. 📺' : 'Use multiple devices — log in with the same account. Simultaneous streaming limits may apply. 📺';
        } elseif (preg_match('/\b(kdrama|korean|k.?drama)\b/i', $q)) {
            $reply = $isTagalog ? 'Oo! Maraming K-dramas at variety shows. Tingnan ang K-Drama section sa app! 🇰🇷' : 'Absolutely! Tons of K‑dramas and variety shows. Check the K‑Drama section in the app! 🇰🇷';
        } elseif (preg_match('/\b(genre|categories|type|content)\b/i', $q)) {
            $reply = $isTagalog ? 'May K‑dramas, C‑dramas, anime, movies, variety shows, at iba pa! 🎬' : 'We have K‑dramas, C‑dramas, anime, movies, variety shows, and more! 🎬';
        } elseif (preg_match('/\b(password|forgot|reset|login)\b/i', $q)) {
            $reply = $isTagalog ? 'Password: Settings → Security → Change. Nakalimutan? Gamitin ang “Forgot Password” sa login para sa email reset. 🔐' : "Password: Settings → Security → Change. Forgot it? Use 'Forgot Password' on login to reset via email. 🔐";
        } elseif (preg_match('/\b(subtitle|subtitles|dub|language)\b/i', $q)) {
            $reply = $isTagalog ? 'Maraming subtitle languages! Habang nanonood: Settings icon → piliin ang language. 🗣️' : 'Multiple subtitle languages! While watching: Settings icon → choose language. 🗣️';
        } elseif (preg_match('/\b(quality|hd|4k|resolution|buffer|blurry|pixel)\b/i', $q)) {
            $reply = $isTagalog ? 'Quality depende sa internet at plan. Premium may HD. Subukan baguhin ang quality sa settings. 📺' : 'Quality depends on internet and plan. Premium gets HD. Try adjusting quality in settings. 📺';
        } elseif (preg_match('/\b(thank|thanks|salamat)\b/i', $q)) {
            $reply = $isTagalog ? 'Walang anuman, Viu Fam! 😊' : "You're welcome, Viu Fam! 😊";
        } else {
            $reply = $isTagalog ? 'Pwede kitang tulungan sa subscriptions, downloads, devices, content, at account settings. Anong gusto mong malaman? 🤔' : 'I can help with subscriptions, downloads, devices, content, and account settings. What would you like to know? 🤔';
        }

        return response()->json([
            'success' => true,
            'data' => ['answer' => $reply],
            'conversation_id' => $request->input('conversation_id') ?? ('chat-' . time())
        ], 200);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => true,
            'data' => ['answer' => 'Viu Fam, our assistant is warming up. Try again in a moment 😊'],
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
