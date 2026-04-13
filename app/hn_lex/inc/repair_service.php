<?php
declare(strict_types=1);

require_once __DIR__ . '/contracts/LexContract.php';
require_once __DIR__ . '/lex_save.php';
require_once __DIR__ . '/../../hn_core/ai.php';

function repairSingleEntry(PDO $pdo, int $entryId): bool
{
    // 1️⃣ Hent lemma + ordklasse
    $stmt = $pdo->prepare("
        SELECT e.lemma, wc.code AS word_class
        FROM lex_entries e
        JOIN lex_word_classes wc ON wc.id = e.word_class_id
        WHERE e.id = ?
    ");
    $stmt->execute([$entryId]);
    $entry = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$entry) {
        return false;
    }

    $lemma = $entry['lemma'];
    $wordClass = $entry['word_class'];

    // 2️⃣ Kall AI (samme som CLI bruker)
    $aiData = aiExplainWord($lemma, 'no'); 
    if (!$aiData) {
        return false;
    }

    // 3️⃣ Konverter AI → LexContract
    $contract = LexContract::fromAI($aiData);
    if (!$contract) {
        return false;
    }

    // 4️⃣ Lagre i DB (oppdaterer eksisterende entry)
    $contract->entry_id = $entryId;

    try {
        saveLexEntry($pdo, $contract);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}
