<?php
declare(strict_types=1);

namespace HnLex\Service;

use PDO;
use Throwable;
use RuntimeException;
use HnLex\Contracts\LexContract;
use HnLex\Repository\ExplanationRepository;
use HnLex\Service\LexFormsGenerator;

final class LexStorageService
{
    public function __construct(
        private PDO $pdo
    ) {}

    /* ==========================================================
       STAGING
    ========================================================== */

    public function storeToStaging(
        array $data,
        string $wordClass,
        ?string $subclass = null
    ): int {

        $lemma = mb_strtolower(trim((string)($data['lemma'] ?? '')));

        if ($lemma === '') {
            throw new RuntimeException('Missing lemma');
        }

        $wordClass = LexContract::normalizeWordClass($wordClass);
        $subclass  = LexContract::normalizeSubclass($wordClass, $subclass);

        $data['word_class'] = $wordClass;
        $data['subclass']   = $subclass;

        $payload = json_encode($data, JSON_UNESCAPED_UNICODE);

        if ($payload === false) {
            throw new RuntimeException('Invalid JSON payload');
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO lex_entries_staging
            (lemma, word_class, payload_json, status, source, created_at, updated_at)
            VALUES (?, ?, ?, 'pending', 'ai', NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                word_class   = VALUES(word_class),
                payload_json = VALUES(payload_json),
                status       = 'pending',
                source       = 'ai',
                updated_at   = NOW()
        ");

        $stmt->execute([$lemma, $wordClass, $payload]);

        return (int)$this->pdo->lastInsertId();
    }

    /* ==========================================================
       APPROVAL → PRODUCTION
    ========================================================== */

    public function approveStagingRow(array $row): int
    {
        $data = json_decode((string)($row['payload_json'] ?? ''), true);

        if (!is_array($data) || empty($data['lemma'])) {
            throw new RuntimeException('Invalid staging payload');
        }

        $data['word_class'] = LexContract::normalizeWordClass(
            (string)($data['word_class'] ?? $row['word_class'] ?? 'unknown')
        );

        $data['subclass'] = LexContract::normalizeSubclass(
            $data['word_class'],
            $data['subclass'] ?? null
        );

        if (empty($data['senses']) || !is_array($data['senses'])) {
            $data['senses'] = [[
                'definition' => $data['lemma'],
                'forms'      => [$data['lemma']]
            ]];
        }

        $entryId = $this->storeStructured($data, 'approved');

        $stmt = $this->pdo->prepare("
            UPDATE lex_entries_staging
            SET status = 'approved',
                approved_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([(int)$row['id']]);

        return $entryId;
    }

    /* ==========================================================
       PRODUCTION
    ========================================================== */

    public function storeStructured(array $data, string $status = 'pending'): int
    {
        $status = in_array($status, ['pending', 'approved', 'rejected'], true)
            ? $status
            : 'pending';

        $this->pdo->beginTransaction();

        try {
            /* ==========================================================
               NORMALIZE ENTRY
            ========================================================== */

            $lemma = mb_strtolower(trim((string)($data['lemma'] ?? '')));
            if ($lemma === '') {
                throw new RuntimeException('Missing lemma');
            }

            $data['word_class'] = LexContract::normalizeWordClass(
                (string)($data['word_class'] ?? 'unknown')
            );

            if ($data['word_class'] === 'unknown') {
                throw new RuntimeException('Invalid entry word_class');
            }

            $data['subclass'] = LexContract::normalizeSubclass(
                $data['word_class'],
                $data['subclass'] ?? null
            );

            if (empty($data['senses']) || !is_array($data['senses'])) {
                throw new RuntimeException('Missing senses');
            }

            /* ==========================================================
               UPSERT ENTRY
            ========================================================== */

            $entryId = $this->upsertEntry($data, $status);

            /* ==========================================================
               IDEMPOTENT RESET
            ========================================================== */

            $this->pdo->prepare("DELETE FROM lex_senses WHERE entry_id = ?")
                ->execute([$entryId]);

            $this->pdo->prepare("DELETE FROM lex_forms WHERE entry_id = ?")
                ->execute([$entryId]);

            $explanationRepo = new ExplanationRepository($this->pdo);
            $generated = [];

            /* ==========================================================
               INSERT SENSES
            ========================================================== */

            foreach ($data['senses'] as $i => $sense) {

                $senseWordClassCode = LexContract::normalizeWordClass(
                    (string)($sense['word_class'] ?? $data['word_class'])
                );

                if ($senseWordClassCode === 'unknown') {
                    throw new RuntimeException("Invalid sense word_class at index $i");
                }

                $senseWordClassId = $this->resolveWordClassId($senseWordClassCode);

                $definition = trim((string)($sense['definition'] ?? ''));
                if ($definition === '') {
                    throw new RuntimeException("Missing definition at sense $i");
                }

                $senseOrder = $i + 1;

                $senseId = $this->insertSense(
                    $entryId,
                    [
                        'definition' => $definition,
                        'domain'     => $sense['domain'] ?? null,
                        'embedding'  => $sense['embedding'] ?? null
                    ],
                    $senseOrder,
                    $senseWordClassId,
                    $status
                );

                /* ======================================================
                   FORMS FRA INPUT
                ====================================================== */

                $this->insertForms(
                    $entryId,
                    $senseId,
                    $sense['forms'] ?? [],
                    $senseWordClassCode
                );

                /* ======================================================
                   GENERER FORMER (1 GANG PER KLASSE)
                ====================================================== */

                $key = $entryId . '|' . $senseWordClassCode;

                if (!isset($generated[$key])) {

                    $formsGenerator = new LexFormsGenerator($this->pdo);

                    $formsGenerator->generateForEntry(
                        $entryId,
                        $lemma,
                        $senseWordClassCode,
                        $data['grammar'] ?? []
                    );

                    $generated[$key] = true;
                }

                /* ======================================================
                   EXPLANATIONS
                ====================================================== */

                if (!empty($sense['explanations']) && is_array($sense['explanations'])) {
                    foreach ($sense['explanations'] as $exp) {

                        $explanationRepo->upsertForSense(
                            $senseId,
                            $entryId,
                            (string)($data['language'] ?? 'nb'),
                            (string)($exp['level'] ?? 'A1'),
                            (string)($exp['explanation'] ?? ''),
                            $exp['example'] ?? null
                        );
                    }
                }
            }

            $this->pdo->commit();

            return $entryId;

        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /* ==========================================================
       ENTRY
    ========================================================== */

    private function upsertEntry(array $data, string $status): int
    {
        $lemma    = mb_strtolower(trim((string)($data['lemma'] ?? '')));
        $language = trim((string)($data['language'] ?? 'nb'));
        $source   = trim((string)($data['source'] ?? 'ai'));

        if ($lemma === '') {
            throw new RuntimeException('Missing lemma');
        }

        $wordClassCode = $data['word_class'];
        $wordClassId   = $this->resolveWordClassId($wordClassCode);

        $subclass = $data['subclass'] ?? null;

        $stmt = $this->pdo->prepare("
            SELECT id FROM lex_entries
            WHERE lemma = ? AND language = ?
            LIMIT 1
        ");
        $stmt->execute([$lemma, $language]);

        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $entryId = (int)$existing['id'];

            $update = $this->pdo->prepare("
                UPDATE lex_entries
                SET word_class_id = ?,
                    word_class    = ?,
                    subclass      = ?,
                    source        = ?,
                    status        = ?,
                    updated_at    = NOW()
                WHERE id = ?
            ");

            $update->execute([
                $wordClassId,
                $wordClassCode,
                $subclass,
                $source,
                $status,
                $entryId
            ]);

            return $entryId;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO lex_entries (
                lemma,
                language,
                word_class_id,
                word_class,
                subclass,
                source,
                status,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $stmt->execute([
            $lemma,
            $language,
            $wordClassId,
            $wordClassCode,
            $subclass,
            $source,
            $status
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /* ==========================================================
       WORD CLASS
    ========================================================== */

    private function resolveWordClassId(string $code): int
    {
        $stmt = $this->pdo->prepare("
            SELECT id FROM lex_word_classes WHERE code = ? LIMIT 1
        ");
        $stmt->execute([$code]);

        $id = $stmt->fetchColumn();

        if ($id) return (int)$id;

        $stmt = $this->pdo->query("
            SELECT id FROM lex_word_classes WHERE code = 'unknown' LIMIT 1
        ");

        return (int)$stmt->fetchColumn();
    }

    /* ==========================================================
       SENSES + FORMS (uendret)
    ========================================================== */

    private function insertSense(
        int $entryId,
        array $sense,
        int $order,
        int $wordClassId,
        string $status
    ): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO lex_senses (
                entry_id,
                word_class_id,
                sense_order,
                definition,
                domain,
                embedding,
                status,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $entryId,
            $wordClassId,
            $order,
            $sense['definition'] ?? null,
            $sense['domain'] ?? null,
            isset($sense['embedding'])
                ? json_encode($sense['embedding'], JSON_UNESCAPED_UNICODE)
                : null,
            $status
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    private function insertForms(
        int $entryId,
        int $senseId,
        array $forms,
        string $wordClassCode
    ): void {
        if (empty($forms)) return;

        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO lex_forms (
                entry_id,
                sense_id,
                form,
                word_class,
                field
            ) VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($forms as $form) {
            $form = trim(mb_strtolower((string)$form));

            if ($form === '') continue;

            $stmt->execute([
                $entryId,
                $senseId,
                $form,
                $wordClassCode,
                'inferred'
            ]);
        }
    }
}