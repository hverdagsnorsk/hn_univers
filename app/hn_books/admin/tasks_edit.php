<?php
declare(strict_types=1);

require_once __DIR__ . '/../_config/config.php';

/* --------------------------------------------------
   Session + tilgang
-------------------------------------------------- */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin'])) {
    http_response_code(403);
    exit('Ingen tilgang');
}

/* --------------------------------------------------
   Hent oppgave
-------------------------------------------------- */
$taskId = (int)($_GET['id'] ?? 0);
if ($taskId <= 0) {
    exit('Ugyldig oppgave-ID');
}

$stmt = $pdo->prepare("
    SELECT t.*, x.title AS text_title
    FROM tasks t
    JOIN texts x ON x.id = t.text_id
    WHERE t.id = :id
    LIMIT 1
");
$stmt->execute(['id' => $taskId]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    exit('Oppgave ikke funnet');
}

$type = (string)$task['task_type'];
$payload = json_decode($task['payload_json'], true) ?? [];

/* --------------------------------------------------
   LAGRE
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    switch ($type) {

        case 'mcq':
            $choices = array_values(array_filter($_POST['choices'] ?? []));
            $correctIndex = isset($_POST['correct_index'])
                ? (int)$_POST['correct_index']
                : -1;

            $payload = [
                'prompt' => trim($_POST['prompt'] ?? ''),
                'choices' => $choices,
                'correct_index' => $correctIndex,
                'feedback' => [
                    'correct' => trim($_POST['fb_correct'] ?? ''),
                    'incorrect' => trim($_POST['fb_incorrect'] ?? '')
                ]
            ];
            break;

        case 'fill':
            $payload = [
                'prompt' => trim($_POST['prompt'] ?? ''),
                'sentence' => trim($_POST['sentence'] ?? ''),
                'answer' => trim($_POST['answer'] ?? ''),
                'feedback' => [
                    'correct' => trim($_POST['fb_correct'] ?? ''),
                    'incorrect' => trim($_POST['fb_incorrect'] ?? '')
                ]
            ];
            break;

        case 'short':
            $payload = [
                'prompt' => trim($_POST['prompt'] ?? '')
            ];
            break;

        case 'order':
            $items = array_values(array_filter($_POST['items'] ?? []));
            $payload = [
                'prompt' => trim($_POST['prompt'] ?? ''),
                'items' => $items
            ];
            break;

        case 'match':
            $pairs = [];
            foreach ($_POST['left'] ?? [] as $i => $left) {
                $right = $_POST['right'][$i] ?? '';
                if (trim($left) !== '' && trim($right) !== '') {
                    $pairs[] = [
                        'left' => trim($left),
                        'right' => trim($right)
                    ];
                }
            }
            $payload = [
                'prompt' => trim($_POST['prompt'] ?? ''),
                'pairs' => $pairs
            ];
            break;
    }

    $stmt = $pdo->prepare("
        UPDATE tasks
        SET payload_json = :json
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([
        'json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        'id'   => $taskId
    ]);

    header('Location: tasks.php');
    exit;
}
?>
