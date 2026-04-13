<?php
declare(strict_types=1);

namespace HnLex\NLP;

final class WordClassClassifier
{
    public function classify(string $word, array $context = []): string
    {
        $scores = [
            'noun' => 0,
            'verb' => 0,
            'adjective' => 0,
            'adverb' => 0,
            'unknown' => 0
        ];

        $word = mb_strtolower($word);

        /* ---------- MORPHOLOGY ---------- */

        if (preg_match('/(isk|lig|ig)$/u', $word)) {
            $scores['adjective'] += 3;
        }

        if (preg_match('/(ende|vis)$/u', $word)) {
            $scores['adverb'] += 3;
        }

        if (preg_match('/(et|te|de)$/u', $word)) {
            $scores['verb'] += 2;
        }

        if (preg_match('/(het|else|dom)$/u', $word)) {
            $scores['noun'] += 2;
        }

        /* ---------- CONTEXT ---------- */

        $prev = mb_strtolower($context['prev'] ?? '');
        $next = mb_strtolower($context['next'] ?? '');

        if (in_array($prev, ['å','kan','skal','vil','må'], true)) {
            $scores['verb'] += 5;
        }

        if (in_array($prev, ['en','ei','et','den','det'], true)) {
            $scores['noun'] += 4;
        }

        if ($next && preg_match('/\b(er|te|de)\b/u', $next)) {
            $scores['verb'] += 2;
        }

        /* ---------- PICK WINNER ---------- */

        arsort($scores);

        $winner = array_key_first($scores);

        return $scores[$winner] > 0 ? $winner : 'unknown';
    }
}