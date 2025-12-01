<?php

namespace App\Services;

use Illuminate\Support\Str;

class PromptBuilder
{
    public function detectLanguage(string $text): string
    {
        $tagalogTokens = '(ang|ng|sa|ako|ikaw|kayo|po|opo|hindi|oo|salamat|paano|saan|kailan|magkano|gusto|nasaan|bakit|kailangan|meron|wala|ito|iyan|doon|naman|ayos|tulong|kung|habang|dahil|para|lahat|mga)';
        $hits = preg_match_all('/\b'.$tagalogTokens.'\b/u', Str::lower($text));
        $words = max(str_word_count($text),1);
        $ratio = $hits / $words;
        return $ratio >= 0.06 ? 'tl' : 'en';
    }

    public function normalize(string $text, array $map): string
    {
        $out = Str::lower($text);
        foreach ($map as $needle => $replacement) {
            $out = Str::of($out)->replace($needle, $replacement)->__toString();
        }
        return trim($out);
    }

    public function buildBasePrompt(string $systemPrompt, string $intent, string $langCode, array $contextSnippets, string $original): string
    {
        $langDirective = $langCode === 'tl' ? 'Primary language: Tagalog.' : 'Primary language: English.';
        $contextBlock = empty($contextSnippets) ? 'None.' : implode("\n", $contextSnippets);
        return $systemPrompt
            ."\nIntent: $intent\n".$langDirective
            ."\nRelevant facts (if any):\n".$contextBlock
            ."\nUser question: ".$original;
    }
}
