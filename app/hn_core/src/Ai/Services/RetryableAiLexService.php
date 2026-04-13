<?php
declare(strict_types=1);

namespace HnCore\Ai\Services;

use HnCore\AI\Contracts\AiLexInterface;
use HnCore\AI\Exceptions\RetryableException;

final class RetryableAiLexService implements AiLexInterface
{
    private AiLexInterface $inner;
    private int $maxRetries;

    public function __construct(AiLexInterface $inner, int $maxRetries = 3)
    {
        $this->inner = $inner;
        $this->maxRetries = $maxRetries;
    }

    private function retry(callable $fn)
    {
        $attempt = 0;

        while (true) {
            try {
                return $fn();
            } catch (RetryableException $e) {
                if ($attempt >= $this->maxRetries) {
                    throw $e;
                }

                sleep(2 ** $attempt);
                $attempt++;
            }
        }
    }

    public function generateLexEntry(string $word): array
    {
        return $this->retry(fn()=> $this->inner->generateLexEntry($word));
    }

    public function generateLexEntryForced(
        string $word,
        string $ordklasse,
        array $grammarSchema
    ): array {
        return $this->retry(fn()=> $this->inner
            ->generateLexEntryForced($word,$ordklasse,$grammarSchema));
    }

    public function generateBatch(
        array $words,
        callable $schemaResolver
    ): array {
        return $this->retry(fn()=> $this->inner
            ->generateBatch($words,$schemaResolver));
    }

    public function expandSenses(string $lemma, string $ordklasse): array
    {
        return $this->retry(fn()=> $this->inner
            ->expandSenses($lemma,$ordklasse));
    }
}