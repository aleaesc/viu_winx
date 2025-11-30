<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class ChatbotController extends Controller
{
    public function ask(Request $request)
    {
        $request->validate(['question' => ['required','string','max:500']]);
        $q = Str::lower($request->input('question'));
        // Attempt external provider if configured (Google Gemini), otherwise fallback to KB
        $chatCfg = config('viu.chatbot');
        if (!empty($chatCfg['api_key']) && $chatCfg['provider'] === 'google') {
            try {
                $model = $chatCfg['model'];
                $base = rtrim($chatCfg['endpoint'], '/');
                $url = $base.'/'.$model.':generateContent?key='.$chatCfg['api_key'];
                $payload = [
                    'contents' => [[
                        'role' => 'user',
                        'parts' => [[ 'text' => 'You are the VIU Virtual Assistant. Answer based only on trusted VIU information. If unsure, say you do not have that info.' ]]
                    ],[
                        'role' => 'user',
                        'parts' => [[ 'text' => $request->input('question') ]]
                    ]],
                    'generationConfig' => [
                        'temperature' => 0.2,
                    ],
                ];
                $resp = Http::withHeaders([ 'Content-Type' => 'application/json' ])->post($url, $payload);
                if ($resp->successful()) {
                    $json = $resp->json();
                    $candidates = $json['candidates'] ?? [];
                    $answer = $candidates[0]['content']['parts'][0]['text'] ?? null;
                    if ($answer) return response()->json(['answer' => $answer]);
                }
            } catch (\Throwable $e) {
                // ignore and fallback
            }
        }
        $kb = config('viu.kb');
        foreach ($kb as $item) {
            if (Str::contains($q, Str::lower($item['q']))) {
                return response()->json(['answer' => $item['a']]);
            }
        }
        return response()->json(['answer' => 'Sorry, I do not have that info yet.']);
    }
}
