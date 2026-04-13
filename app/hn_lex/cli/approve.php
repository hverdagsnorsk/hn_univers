<?php
require_once __DIR__ . '/../../hn_core/inc/bootstrap.php';

use HnCore\Database\DatabaseManager;
use HnLex\Service\LexStorageService;

$pdo = DatabaseManager::get('lex');
$storage = new LexStorageService($pdo);

$rows = $pdo->query("
    SELECT * FROM lex_entries_staging
    WHERE status = 'pending'
    LIMIT 50
")->fetchAll();

foreach ($rows as $row) {
    try {
        $id = $storage->approveStagingRow($row);
        echo "✔ Approved {$row['lemma']} → $id\n";
    } catch (Throwable $e) {
        echo "✖ Failed {$row['lemma']} → " . $e->getMessage() . "\n";
    }
}

echo "DONE\n";