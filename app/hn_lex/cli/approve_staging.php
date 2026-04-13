<?php
declare(strict_types=1);

require_once __DIR__ . '/../../hn_core/inc/bootstrap.php';

use HnCore\Database\DatabaseManager;
use HnLex\Service\LexStorageService;

$pdo = DatabaseManager::get('lex');
$storage = new LexStorageService($pdo);

/* ==========================================================
   FETCH PENDING
========================================================== */

$rows = $pdo->query("
    SELECT * FROM lex_entries_staging
    WHERE status = 'pending'
    ORDER BY created_at ASC
    LIMIT 50
")->fetchAll();

/* ==========================================================
   PROCESS
========================================================== */

foreach ($rows as $row) {

    $data = json_decode($row['payload_json'], true);

    if (!$data) {
        echo "Invalid JSON for ID {$row['id']}\n";
        continue;
    }

    try {

        $entryId = $storage->storeToProduction($data);

        $stmt = $pdo->prepare("
            UPDATE lex_entries_staging
            SET status = 'approved',
                approved_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$row['id']]);

        echo "✔ Approved: {$data['lemma']} (ID: $entryId)\n";

    } catch (Throwable $e) {

        echo "✖ Failed: {$row['lemma']} → " . $e->getMessage() . "\n";

        $pdo->prepare("
            UPDATE lex_entries_staging
            SET status = 'failed'
            WHERE id = ?
        ")->execute([$row['id']]);
    }
}

echo "DONE\n";