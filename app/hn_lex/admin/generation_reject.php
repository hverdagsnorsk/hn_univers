<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'].'/hn_core/inc/bootstrap.php';

use HnCore\Database\DatabaseManager;

$pdo = DatabaseManager::get('lex');

$id = (int)($_POST['id'] ?? 0);

if ($id) {
    $stmt = $pdo->prepare("
        UPDATE lex_generation_queue
        SET status = 'rejected', updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$id]);
}

header('Location: /hn_lex/admin/generation_review.php');
exit;