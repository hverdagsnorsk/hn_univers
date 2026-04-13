<?php
declare(strict_types=1);

namespace HnLex\Service;

use HnLex\Contracts\LexContract;
use RuntimeException;

final class StructuredDataValidator
{
    /**
     * Validerer strukturert AI-data før lagring i production.
     *
     * @throws RuntimeException
     */
    public function validate(array $data): void
    {
        $this->validateLemma($data);
        $this->validateEntryWordClass($data);
        $this->validateSenses($data);
    }

    /* ==========================================================
       ENTRY-LEVEL VALIDATION
    ========================================================== */

    private function validateLemma(array $data): void
    {
        $lemma = trim((string)($data['lemma'] ?? ''));

        if ($lemma === '') {
            throw new RuntimeException('Validation failed: lemma is empty');
        }

        if (mb_strlen($lemma) > 255) {
            throw new RuntimeException('Validation failed: lemma too long');
        }
    }

    private function validateEntryWordClass(array $data): void
    {
        if (!isset($data['word_class'])) {
            throw new RuntimeException('Validation failed: missing entry word_class');
        }

        $normalized = LexContract::normalizeWordClass((string)$data['word_class']);

        if ($normalized === 'unknown') {
            throw new RuntimeException('Validation failed: invalid entry word_class');
        }
    }

    /* ==========================================================
       SENSE VALIDATION
    ========================================================== */

    private function validateSenses(array $data): void
    {
        if (!isset($data['senses']) || !is_array($data['senses'])) {
            throw new RuntimeException('Validation failed: senses missing or invalid');
        }

        if (count($data['senses']) === 0) {
            throw new RuntimeException('Validation failed: senses cannot be empty');
        }

        foreach ($data['senses'] as $index => $sense) {
            $this->validateSingleSense($sense, $index);
        }
    }

    private function validateSingleSense(array $sense, int $index): void
    {
        $this->validateSenseWordClass($sense, $index);
        $this->validateDefinition($sense, $index);
        $this->validateExample($sense, $index);

        // Optional (hvis du bruker subclass aktivt)
        if (isset($sense['subclass'])) {
            $this->validateSubclass($sense['subclass'], $index);
        }
    }

    private function validateSenseWordClass(array $sense, int $index): void
    {
        if (!isset($sense['word_class'])) {
            throw new RuntimeException("Validation failed: sense[$index] missing word_class");
        }

        $normalized = LexContract::normalizeWordClass((string)$sense['word_class']);

        if ($normalized === 'unknown') {
            throw new RuntimeException("Validation failed: sense[$index] invalid word_class");
        }
    }

    private function validateDefinition(array $sense, int $index): void
    {
        $definition = trim((string)($sense['definition'] ?? ''));

        if ($definition === '') {
            throw new RuntimeException("Validation failed: sense[$index] missing definition");
        }

        if (mb_strlen($definition) < 2) {
            throw new RuntimeException("Validation failed: sense[$index] definition too short");
        }
    }

    private function validateExample(array $sense, int $index): void
    {
        if (!isset($sense['example'])) {
            return; // optional field
        }

        $example = trim((string)$sense['example']);

        if ($example === '') {
            throw new RuntimeException("Validation failed: sense[$index] example is empty");
        }
    }

    private function validateSubclass(string $subclass, int $index): void
    {
        $subclass = trim($subclass);

        if ($subclass === '') {
            throw new RuntimeException("Validation failed: sense[$index] subclass is empty");
        }

        if (mb_strlen($subclass) > 100) {
            throw new RuntimeException("Validation failed: sense[$index] subclass too long");
        }
    }
}