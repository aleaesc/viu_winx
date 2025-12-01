<?php

namespace App\Services;

use Illuminate\Support\Str;

class ContentModerationService
{
    protected array $patterns;
    protected string $refusalEn;
    protected string $refusalTl;

    public function __construct(array $patterns, string $refusalEn, string $refusalTl)
    {
        $this->patterns = $patterns;
        $this->refusalEn = $refusalEn;
        $this->refusalTl = $refusalTl;
    }

    public function check(string $input): ?array
    {
        $lower = Str::lower($input);
        foreach ($this->patterns as $frag) {
            if (Str::contains($lower, Str::lower($frag))) {
                return [
                    'allowed' => false,
                    'reason' => $frag,
                    'en' => $this->refusalEn,
                    'tl' => $this->refusalTl,
                ];
            }
        }
        return ['allowed' => true];
    }
}
