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

// Chatbot endpoint
Route::post('/chatbot/ask', [ChatbotController::class, 'ask'])
    ->withoutMiddleware([VerifyCsrfToken::class, EnsureFrontendRequestsAreStateful::class]);

// Public survey responses (no auth, stateless)
Route::get('/public/responses', [PublicSurveyResponseController::class, 'index'])
    ->withoutMiddleware([VerifyCsrfToken::class, EnsureFrontendRequestsAreStateful::class]);
Route::post('/public/responses', [PublicSurveyResponseController::class, 'store'])
    ->withoutMiddleware([VerifyCsrfToken::class, EnsureFrontendRequestsAreStateful::class]);
Route::delete('/public/responses/{id}', [PublicSurveyResponseController::class, 'destroy'])
    ->withoutMiddleware([VerifyCsrfToken::class, EnsureFrontendRequestsAreStateful::class]);

// Public: active survey questions
Route::get('/questions', [SurveyQuestionController::class, 'index']);

// SuperAdmin: manage admin accounts (auth handled in controller)
Route::get('/superadmin/admins', [SuperAdminController::class, 'index'])
    ->withoutMiddleware([VerifyCsrfToken::class, EnsureFrontendRequestsAreStateful::class]);
Route::post('/superadmin/admins', [SuperAdminController::class, 'store'])
    ->withoutMiddleware([VerifyCsrfToken::class, EnsureFrontendRequestsAreStateful::class]);
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
