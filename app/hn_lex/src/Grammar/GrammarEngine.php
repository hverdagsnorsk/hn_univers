<?php
declare(strict_types=1);

namespace HnLex\Grammar;

use HnLex\Contracts\LexContract;

final class GrammarEngine
{
    public function __construct(
        private AdjectiveInflector $adj,
        private VerbInflector $verb,
        private NounInflector $noun
    ) {}

    public function generate(string $lemma, string $wordClass): array
    {
        $wordClass = LexContract::normalizeWordClass($wordClass);

        return match ($wordClass) {
            'adjective' => $this->adj->inflect($lemma),
            'verb'      => $this->verb->inflect($lemma),
            'noun'      => $this->noun->inflect($lemma),
            default     => []
        };
    }
}