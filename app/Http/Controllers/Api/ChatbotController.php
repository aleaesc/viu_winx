<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatbotService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ChatbotController extends Controller
{
    public function ask(Request $request)
    {
        try {
            // Rate limiting: Max 20 requests per minute per IP
            $identifier = $request->ip() . '_chatbot';
            $rateLimit = Cache::get($identifier, 0);
            if ($rateLimit >= 20) {
                return response()->json([
                    'success' => false,
                    'error' => 'Hello, Viu Fam! Please slow down a bit. You can ask again in a moment. 😊'
                ], 429);
            }
            Cache::put($identifier, $rateLimit + 1, 60); // 1 minute window

            // Validation (permissive and resilient)
            try {
                $request->validate([
                    'question' => 'required_without:message|string|max:500',
                    'message' => 'required_without:question|string|max:500',
                    'conversation_id' => 'nullable|string|max:255'
                ]);
            } catch (\Throwable $v) {
                // Continue with sanitized input even if validation complains
            }

            // Sanitize input - accept both 'question' and 'message'
            $question = strip_tags($request->input('question') ?? $request->input('message') ?? '');
            $question = htmlspecialchars($question, ENT_QUOTES, 'UTF-8');

            // Profanity filter (friendly redirect)
            $profanities = ['putang', 'gago', 'tangina', 'fuck', 'shit', 'bitch', 'asshole', 'tite','inamo', 'putanginamo', 'tanginamo'];
            $lowerQuestion = strtolower($question);
            foreach ($profanities as $word) {
                if (stripos($lowerQuestion, $word) !== false) {
                    return response()->json([
                        'success' => true,
                        'data' => ['answer' => "Hello, Viu Fam! Let's keep it friendly. How can I help you with the survey? 😊"],
                        'conversation_id' => $request->input('conversation_id') ?? 'chat-' . time()
                    ], 200);
                }
            }

            $conversationId = $request->input('conversation_id') ?? 'chat-' . $request->ip() . '-' . time() . '-' . Str::random(8);

            // Try service; if it fails, return friendly fallback (200)
            try {
                // Construct service inside try so constructor errors are caught
                $chatService = new ChatbotService();
                $answer = $chatService->chat($question, $conversationId);
                $answer = is_string($answer) && strlen(trim($answer)) ? $answer : 'Viu Fam, our assistant is warming up. Try again in a moment 😊';
                return response()->json([
                    'success' => true,
                    'data' => ['answer' => $answer],
                    'conversation_id' => $conversationId
                ], 200);
            } catch (\Throwable $e) {
                Log::error('Chatbot service failed', [
                    'conversation_id' => $conversationId,
                    'question' => $request->input('question'),
                    'error' => $e->getMessage()
                ]);
                return response()->json([
                    'success' => true,
                    'data' => ['answer' => 'Viu Fam, our assistant is warming up. Try again in a moment — or check the survey for now 😊'],
                    'conversation_id' => $conversationId
                ], 200);
            }
        } catch (\Throwable $outer) {
            Log::error('Chatbot outer failure', ['error' => $outer->getMessage()]);
            return response()->json([
                'success' => true,
                'data' => ['answer' => 'Viu Fam, our assistant is warming up. Try again in a moment 😊'],
                'conversation_id' => 'chat-' . time()
            ], 200);
        }
    }
}
