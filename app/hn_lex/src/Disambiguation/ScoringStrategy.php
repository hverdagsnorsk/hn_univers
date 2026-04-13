<?php
declare(strict_types=1);

namespace HnLex\Disambiguation;

use HnLex\Service\EmbeddingService;

final class ScoringStrategy
{
    public function __construct(
        private ?EmbeddingService $embedding = null
    ) {}

    public function score(array $candidate, array $context): float
    {
        $score = 0.0;

        $lemma = strtolower($candidate['lemma'] ?? '');
        $word  = strtolower($context['word'] ?? '');

        /* EXACT MATCH */
        if ($lemma === $word) {
            $score += 5.0;
        }

        /* CONTEXT MATCH */
        $contextText = strtolower(
            ($context['sentence'] ?? '') . ' ' .
            ($context['prev'] ?? '') . ' ' .
            ($context['next'] ?? '')
        );

        if ($contextText && str_contains($contextText, $lemma)) {
            $score += 2.0;
        }

        /* EMBEDDING */
        if ($this->embedding && !empty($candidate['embedding'])) {

            $text = trim($contextText);

            if ($text !== '') {
                $contextVec = $this->embedding->embed($text);

                $sim = EmbeddingService::cosine(
                    $contextVec,
                    $candidate['embedding']
                );

                $score += $sim * 10;
            }
        }

        return $score;
    }
}