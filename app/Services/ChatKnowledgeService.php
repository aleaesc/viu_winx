<?php

namespace App\Services;

use Illuminate\Support\Str;

class ChatKnowledgeService
{
    public function search(string $query, array $kb, int $limit = 5, bool $isTagalog = false): array
    {
        $q = Str::lower(trim($query));
        $results = [];
        foreach ($kb as $item) {
            $score = 0;
            foreach ($item['keywords'] ?? [] as $kw) {
                $kwl = Str::lower($kw);
                if (Str::contains($q, $kwl)) {
                    $score += 3;
                } elseif (strlen($kwl) > 4) {
                    $dist = levenshtein($kwl, $q);
                    if ($dist > 0 && $dist <= 2) $score += 1;
                }
            }
            if ($score > 0) {
                $results[] = [
                    'id' => $item['id'] ?? null,
                    'score' => $score,
                    'text' => $isTagalog ? ($item['tl'] ?? '') : ($item['en'] ?? '')
                ];
            }
        }
        usort($results, fn($a,$b) => $b['score'] <=> $a['score']);
        return array_slice($results, 0, $limit);
    }
}
