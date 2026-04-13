<?php
declare(strict_types=1);

echo "=== HN AI BATCH WORKER START ===\n";
$startTime = microtime(true);

/* ==========================================================
   LOCK SYSTEM
========================================================== */

$lockFilePath = sys_get_temp_dir() . '/hn_ai_worker.lock';
$lockFile = fopen($lockFilePath, 'c');

if (!$lockFile) {
    exit("Cannot create lock file.\n");
}

if (!flock($lockFile, LOCK_EX | LOCK_NB)) {
    exit("Worker already running.\n");
}

register_shutdown_function(function () use ($lockFile) {
    flock($lockFile, LOCK_UN);
    fclose($lockFile);
});

/* ==========================================================
   BOOTSTRAP
========================================================== */

$root = dirname(__DIR__, 2);

require_once $root . '/hn_core/inc/bootstrap.php';
require_once $root . '/hn_core/ai.php';

use HnCore\Database\DatabaseManager;

/* ==========================================================
   DB INIT
========================================================== */

$pdo = DatabaseManager::get('lex');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Connected to DB: ";
echo $pdo->query("SELECT DATABASE()")->fetchColumn() . "\n";

/* ==========================================================
   CONFIG
========================================================== */

$limit = isset($argv[1]) ? max(1, (int)$argv[1]) : 10;
echo "Batch limit: {$limit}\n";

/* ==========================================================
   FETCH JOBS
========================================================== */

$stmt = $pdo->prepare("
    SELECT j.*, s.id AS staging_id, s.lemma
    FROM lex_ai_jobs j
    JOIN lex_entries_staging s ON s.id = j.entry_id
    WHERE j.status = 'pending'
    ORDER BY j.id ASC
    LIMIT :limit
");

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();

$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$jobs) {
    echo "No pending jobs.\n";
    exit;
}

echo "Jobs fetched: " . count($jobs) . "\n";

/* ==========================================================
   PROCESS JOBS
========================================================== */

foreach ($jobs as $job) {

    $jobId = (int)$job['id'];
    $stagingId = (int)$job['staging_id'];
    $word = $job['lemma'];

    echo "Processing job {$jobId} ({$word})...\n";

    try {

        /* LOCK JOB */

        $pdo->prepare("
            UPDATE lex_ai_jobs
            SET status = 'processing',
                attempts = attempts + 1,
                started_at = NOW()
            WHERE id = ?
        ")->execute([$jobId]);

        /* AI */

        echo "Generating AI for: {$word}\n";

        $aiData = ai_generate_lex_entry($word);

        if (!$aiData || !is_array($aiData)) {
            throw new RuntimeException("AI returned invalid data.");
        }

        /* VALIDATION (minimum) */

        if (empty($aiData['lemma']) || empty($aiData['ordklasse'])) {
            throw new RuntimeException("Missing lemma or word_class from AI.");
        }

        /* STORE FULL PAYLOAD */

        $json = json_encode($aiData, JSON_UNESCAPED_UNICODE);

        if (!$json) {
            throw new RuntimeException("JSON encode failed: " . json_last_error_msg());
        }

        $stmt = $pdo->prepare("
            UPDATE lex_entries_staging
            SET payload_json = :payload,
                status = 'pending'
            WHERE id = :id
        ");

        $stmt->execute([
            'payload' => $json,
            'id'      => $stagingId
        ]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException("No staging row updated.");
        }

        /* MARK DONE */

        $pdo->prepare("
            UPDATE lex_ai_jobs
            SET status = 'done',
                finished_at = NOW()
            WHERE id = ?
        ")->execute([$jobId]);

        echo "DONE: {$word}\n";

    } catch (Throwable $e) {

        echo "FAILED job {$jobId}: " . $e->getMessage() . "\n";

        $pdo->prepare("
            UPDATE lex_ai_jobs
            SET status = 'failed',
                error_message = ?,
                finished_at = NOW()
            WHERE id = ?
        ")->execute([
            mb_substr($e->getMessage(), 0, 1000),
            $jobId
        ]);
    }
}

$duration = round(microtime(true) - $startTime, 2);
echo "Runtime: {$duration}s\n";
echo "=== WORKER END ===\n";