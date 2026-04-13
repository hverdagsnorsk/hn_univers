<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| lex_save.php – AUTHORITATIVE SAVE (CONTRACT DRIVEN – FINAL)
|--------------------------------------------------------------------------
| - Expects normalized data from LexContract::fromAI()
| - Caller controls transaction
| - Strict validation via LexContract helpers
| - Requires at least one explanation
| - Saves lex_entries
| - Saves grammar dynamically
| - Saves explanations
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/contracts/LexContract.php';

use HnLex\Contracts\LexContract;

/* ==========================================================
   VALIDATION LAYER
========================================================== */

function validate_before_save(array $entry): void
{
    foreach (['lemma','language','ordklasse','explanations'] as $field) {
        if (!array_key_exists($field, $entry)) {
            throw new RuntimeException("Missing field: {$field}");
        }
    }

    if (!LexContract::isValidWordClass($entry['ordklasse'])) {
        throw new RuntimeException(
            "Invalid word class: {$entry['ordklasse']}"
        );
    }

    if (empty($entry['explanations']) || !is_array($entry['explanations'])) {
        throw new RuntimeException(
            "At least one explanation is required."
        );
    }

    $hasExplanation = false;

    foreach ($entry['explanations'] as $exp) {
        if (!empty($exp['forklaring'])) {
            $hasExplanation = true;
            break;
        }
    }

    if (!$hasExplanation) {
        throw new RuntimeException("No valid explanation found.");
    }

    /* ---------- Grammar validation ---------- */

    $required = LexContract::getRequiredFields($entry['ordklasse']);
    $grammar  = $entry['grammar'] ?? [];

    foreach ($required as $field) {
        if (!array_key_exists($field, $grammar)) {
            throw new RuntimeException(
                "Missing grammar field: {$field}"
            );
        }
    }
}

/* ==========================================================
   SAVE ENTRY
========================================================== */

function saveLexEntry(PDO $pdo, array $entry): int
{
    validate_before_save($entry);

    $ordklasse = $entry['ordklasse'];
    $grammar   = $entry['grammar'] ?? [];

    /* --------------------------------------------------
       Resolve word_class_id
    -------------------------------------------------- */

    $stmt = $pdo->prepare(
        "SELECT id FROM lex_word_classes WHERE code = ? LIMIT 1"
    );
    $stmt->execute([$ordklasse]);

    $wordClassId = $stmt->fetchColumn();

    if (!$wordClassId) {
        throw new RuntimeException(
            "Word class not found in DB: {$ordklasse}"
        );
    }

    /* --------------------------------------------------
       Insert / update lex_entries
    -------------------------------------------------- */

    $stmt = $pdo->prepare(
        "INSERT INTO lex_entries
         (lemma, language, word_class_id, source)
         VALUES (?, ?, ?, 'ai')
         ON DUPLICATE KEY UPDATE
            word_class_id = VALUES(word_class_id),
            updated_at    = CURRENT_TIMESTAMP"
    );

    $stmt->execute([
        $entry['lemma'],
        $entry['language'],
        $wordClassId
    ]);

    $entryId = (int)$pdo->lastInsertId();

    if ($entryId === 0) {
        $stmt = $pdo->prepare(
            "SELECT id FROM lex_entries
             WHERE lemma = ? AND language = ?
             LIMIT 1"
        );
        $stmt->execute([
            $entry['lemma'],
            $entry['language']
        ]);
        $entryId = (int)$stmt->fetchColumn();
    }

    if ($entryId === 0) {
        throw new RuntimeException('Could not resolve entry_id');
    }

    /* --------------------------------------------------
       Save grammar (strict contract-driven)
    -------------------------------------------------- */

    $table = LexContract::getGrammarTable($ordklasse);

    if ($table && !empty($grammar)) {

        $allowedFields = array_merge(
            LexContract::getRequiredFields($ordklasse),
            LexContract::getOptionalFields($ordklasse)
        );

        $filtered = [];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $grammar)) {
                $filtered[$field] = $grammar[$field];
            }
        }

        if (!empty($filtered)) {

            $columns      = implode(', ', array_keys($filtered));
            $placeholders = implode(', ', array_fill(0, count($filtered), '?'));

            $updates = [];
            foreach (array_keys($filtered) as $field) {
                $updates[] = "{$field} = VALUES({$field})";
            }

            $sql = "
                INSERT INTO {$table}
                (entry_id, {$columns})
                VALUES (?, {$placeholders})
                ON DUPLICATE KEY UPDATE
                " . implode(', ', $updates);

            $stmt = $pdo->prepare($sql);

            $stmt->execute(
                array_merge([$entryId], array_values($filtered))
            );
        }
    }

    /* --------------------------------------------------
       Save explanations
    -------------------------------------------------- */

    foreach ($entry['explanations'] as $level => $data) {

        if (empty($data['forklaring'])) {
            continue;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO lex_explanations
             (entry_id, level, language, explanation, example, source)
             VALUES (?, ?, ?, ?, ?, 'ai')
             ON DUPLICATE KEY UPDATE
                explanation = VALUES(explanation),
                example     = VALUES(example)"
        );

        $stmt->execute([
            $entryId,
            $level,
            $entry['language'],
            $data['forklaring'],
            $data['example'] ?? null
        ]);
    }

    return $entryId;
}
