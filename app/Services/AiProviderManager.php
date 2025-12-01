<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AiProviderManager
{
    protected array $providers;
    protected string $systemPrompt;

    public function __construct(array $providers, string $systemPrompt)
    {
        $this->providers = $providers;
        $this->systemPrompt = $systemPrompt;
    }

    public function firstAvailable(string $basePrompt, string $ip, float $temperature = 0.25, int $maxTokens = 512): ?array
    {
        foreach ($this->providers as $prov) {
            $name = $prov['name'] ?? 'unknown';
            $failKey = 'provider_fail_'.$name;
            // Circuit breaker: skip if failures exceed threshold
            if (Cache::get($failKey, 0) >= 5) continue;
            if (empty($prov['key'])) continue;
            try {
                $answer = null;
                if (($prov['type'] ?? '') === 'openai') {
                    $answer = $this->callOpenAI($prov, $basePrompt, $temperature, $maxTokens);
                } elseif (($prov['type'] ?? '') === 'gemini') {
                    $answer = $this->callGemini($prov, $basePrompt, $temperature, $maxTokens);
                }
                if ($answer) {
                    // reset failure count on success
                    Cache::forget($failKey);
                    return ['provider' => $name, 'answer' => $answer];
                }
            } catch (\Throwable $e) {
                Cache::increment($failKey);
                Cache::put($failKey, Cache::get($failKey, 0), 300); // 5 min TTL
            }
        }
        return null;
    }

    // Custom variant allowing override of system prompt and user content separation
    public function firstAvailableCustom(string $systemPrompt, string $userContent, string $ip, float $temperature = 0.8, int $maxTokens = 512, float $topP = 0.95): ?array
    {
        foreach ($this->providers as $prov) {
            $name = $prov['name'] ?? 'unknown';
            $failKey = 'provider_fail_'.$name;
            if (Cache::get($failKey, 0) >= 5) continue;
            if (empty($prov['key'])) continue;
            try {
                $answer = null;
                if (($prov['type'] ?? '') === 'openai') {
                    $answer = $this->callOpenAICustom($prov, $systemPrompt, $userContent, $temperature, $maxTokens);
                } elseif (($prov['type'] ?? '') === 'gemini') {
                    $answer = $this->callGeminiCustom($prov, $systemPrompt, $userContent, $temperature, $maxTokens, $topP);
                }
                if ($answer) {
                    Cache::forget($failKey);
                    return ['provider' => $name, 'answer' => $answer];
                }
            } catch (\Throwable $e) {
                Cache::increment($failKey);
                Cache::put($failKey, Cache::get($failKey, 0), 300);
            }
        }
        return null;
    }

    private function callOpenAI(array $cfg, string $basePrompt, float $temperature, int $maxTokens): ?string
    {
        $endpoint = rtrim($cfg['endpoint'] ?? 'https://api.openai.com/v1/chat/completions','/');
        $resp = Http::withToken($cfg['key'])
            ->acceptJson()->timeout($cfg['timeout'] ?? 15)
            ->retry(3, 200, function($exception, $request){
                return $exception instanceof \Illuminate\Http\Client\ConnectionException || ($exception->response && $exception->response->serverError());
            })
            ->post($endpoint, [
                'model' => $cfg['model'] ?? 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system','content' => $this->systemPrompt],
                    ['role' => 'user','content' => $basePrompt],
                ],
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ]);
        if (!$resp->successful()) return null;
        $json = $resp->json();
        return $json['choices'][0]['message']['content'] ?? null;
    }

    private function callOpenAICustom(array $cfg, string $systemPrompt, string $userContent, float $temperature, int $maxTokens): ?string
    {
        $endpoint = rtrim($cfg['endpoint'] ?? 'https://api.openai.com/v1/chat/completions','/');
        $resp = Http::withToken($cfg['key'])
            ->acceptJson()->timeout($cfg['timeout'] ?? 15)
            ->retry(3, 200, function($exception, $request){
                return $exception instanceof \Illuminate\Http\Client\ConnectionException || ($exception->response && $exception->response->serverError());
            })
            ->post($endpoint, [
                'model' => $cfg['model'] ?? 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system','content' => $systemPrompt],
                    ['role' => 'user','content' => $userContent],
                ],
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ]);
        if (!$resp->successful()) return null;
        $json = $resp->json();
        return $json['choices'][0]['message']['content'] ?? null;
    }

    private function callGemini(array $cfg, string $basePrompt, float $temperature, int $maxTokens): ?string
    {
        $model = $cfg['model'] ?? 'gemini-1.5-flash';
        $endpoint = rtrim($cfg['endpoint'] ?? 'https://generativelanguage.googleapis.com/v1beta/models','/')."/{$model}:generateContent";
        $resp = Http::withToken($cfg['key'])
            ->acceptJson()->timeout($cfg['timeout'] ?? 15)
            ->retry(3, 200, function($exception, $request){
                return $exception instanceof \Illuminate\Http\Client\ConnectionException || ($exception->response && $exception->response->serverError());
            })
            ->post($endpoint, [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [[ 'text' => $basePrompt ]]
                ]],
                'generationConfig' => [
                    'temperature' => $temperature,
                    'topP' => 0.95,
                    'maxOutputTokens' => $maxTokens,
                ],
            ]);
        if (!$resp->successful()) return null;
        $json = $resp->json();
        return $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }

    private function callGeminiCustom(array $cfg, string $systemPrompt, string $userContent, float $temperature, int $maxTokens, float $topP = 0.95): ?string
    {
        $model = $cfg['model'] ?? 'gemini-1.5-flash';
        $endpoint = rtrim($cfg['endpoint'] ?? 'https://generativelanguage.googleapis.com/v1beta/models','/')."/{$model}:generateContent";
        $combined = $systemPrompt."\n\nUser Message:\n".$userContent;
        $resp = Http::withToken($cfg['key'])
            ->acceptJson()->timeout($cfg['timeout'] ?? 15)
            ->retry(3, 200, function($exception, $request){
                return $exception instanceof \Illuminate\Http\Client\ConnectionException || ($exception->response && $exception->response->serverError());
            })
            ->post($endpoint, [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [[ 'text' => $combined ]]
                ]],
                'generationConfig' => [
                    'temperature' => $temperature,
                    'topP' => $topP,
                    'maxOutputTokens' => $maxTokens,
                ],
            ]);
        if (!$resp->successful()) return null;
        $json = $resp->json();
        return $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }
}
