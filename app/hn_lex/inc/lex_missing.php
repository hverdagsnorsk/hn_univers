<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| lex_missing.php – LOGGING AV MANGLENDE ORD
|--------------------------------------------------------------------------
| - Logger ord som ikke finnes i lex_entries
| - Brukes av lookup / view / api
| - INGEN AI
| - INGEN LexContract
|--------------------------------------------------------------------------
*/

function logMissingWord(
    PDO $pdo,
    string $rawWord,
    string $lemma,
    string $language = 'no'
): void {

    $stmt = $pdo->prepare("
        INSERT INTO lex_missing_log
          (word, language, clicks)
        VALUES
          (:word, :lang, 1)
        ON DUPLICATE KEY UPDATE
          clicks = clicks + 1,
          last_seen = CURRENT_TIMESTAMP
    ");

    $stmt->execute([
        ':word' => mb_strtolower(trim($lemma)),
        ':lang' => $language,
    ]);
}
