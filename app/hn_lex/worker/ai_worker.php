<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);

require_once $root . '/hn_core/inc/bootstrap.php';
require_once $root . '/hn_core/ai.php';

use HnCore\Database\DatabaseManager;

/* ==========================================================
   INIT DB
========================================================== */

$pdo = DatabaseManager::get('lex');

echo "Connected to DB: ";
echo $pdo->query("SELECT DATABASE()")->fetchColumn() . "\n";

/* ==========================================================
   FETCH JOB
========================================================== */

$stmt = $pdo->prepare("
    SELECT *
    FROM lex_ai_jobs
    WHERE status = 'pending'
    ORDER BY id ASC
    LIMIT 1
");
$stmt->execute();

$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    echo "No pending jobs.\n";
    exit;
}

/* ==========================================================
   LOCK JOB
========================================================== */

$pdo->prepare("
    UPDATE lex_ai_jobs
    SET status = 'processing',
        attempts = attempts + 1,
        started_at = NOW()
    WHERE id = ?
")->execute([$job['id']]);

echo "Processing job ID {$job['id']}...\n";

/* ==========================================================
   PROCESS
========================================================== */

try {

    /* ======================================================
       HENT ORD
    ====================================================== */

    $word = null;

    if (!empty($job['word'])) {
        $word = trim($job['word']);
    }
    elseif (!empty($job['missing_id'])) {

        $stmt = $pdo->prepare("
            SELECT word
            FROM lex_missing_log
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$job['missing_id']]);

        $word = $stmt->fetchColumn();
    }

    if (!$word) {
        throw new RuntimeException("No word found for job");
    }

    echo "Generating AI for: {$word}\n";

    /* ======================================================
       AI GENERERING
    ====================================================== */

    $aiData = ai_generate_lex_entry($word);

    if (!$aiData || !is_array($aiData)) {
        throw new RuntimeException("AI returned invalid data");
    }

    /* ======================================================
       VALIDER MINIMUM
    ====================================================== */

    if (empty($aiData['lemma']) || empty($aiData['word_class'])) {
        throw new RuntimeException("AI returned incomplete data");
    }

    /* ======================================================
       INSERT STAGING (REN KONTRAKT)
    ====================================================== */

    $stmt = $pdo->prepare("
        INSERT INTO lex_entries_staging
        (lemma, word_class, payload_json, status, created_at)
        VALUES (?, ?, ?, 'pending', NOW())
    ");

    $stmt->execute([
        $aiData['lemma'],
        $aiData['word_class'],
        json_encode($aiData, JSON_UNESCAPED_UNICODE)
    ]);

    $stagingId = (int)$pdo->lastInsertId();

    /* ======================================================
       UPDATE JOB
    ====================================================== */

    $pdo->prepare("
        UPDATE lex_ai_jobs
        SET entry_id = ?,
            status = 'done',
            finished_at = NOW()
        WHERE id = ?
    ")->execute([$stagingId, $job['id']]);

    echo "DONE: {$word} → staging ID {$stagingId}\n";

} catch (Throwable $e) {

    $pdo->prepare("
        UPDATE lex_ai_jobs
        SET status = 'failed',
            error_message = ?,
            finished_at = NOW()
        WHERE id = ?
    ")->execute([
        $e->getMessage(),
        $job['id']
    ]);

    echo "FAILED: " . $e->getMessage() . "\n";
}