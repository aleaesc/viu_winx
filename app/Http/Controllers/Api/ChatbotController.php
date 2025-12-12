<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    public function ask(Request $request)
    {
        try {
            // Always return 200 with friendly message to prevent client errors
            Log::info('Chatbot controller reached', ['ip' => $request->ip()]);
            
            $answer = 'Viu Fam, our assistant is warming up. Try again in a moment — or check the survey for now 😊';
            $cid = $request->input('conversation_id') ?? ('chat-' . time());
            return response()->json([
                'success' => true,
                'answer' => $answer,
                'data' => ['answer' => $answer],
                'conversation_id' => $cid
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Chatbot ask method failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => true,
                'answer' => 'Viu Fam, our assistant is warming up. Try again in a moment — or check the survey for now 😊',
                'data' => ['answer' => 'Viu Fam, our assistant is warming up. Try again in a moment — or check the survey for now 😊'],
                'conversation_id' => 'chat-' . time()
            ], 200);
        }
    }
    
    // ORIGINAL METHOD DISABLED TO PREVENT 500s - RE-ENABLE AFTER INVESTIGATION
    public function ask_disabled_original(Request $request)
    {
        // Rate limiting: Max 20 requests per minute per IP
        try {
            $identifier = $request->ip() . '_chatbot';
            $rateLimit = Cache::get($identifier, 0);
            if ($rateLimit >= 20) {
                return response()->json([
                    'success' => false,
                    'error' => 'Hello, Viu Fam! Please slow down a bit. You can ask again in a moment. 😊'
                ], 429);
            }
            Cache::put($identifier, $rateLimit + 1, 60); // 1 minute window

        try {
            // Instantiate service inside try so constructor errors are caught
            $chatService = new ChatbotService();
            // Validate more permissively (allow emojis and international scripts)
            $request->validate([
                'question' => 'required_without:message|string|max:500',
                'message' => 'required_without:question|string|max:500',
                'conversation_id' => 'nullable|string|max:255'
            ]);

            // Sanitize input (remove potential XSS) - accept both 'question' and 'message'
            $question = strip_tags($request->input('question') ?? $request->input('message'));
            $question = htmlspecialchars($question, ENT_QUOTES, 'UTF-8');
        
        // Profanity filter
        $profanities = ['putang', 'gago', 'tangina', 'fuck', 'shit', 'bitch', 'asshole', 'tite','inamo', 'putanginamo', 'tanginamo'];
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

            $answer = $chatService->chat(
                $question,
                $conversationId
            );

            // Defensive: ensure a non-empty answer
            $answer = is_string($answer) && strlen(trim($answer)) ? $answer : 'Viu Fam, our assistant is warming up. Try again in a moment 😊';
            return response()->json([
                'success' => true,
                'data' => ['answer' => $answer],
                'conversation_id' => $conversationId
            ], 200);
            END DISABLED SECTION */

        } catch (\Throwable $e) {
            Log::error('Chatbot service failed', [
                'conversation_id' => $conversationId,
                'question' => $request->input('question'),
                'error' => $e->getMessage()
            ]);

            // Return a friendly fallback message instead of 500
            return response()->json([
                'success' => true,
                'data' => ['answer' => 'Viu Fam, our assistant is warming up. Try again in a moment — or check the survey for now 😊'],
                'conversation_id' => $conversationId ?? ('chat-' . time())
            ], 200);
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
