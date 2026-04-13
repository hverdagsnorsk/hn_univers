<?php
declare(strict_types=1);

$root = dirname(__DIR__, 3);

require_once $root . '/hn_core/inc/bootstrap.php';
require_once $root . '/hn_core/auth/admin.php';
require_once $root . '/hn_lex/contracts/LexContract.php';

use HnLex\Contracts\LexContract;

header('Content-Type: application/json');

$data  = json_decode(file_get_contents('php://input'), true);

$id    = (int)($data['id'] ?? 0);
$type  = $data['type'] ?? 'entry';
$field = $data['field'] ?? '';
$value = trim((string)($data['value'] ?? ''));

if (!$id || !$field) {
    echo json_encode(['success'=>false]);
    exit;
}

/* ==========================================================
   ENTRY UPDATE
========================================================== */

if ($type === 'entry') {

    if ($field === 'lemma') {

        $stmt = $pdo_lex->prepare("
            UPDATE lex_entries
            SET lemma = ?
            WHERE id = ?
        ");
        $stmt->execute([$value, $id]);

        echo json_encode(['success'=>true]);
        exit;
    }

    if ($field === 'word_class') {

        $stmt = $pdo_lex->prepare("
            UPDATE lex_entries
            SET word_class_id = (
                SELECT id FROM lex_word_classes
                WHERE code = ?
                LIMIT 1
            )
            WHERE id = ?
        ");
        $stmt->execute([$value, $id]);

        echo json_encode(['success'=>true]);
        exit;
    }
}

/* ==========================================================
   GRAMMAR UPDATE
========================================================== */

if ($type === 'grammar') {

    $stmt = $pdo_lex->prepare("
        SELECT wc.code
        FROM lex_entries e
        JOIN lex_word_classes wc ON wc.id = e.word_class_id
        WHERE e.id = ?
    ");
    $stmt->execute([$id]);
    $ordklasse = $stmt->fetchColumn();

    if (!$ordklasse) {
        echo json_encode(['success'=>false]);
        exit;
    }

    $allowed = LexContract::getAllowedFields($ordklasse);

    if (!in_array($field, $allowed, true)) {
        echo json_encode(['success'=>false]);
        exit;
    }

    $table = LexContract::getGrammarTable($ordklasse);

    if (!$table) {
        echo json_encode(['success'=>false]);
        exit;
    }

    $stmt = $pdo_lex->prepare("
        UPDATE {$table}
        SET {$field} = ?
        WHERE entry_id = ?
    ");

    $stmt->execute([$value, $id]);

    echo json_encode(['success'=>true]);
    exit;
}

echo json_encode(['success'=>false]);