<?php
declare(strict_types=1);

require_once __DIR__ . '/../engine/bootstrap.php';

/* --------------------------------------------------
   INPUT
-------------------------------------------------- */
$bookKey = $_GET['book'] ?? '';
$textKey = $_GET['text'] ?? '';

if ($bookKey === '' || $textKey === '') {
    http_response_code(400);
    exit('Mangler book eller text');
}

/* --------------------------------------------------
   HENT TEKST
-------------------------------------------------- */
$stmt = $pdo->prepare("
    SELECT id, title
    FROM texts
    WHERE book_key = :book
      AND text_key = :text
      AND active = 1
    LIMIT 1
");
$stmt->execute([
    'book' => $bookKey,
    'text' => $textKey
]);

$text = $stmt->fetch();

if (!$text) {
    http_response_code(404);
    exit('Tekst ikke funnet');
}

/* --------------------------------------------------
   HENT GODKJENTE OPPGAVER
-------------------------------------------------- */
$stmt = $pdo->prepare("
    SELECT id, task_type, payload_json
    FROM tasks
    WHERE text_id = :text_id
      AND status = 'approved'
    ORDER BY id ASC
");
$stmt->execute([
    'text_id' => $text['id']
]);

$tasks = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>Oppgaver – <?= htmlspecialchars($text['title']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Felles oppgavestil -->
<link rel="stylesheet" href="/hn_books/tasks/css/tasks.css">

<!-- JS for interaktive typer -->
<script src="/hn_books/tasks/js/drag_sort.js" defer></script>
</head>
<body>

<main class="tasks-wrapper">

    <h1><?= htmlspecialchars($text['title']) ?></h1>

    <?php if (!$tasks): ?>
        <p>Ingen oppgaver publisert for denne teksten.</p>
    <?php endif; ?>

    <form method="post" action="submit.php">

        <?php foreach ($tasks as $task): ?>

            <?php
            $payload = json_decode($task['payload_json'], true);

            if (!is_array($payload)) {
                echo '<p>Ugyldig oppgavedata.</p>';
                continue;
            }

            $rendererFile = __DIR__ . '/../engine/renderers/' . $task['task_type'] . '.php';

            if (!file_exists($rendererFile)) {
                echo '<p>Mangler renderer for oppgavetype: <strong>'
                     . htmlspecialchars($task['task_type'])
                     . '</strong></p>';
                continue;
            }

            // Renderer-kontrakt:
            // $task (array)
            // $payload (array)
            require $rendererFile;
            ?>

        <?php endforeach; ?>

        <?php if ($tasks): ?>
            <div class="task-submit">
                <button type="submit">Send svar</button>
            </div>
        <?php endif; ?>

    </form>

</main>

</body>
</html>
