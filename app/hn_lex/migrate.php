<?php
declare(strict_types=1);

require_once __DIR__ . '/../hn_core/inc/bootstrap.php';

if (!isset($pdo_lex)) {
    exit("No DB connection.\n");
}

$pdo_lex->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== RUNNING LEX MIGRATIONS ===\n";

$migrationDir = __DIR__ . '/migrations';
$files = glob($migrationDir . '/*.php');
sort($files);

/* Ensure table exists */
$pdo_lex->exec("
    CREATE TABLE IF NOT EXISTS lex_migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )
");

$executed = $pdo_lex->query("SELECT migration FROM lex_migrations")
                     ->fetchAll(PDO::FETCH_COLUMN);

foreach ($files as $file) {

    $name = basename($file);

    if (in_array($name, $executed, true)) {
        continue;
    }

    echo "Running: {$name}\n";

    $migration = require $file;

    $pdo_lex->beginTransaction();

    try {
        $migration($pdo_lex);

        $stmt = $pdo_lex->prepare("
            INSERT INTO lex_migrations (migration)
            VALUES (?)
        ");
        $stmt->execute([$name]);

        $pdo_lex->commit();

        echo "✔ Done\n";

    } catch (Throwable $e) {

        $pdo_lex->rollBack();
        echo "✖ Failed: " . $e->getMessage() . "\n";
        exit;
    }
}

echo "=== MIGRATIONS COMPLETE ===\n";
