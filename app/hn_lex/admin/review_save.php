<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'].'/hn_core/inc/bootstrap.php';

use HnCore\Database\DatabaseManager;
use HnLex\Service\LexStorageService;

$pdo = DatabaseManager::get('lex');
$storage = new LexStorageService($pdo);

$id        = (int)($_POST['id'] ?? 0);
$action    = $_POST['action'] ?? '';
$wordClass = $_POST['word_class'] ?? '';
$payload   = $_POST['payload'] ?? '';

if ($id <= 0) {
    exit('Invalid ID');
}

/* ================= REJECT (ALLTID FØRST) ================= */

if ($action === 'reject') {

    $pdo->prepare("
        UPDATE lex_review_queue
        SET status = 'processed'
        WHERE id = ?
    ")->execute([$id]);

    header('Location: /hn_lex/admin/review_queue.php');
    exit;
}

/* ================= APPROVE ================= */

if ($action === 'approve') {

    if (!$wordClass) {
        exit('Missing word_class');
    }

    $data = json_decode($payload, true);

    if (!is_array($data)) {
        exit('Invalid JSON');
    }

    try {

        $storage->storeStructured($data, $wordClass);

        $pdo->prepare("
            UPDATE lex_review_queue
            SET status = 'processed'
            WHERE id = ?
        ")->execute([$id]);

    } catch (Throwable $e) {
        exit("ERROR: " . $e->getMessage());
    }

    header('Location: /hn_lex/admin/review_queue.php');
    exit;
}

/* ================= FALLBACK ================= */

exit('Invalid action');