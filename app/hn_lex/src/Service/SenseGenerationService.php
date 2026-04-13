<?php
declare(strict_types=1);

namespace HnLex\Service;

use PDO;
use Throwable;
use HnLex\Contracts\LexContract;
use HnLex\Grammar\GrammarEngine;
use HnLex\Grammar\AdjectiveInflector;
use HnLex\Grammar\VerbInflector;
use HnLex\Grammar\NounInflector;
use HnCore\Ai\Services\OpenAiLexService;

final class SenseGenerationService
{
    private GrammarEngine $grammarEngine;
    private OpenAiLexService $ai;

    public function __construct(
        private PDO $pdo,
        private LexStorageService $storage,
        private ?EmbeddingService $embeddingService = null
    ) {
        $this->grammarEngine = new GrammarEngine(
            new AdjectiveInflector(),
            new VerbInflector(),
            new NounInflector()
        );

        $this->ai = new OpenAiLexService();
    }

    public function ensureFullEntryExists(
        string $word,
        string $language,
        string $level,
        array $context = []
    ): void {

        $word = $this->normalize($word);

        if ($word === '') {
            return;
        }

        /* ======================================================
           1. STOP hvis finnes fra før
        ====================================================== */

        if ($this->approvedEntryExists($word, $language)) {
            return;
        }

        if ($this->stagingRowExists($word)) {
            return;
        }

        /* ======================================================
           2. GENERATE
        ====================================================== */

      try {

    file_put_contents(
        '/home/7/h/hverdagsnorsk/www/logs/sense_debug.log',
        "BEFORE AI\n",
        FILE_APPEND
    );

    $aiData = $this->ai->generateEntry($word);

    file_put_contents(
        '/home/7/h/hverdagsnorsk/www/logs/sense_debug.log',
        "AFTER AI\n",
        FILE_APPEND
    );

} catch (Throwable $e) {

    file_put_contents(
        '/home/7/h/hverdagsnorsk/www/logs/sense_debug.log',
        "AI FAILED: " . $e->getMessage() . "\n",
        FILE_APPEND
    );

    error_log('[AI GENERATION FAIL] ' . $e->getMessage());
    return;
}
        /* ======================================================
           3. WORD CLASS
        ====================================================== */

        $wordClass = LexContract::normalizeWordClass(
            (string)($aiData['word_class'] ?? '')
        );

        if (!LexContract::isValidWordClass($wordClass)) {
            error_log('[INVALID WORD CLASS] ' . $word);
            return;
        }

        /* ======================================================
           4. SUBCLASS (NY)
        ====================================================== */

        $subclass = LexContract::normalizeSubclass(
            $wordClass,
            $aiData['subclass'] ?? null
        );

        /* ======================================================
           5. VALIDER SENSES (KRITISK)
        ====================================================== */

        if (empty($aiData['senses'])) {
            error_log('[NO SENSES] ' . $word);
            return;
        }

        /* ======================================================
           6. BUILD DATA
        ====================================================== */

        $data = [
            'lemma'     => $aiData['lemma'] ?? $word,
            'language'  => $aiData['language'] ?? $language,
            'level'     => strtoupper(trim($level)) ?: 'A1',
            'source'    => 'ai',
            'word_class'=> $wordClass,
            'subclass'  => $subclass,
            'senses'    => $aiData['senses']
        ];

        /* ======================================================
           7. EMBEDDING (optional)
        ====================================================== */

        if ($this->embeddingService && !empty($data['senses'][0])) {
            try {
                $text = $data['lemma'];
                $data['senses'][0]['embedding'] = $this->embeddingService->embed($text);
            } catch (Throwable $e) {
                error_log('[EMBED FAIL] ' . $e->getMessage());
            }
        }

        /* ======================================================
           8. STORE → STAGING
        ====================================================== */

        try {
            $this->storage->storeToStaging(
                $data,
                $wordClass,
                $subclass // 🔥 NY PARAMETER
            );
        } catch (Throwable $e) {
            error_log('[STAGING FAIL] ' . $e->getMessage());
        }
    }

    private function approvedEntryExists(string $word, string $language): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM lex_entries
            WHERE lemma = ? AND language = ? AND status = 'approved'
        ");
        $stmt->execute([$word, $language]);
        return (bool)$stmt->fetchColumn();
    }

    private function stagingRowExists(string $word): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM lex_entries_staging
            WHERE lemma = ?
              AND status IN ('pending','review')
        ");
        $stmt->execute([$word]);
        return (bool)$stmt->fetchColumn();
    }

    private function normalize(string $word): string
    {
        return preg_replace(
            '/^[^\p{L}\p{N}]+|[^\p{L}\p{N}]+$/u',
            '',
            mb_strtolower(trim($word))
        );
    }
}