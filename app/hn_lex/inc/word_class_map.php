<?php
declare(strict_types=1);

/*
|--------------------------------------------------
| Ordklasse → word_class_id
|--------------------------------------------------
| Brukes KUN i admin/batch.
| LexContract skal ALDRI kjenne DB-id-er.
*/

function getWordClassId(PDO $pdo, string $ordklasse): int
{
    static $cache = [];

    $ordklasse = mb_strtolower(trim($ordklasse));

    if (isset($cache[$ordklasse])) {
        return $cache[$ordklasse];
    }

    $stmt = $pdo->prepare("
        SELECT id
        FROM lex_word_classes
        WHERE code = ?
        LIMIT 1
    ");
    $stmt->execute([$ordklasse]);

    $id = $stmt->fetchColumn();

    if (!$id) {
        throw new RuntimeException("Ukjent ordklasse i DB: {$ordklasse}");
    }

    return $cache[$ordklasse] = (int)$id;
}
