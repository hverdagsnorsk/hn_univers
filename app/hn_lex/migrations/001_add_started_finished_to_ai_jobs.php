<?php

return function (PDO $pdo) {

    // Check started_at
    $stmt = $pdo->query("
        SHOW COLUMNS FROM lex_ai_jobs LIKE 'started_at'
    ");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("
            ALTER TABLE lex_ai_jobs
            ADD COLUMN started_at DATETIME NULL AFTER attempts
        ");
    }

    // Check finished_at
    $stmt = $pdo->query("
        SHOW COLUMNS FROM lex_ai_jobs LIKE 'finished_at'
    ");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("
            ALTER TABLE lex_ai_jobs
            ADD COLUMN finished_at DATETIME NULL AFTER started_at
        ");
    }
};
