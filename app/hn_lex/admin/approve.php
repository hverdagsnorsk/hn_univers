<?php
declare(strict_types=1);

require_once __DIR__ . '/../../hn_core/inc/bootstrap.php';

use HnLex\Service\ApprovalService;

$pdo = db('lex');

/* ==========================================================
   INPUT
========================================================== */

$id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($id <= 0) {
    http_response_code(400);
    exit('Invalid ID');
}

/* ==========================================================
   FETCH STAGING (sikkerhet)
========================================================== */

$stmt = $pdo->prepare("
    SELECT status FROM lex_entries_staging WHERE id = ?
");
$stmt->execute([$id]);

$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    exit('Entry not found');
}

/* ==========================================================
   REJECT
========================================================== */

if ($action === 'reject') {

    $pdo->prepare("
        UPDATE lex_entries_staging
        SET status = 'rejected'
        WHERE id = ?
    ")->execute([$id]);

    header("Location: approval.php?filter=pending");
    exit;
}

/* ==========================================================
   APPROVE
========================================================== */

if ($action === 'approve') {

    try {

        $service = new ApprovalService($pdo);

        $entryId = $service->approve($id);

        header("Location: approval.php?approved=" . $entryId);
        exit;

    } catch (\Throwable $e) {

        // 🔥 VIS FEIL DIREKTE (IKKE REDIRECT)

        echo "<pre style='background:#111;color:#0f0;padding:15px;font-size:14px;'>";
        echo "APPROVE ERROR:\n\n";
        echo $e->getMessage();
        echo "\n\nTRACE:\n";
        echo $e->getTraceAsString();
        echo "</pre>";

        exit;
    }
}
/* ==========================================================
   FALLBACK
========================================================== */

http_response_code(400);
exit('Invalid action');