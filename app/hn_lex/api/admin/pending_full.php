<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'].'/hn_core/inc/bootstrap.php';

use HnCore\Database\DatabaseManager;

$pdo = DatabaseManager::get('lex');

$stmt = $pdo->query("
    SELECT e.id, e.lemma, wc.code as word_class
    FROM lex_entries e
    JOIN lex_word_classes wc ON wc.id = e.word_class_id
    WHERE e.status = 'pending'
    ORDER BY e.id DESC
    LIMIT 50
");

$entries = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $entryId = (int)$row['id'];

    /* FORMS */
    $forms = $pdo->prepare("
        SELECT form FROM lex_forms WHERE entry_id = ?
    ");
    $forms->execute([$entryId]);

    /* EXPLANATION */
    $exp = $pdo->prepare("
        SELECT explanation, example
        FROM lex_explanations
        WHERE entry_id = ?
        LIMIT 1
    ");
    $exp->execute([$entryId]);

    /* GRAMMAR */
    $grammar = $pdo->prepare("
        SELECT `key`, `value`
        FROM lex_grammar
        WHERE entry_id = ?
    ");
    $grammar->execute([$entryId]);

    $entries[] = [
        'id' => $entryId,
        'lemma' => $row['lemma'],
        'word_class' => $row['word_class'],
        'forms' => $forms->fetchAll(PDO::FETCH_COLUMN),
        'explanation' => $exp->fetch(PDO::FETCH_ASSOC),
        'grammar' => $grammar->fetchAll(PDO::FETCH_ASSOC)
    ];
}

header('Content-Type: application/json');
echo json_encode($entries);