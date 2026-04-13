<?php
declare(strict_types=1);

namespace HnCore\Ai\Contracts;

interface LexAiInterface
{
    public function generateEntry(string $word): array;

    public function generateBatch(array $words): array;
}