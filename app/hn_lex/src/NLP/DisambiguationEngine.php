<?php
declare(strict_types=1);

namespace HnLex\NLP;

use HnLex\Service\EmbeddingService;

final class DisambiguationEngine
{
    public function __construct(
        private ?EmbeddingService $embedding = null
    ) {}

    public function choose(array $candidates, array $context): ?array
    {
        if (empty($candidates)) {
            return null;
        }

        if (!$this->embedding) {
            return $candidates[0];
        }

        $contextText = trim(
            ($context['prev'] ?? '') . ' ' .
            ($context['word'] ?? '') . ' ' .
            ($context['next'] ?? '')
        );

        $contextVec = $this->embedding->embed($contextText);

        $best = null;
        $bestScore = -1;

        foreach ($candidates as $c) {

            $vec = $c['embedding'] ?? [];

            $score = EmbeddingService::cosine($contextVec, $vec);

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $c;
            }
        }

        return $best;
    }
}