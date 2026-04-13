<?php
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/contracts/LexContract.php';
require_once __DIR__ . '/../inc/lex_save.php';

$in = json_decode(file_get_contents('php://input'), true);

$id        = (int)$in['id'];
$ordklasse = $in['ordklasse'];

$row = $pdo->prepare("
  SELECT * FROM lex_review_queue WHERE id = ?
");
$row->execute([$id]);
$item = $row->fetch(PDO::FETCH_ASSOC);

if (!$item) exit;

$ai = json_decode($item['ai_payload'], true);
$ai['ordklasse'] = $ordklasse;

$entry = LexContract::fromAI($ai);
saveLexEntry($pdo, $entry);

$pdo->prepare("
  UPDATE lex_review_queue
  SET status='processed', processed_at=NOW()
  WHERE id=?
")->execute([$id]);
