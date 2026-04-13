<?php
declare(strict_types=1);

require_once __DIR__ . '/../../engine/bootstrap.php';

$attempt = (int)($_GET["attempt"] ?? 0);

$stmt = $pdo->prepare("
SELECT
COUNT(*) total,
SUM(is_correct) correct
FROM responses
WHERE attempt_id = ?
");

$stmt->execute([$attempt]);

$r = $stmt->fetch(PDO::FETCH_ASSOC);

$total   = $r["total"] ?? 0;
$correct = $r["correct"] ?? 0;

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="../css/task.css">
</head>
<body>

<div class="result">

<h2>Resultat</h2>

<p>Riktige svar: <?=$correct?> / <?=$total?></p>

</div>

</body>
</html>