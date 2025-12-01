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
    public function ask(Request $request, ChatbotService $chatService)
    {
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
        
        $request->validate([
            'question' => 'required_without:message|string|max:500|regex:/^[\p{L}\p{N}\p{P}\s]+$/u',
            'message' => 'required_without:question|string|max:500|regex:/^[\p{L}\p{N}\p{P}\s]+$/u',
            'conversation_id' => 'nullable|string|max:255|alpha_dash'
        ]);
        
        // Sanitize input (remove potential XSS) - accept both 'question' and 'message'
        $question = strip_tags($request->input('question') ?? $request->input('message'));
        $question = htmlspecialchars($question, ENT_QUOTES, 'UTF-8');
        
        // Profanity filter
        $profanities = ['putang', 'gago', 'tangina', 'fuck', 'shit', 'bitch', 'asshole'];
        $lowerQuestion = strtolower($question);
        foreach ($profanities as $word) {
            if (stripos($lowerQuestion, $word) !== false) {
                return response()->json([
                    'success' => true,
                    'data' => ['answer' => 'Hello, Viu Fam! Let\'s keep our conversation respectful and friendly. How can I help you with the survey? 😊'],
                    'conversation_id' => $request->input('conversation_id') ?? 'chat-' . time()
                ]);
            }
        }

        $conversationId = $request->input('conversation_id') ?? 
                         'chat-' . $request->ip() . '-' . time() . '-' . Str::random(8);

        try {
            $answer = $chatService->chat(
                $question,
                $conversationId
            );

            return response()->json([
                'success' => true,
                'data' => ['answer' => $answer],
                'conversation_id' => $conversationId
            ], 200);

        } catch (\Exception $e) {
            Log::error('Chatbot service failed', [
                'conversation_id' => $conversationId,
                'question' => $request->input('question'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Chat service temporarily unavailable',
                'conversation_id' => $conversationId
            ], 500);
        }
    }
}
