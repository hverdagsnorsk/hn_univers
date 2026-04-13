<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/hn_core/inc/bootstrap.php';

use HnCore\Database\DatabaseManager;

header('Content-Type: application/json');

$pdo = DatabaseManager::get('lex');

$stmt = $pdo->query("
    SELECT id, lemma, created_at
    FROM lex_entries
    WHERE status = 'pending'
    ORDER BY id DESC
    LIMIT 50
");

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));