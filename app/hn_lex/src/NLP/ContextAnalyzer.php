<?php
declare(strict_types=1);

namespace HnLex\NLP;

final class ContextAnalyzer
{
    public function extract(string $sentence, string $word): array
    {
        $tokens = preg_split('/\s+/u', mb_strtolower($sentence));

        $index = array_search($word, $tokens, true);

        return [
            'prev' => $tokens[$index - 1] ?? '',
            'next' => $tokens[$index + 1] ?? '',
            'sentence' => $sentence
        ];
    }
}