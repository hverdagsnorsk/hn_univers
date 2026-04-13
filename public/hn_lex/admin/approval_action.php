<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../app/hn_core/inc/bootstrap.php';

use HnCore\Database\DatabaseManager;
use HnLex\Service\LexStorageService;

$pdo = DatabaseManager::get('lex');
$storage = new LexStorageService($pdo);

$id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM lex_entries_staging WHERE id = ?");
$stmt->execute([$id]);

$row = $stmt->fetch();

if (!$row) {
    exit('Not found');
}

/* =========================
   REJECT
========================= */

if ($action === 'reject') {

    $pdo->prepare("
        UPDATE lex_entries_staging
        SET status = 'rejected', updated_at = NOW()
        WHERE id = ?
    ")->execute([$id]);

    header("Location: approval.php");
    exit;
}

/* =========================
   LOAD ORIGINAL AI DATA
========================= */

$data = json_decode($row['payload_json'], true);

if (!is_array($data)) {
    exit('Invalid payload');
}

/* =========================
   UPDATE ENTRY-LEVEL
========================= */

$data['lemma'] = $_POST['lemma'];
$data['word_class'] = $_POST['word_class'];
$data['subclass'] = $_POST['subclass'] ?? null;

/* =========================
   UPDATE SENSES (IKKE ERSTATT!)
========================= */

foreach ($_POST['senses'] ?? [] as $i => $input) {

    if (!isset($data['senses'][$i])) continue;

    $data['senses'][$i]['word_class'] = $input['word_class'];
    $data['senses'][$i]['subclass'] = $input['subclass'] ?? null;
    $data['senses'][$i]['definition'] = $input['definition'];

    $data['senses'][$i]['explanations'][0]['example'] = $input['example'];
}

/* =========================
   APPROVE (STORE FULL STRUCTURE)
========================= */

$storage->storeStructured($data, 'approved');

/* =========================
   MARK STAGING
========================= */

$pdo->prepare("
    UPDATE lex_entries_staging
    SET status = 'approved', approved_at = NOW()
    WHERE id = ?
")->execute([$id]);

header("Location: approval.php");
exit;