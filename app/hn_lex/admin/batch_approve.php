<?php
declare(strict_types=1);

require_once __DIR__ . '/../../hn_core/inc/bootstrap.php';

use HnCore\Database\DatabaseManager;
use HnLex\Service\ApprovalService;

/* ==========================================================
   DB
========================================================== */

$pdo = DatabaseManager::get('lex');

/* ==========================================================
   INPUT
========================================================== */

$ids = $_POST['ids'] ?? [];
$action = $_POST['action'] ?? '';

if (empty($ids) || !is_array($ids)) {
    header("Location: approval.php?error=1");
    exit;
}

$service = new ApprovalService($pdo);

/* ==========================================================
   LOOP
========================================================== */

foreach ($ids as $id) {

    $id = (int)$id;
    if ($id <= 0) continue;

    if ($action === 'reject') {

        $pdo->prepare("
            UPDATE lex_entries_staging
            SET status = 'rejected'
            WHERE id = ?
        ")->execute([$id]);

        continue;
    }

    if ($action === 'approve') {

        try {
            $service->approve($id);
        } catch (\Throwable $e) {
            error_log('[BATCH APPROVE ERROR] ' . $e->getMessage());
        }
    }
}

/* ==========================================================
   REDIRECT
========================================================== */

header("Location: approval.php");
exit;