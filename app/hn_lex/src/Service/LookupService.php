<?php
declare(strict_types=1);

namespace HnLex\Service;

use PDO;
use Throwable;

use HnLex\Repository\LexRepository;
use HnLex\Repository\ExplanationRepository;
use HnLex\Repository\SenseRepository;
use HnLex\Repository\CandidatesRepository;

use HnLex\Disambiguation\LexDisambiguationLogger;

use HnLex\Grammar\GrammarEngine;
use HnLex\Grammar\AdjectiveInflector;
use HnLex\Grammar\VerbInflector;
use HnLex\Grammar\NounInflector;

use HnLex\NLP\DisambiguationEngine;
use HnLex\Contracts\LexContract;

final class LookupService
{
    private LexRepository $lexRepo;
    private ExplanationRepository $explanationRepo;
    private SenseRepository $senseRepo;
    private CandidatesRepository $candidateRepo;

    private LexDisambiguationLogger $logger;
    private GrammarEngine $grammarEngine;
    private DisambiguationEngine $disambiguationEngine;

    public function __construct(
        private PDO $pdo,
        private SenseGenerationService $senseGenerator,
        private ?EmbeddingService $embedding = null
    ) {
        $this->lexRepo         = new LexRepository($pdo);
        $this->explanationRepo = new ExplanationRepository($pdo);
        $this->senseRepo       = new SenseRepository($pdo);
        $this->candidateRepo   = new CandidatesRepository($pdo);

        $this->logger = new LexDisambiguationLogger($pdo);

        $this->grammarEngine = new GrammarEngine(
            new AdjectiveInflector(),
            new VerbInflector(),
            new NounInflector()
        );

        $this->disambiguationEngine = new DisambiguationEngine($embedding);
    }

    public function lookup(
        string $word,
        string $language,
        string $level,
        array $context
    ): array {

        $word = trim($word);

        if ($word === '') {
            return ['found' => false];
        }

        /* ======================================================
           PREVIEW OVERRIDE (NY)
        ====================================================== */

        if (!empty($context['override_payload'])) {
            return $this->lookupFromOverride($context, $level, $language);
        }

        /* ======================================================
           1. FIND CANDIDATES
        ====================================================== */

        $candidates = $this->candidateRepo->findCandidatesByForm($word, $language);

        $validCandidates = array_filter($candidates, fn($c) =>
            !empty($c['entry_id'])
        );

        /* ======================================================
           2. MISS → GENERATE (STAGING ONLY)
        ====================================================== */

        if (empty($validCandidates)) {

            try {
                $this->senseGenerator->ensureFullEntryExists(
                    $word,
                    $language,
                    $level,
                    $context
                );
            } catch (Throwable $e) {
                error_log('[LOOKUP PIPELINE ERROR] ' . $e->getMessage());
            }

            return ['found' => false];
        }

        /* ======================================================
           3. DISAMBIGUATION
        ====================================================== */

        $winner = $this->disambiguationEngine->choose($candidates, [
            'word' => $word,
            'prev' => $context['prev'] ?? '',
            'next' => $context['next'] ?? ''
        ]);

        if (!$winner || empty($winner['entry_id'])) {
            return ['found' => false];
        }

        $entryId = (int)$winner['entry_id'];

        /* ======================================================
           LOGGING
        ====================================================== */

        try {
            $this->logger->log(
                $word,
                $context['prev'] ?? '',
                $context['next'] ?? '',
                $entryId,
                0,
                0,
                count($candidates)
            );
        } catch (Throwable $e) {}

        /* ======================================================
           4. FETCH ENTRY
        ====================================================== */

        $entry = $this->lexRepo->findById($entryId, $language);

        if (!$entry) {
            return ['found' => false];
        }

        /* ======================================================
           NORMALIZE WORD CLASS
        ====================================================== */

        $wordClass = LexContract::normalizeWordClass(
            (string)(
                $entry['word_class']
                ?? $entry['word_class_code']
                ?? ''
            )
        );

        /* ======================================================
           5. FETCH SENSE
        ====================================================== */

        $sense = $this->senseRepo->findPrimaryByEntryId($entryId);

        /* ======================================================
           6. FETCH EXPLANATION
        ====================================================== */

        $explanation = $sense
            ? $this->explanationRepo->findBestForSense($sense['id'], $language, $level)
            : null;

        /* ======================================================
           7. GRAMMAR
        ====================================================== */

        $grammar = [];

        try {
            $grammar = $this->grammarEngine->generate(
                $entry['lemma'],
                $wordClass
            );
        } catch (Throwable $e) {}

        /* ======================================================
           RESPONSE
        ====================================================== */

        return [
            'found' => true,
            'lemma' => $entry['lemma'],
            'word_class' => $wordClass,
            'word_class_label' => $entry['word_class_name'] ?? '',
            'forklaring' => $explanation['explanation'] ?? '',
            'example' => $explanation['example'] ?? '',
            'grammar' => $grammar
        ];
    }

    /* ==========================================================
       PREVIEW ENGINE (NY)
    ========================================================== */

    private function lookupFromOverride(array $context, string $level, string $language): array
    {
        $data = $context['override_payload'];

        if (!is_array($data) || empty($data['senses'])) {
            return ['found' => false];
        }

        $wordClass = LexContract::normalizeWordClass(
            (string)($data['word_class'] ?? '')
        );

        $sense = $data['senses'][0];

        $explanation = $sense['explanations'][0] ?? null;

        $grammar = [];

        try {
            $grammar = $this->grammarEngine->generate(
                $data['lemma'],
                $wordClass
            );
        } catch (Throwable $e) {}

        return [
            'found' => true,
            'lemma' => $data['lemma'],
            'word_class' => $sense['word_class'] ?? $wordClass,
            'forklaring' => $explanation['explanation'] ?? $sense['definition'] ?? '',
            'example' => $explanation['example'] ?? '',
            'grammar' => $grammar,
            '_debug' => [
                'mode' => 'preview_override'
            ]
        ];
    }
}