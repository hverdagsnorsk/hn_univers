<?php

$baseDir = realpath(__DIR__ . '/..');

$logFile = __DIR__ . '/fix_db_usage.log';
file_put_contents($logFile, "=== DB MIGRATION START ===\n");

$rii = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir)
);

$changedFiles = 0;

foreach ($rii as $file) {
    if (!$file->isFile()) continue;

    $path = $file->getPathname();

    // Skip backup files
    if (str_contains($path, '.bak')) continue;

    // Only PHP files
    if (pathinfo($path, PATHINFO_EXTENSION) !== 'php') continue;

    $original = file_get_contents($path);
    $updated = $original;

    // --- RULES ---

    // 1. db('courses') → db('courses')
    $updated = preg_replace('/\db('courses')/', "db('courses')", $updated);

    // 2. db('lex') → db('lex')
    $updated = preg_replace('/\db('lex')/', "db('lex')", $updated);

    // 3. db()
    $updated = preg_replace("/\\\$GLOBALS\\['pdo'\\]/", "db()", $updated);

    // 4. 
    $updated = preg_replace('/global\s+\$pdo\s*;/', '', $updated);

    // 5. $pdo = db();
    $updated = preg_replace('/\$pdo\s*=\s*.*?;/', '$pdo = db();', $updated);

    // 6. Remaining $pdo usage → db()
    $updated = preg_replace('/\db()->/', 'db()->', $updated);

    // 7. Function signatures (basic)
    $updated = preg_replace('/function\s+(\w+)\s*\(\s*PDO\s+\$pdo\s*,?/', 'function $1(', $updated);

    // 8. new Service(db())
    $updated = preg_replace('/new\s+(\w+)\(\s*\$pdo\s*\)/', 'new $1(db())', $updated);

    // --- WRITE IF CHANGED ---
    if ($updated !== $original) {
        file_put_contents($path, $updated);
        file_put_contents($logFile, "UPDATED: $path\n", FILE_APPEND);
        $changedFiles++;
    }
}

file_put_contents($logFile, "=== DONE ($changedFiles files) ===\n", FILE_APPEND);

echo "Done. Updated $changedFiles files.\n";