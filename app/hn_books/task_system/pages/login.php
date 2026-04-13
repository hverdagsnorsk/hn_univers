<?php
declare(strict_types=1);

require_once __DIR__ . '/../../engine/bootstrap.php';

$book = $_GET['book'] ?? '';
$text = $_GET['text'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $name  = trim($_POST['name'] ?? '');

    if ($email === '') {
        die("E-post mangler.");
    }

    $stmt = $pdo->prepare("
        SELECT id
        FROM texts
        WHERE book_key = ?
        AND text_key = ?
        LIMIT 1
    ");

    $stmt->execute([$book,$text]);

    $textId = $stmt->fetchColumn();

    if (!$textId) {
        die("Tekst ikke funnet.");
    }

    $stmt = $pdo->prepare("
        SELECT id
        FROM attempts
        WHERE participant_email = ?
        AND text_id = ?
        AND finished_at IS NULL
        ORDER BY started_at DESC
        LIMIT 1
    ");

    $stmt->execute([$email,$textId]);

    $attemptId = $stmt->fetchColumn();

    if (!$attemptId) {

        $stmt = $pdo->prepare("
            INSERT INTO attempts
            (text_id,participant_name,participant_email)
            VALUES (?,?,?)
        ");

        $stmt->execute([$textId,$name,$email]);

        $attemptId = $pdo->lastInsertId();
    }

    header("Location: player.php?attempt=".$attemptId);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="../css/task.css">
</head>
<body>

<div class="login-box">

<h2>Start oppgaver</h2>

<form method="post">

<input type="hidden" name="book" value="<?=htmlspecialchars($book)?>">
<input type="hidden" name="text" value="<?=htmlspecialchars($text)?>">

<label>Navn</label>
<input name="name">

<label>E-post</label>
<input name="email" required>

<button>Start</button>

</form>

</div>

</body>
</html>