<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| hn_admin/tasks_delete.php
| Sletter én oppgave (admin)
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/bootstrap.php';

/* --------------------------------------------------
   Hent ID (KUN POST)
-------------------------------------------------- */
$taskId = (int)($_POST['task_id'] ?? 0);

if ($taskId <= 0) {
    http_response_code(400);
    exit('Ugyldig oppgave-ID');
}

/* --------------------------------------------------
   Slett oppgave
-------------------------------------------------- */
$stmt = db()->prepare("
    DELETE FROM tasks
    WHERE id = :id
    LIMIT 1
");
$stmt->execute(['id' => $taskId]);

/* --------------------------------------------------
   Redirect tilbake (PRG)
-------------------------------------------------- */
header('Location: tasks.php');
exit;
