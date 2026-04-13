<?php
declare(strict_types=1);

namespace HnLex\Service;

use HnCore\Ai\Contracts\LexAiInterface;
use HnLex\Contracts\LexContract;

final class AIWordClassService
{
    public function __construct(
        private LexAiInterface $ai
    ) {}

    public function classify(string $word, string $sentence = ''): array
    {
        $result = $this->ai->generateEntry($word);

        $wordClass = strtolower(trim($result['word_class'] ?? 'unknown'));

        $wordClass = LexContract::normalizeWordClass($wordClass);

        return [
            'word_class' => $wordClass,
            'raw'        => $result
        ];
    }
}