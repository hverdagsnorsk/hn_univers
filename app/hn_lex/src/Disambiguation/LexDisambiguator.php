<?php
declare(strict_types=1);

namespace HnLex\Disambiguation;

final class LexDisambiguator
{
    public function __construct(
        private ScoringStrategy $scoring
    ) {}

    public function choose(array $candidates, array $context): ?array
    {
        if (empty($candidates)) {
            return null;
        }

        $scored = [];

        foreach ($candidates as $candidate) {

            $candidate['_score'] = $this->scoring->score($candidate, $context);

            $scored[] = $candidate;
        }

        usort($scored, fn($a, $b) => $b['_score'] <=> $a['_score']);

        return $scored[0] ?? null;
    }
}