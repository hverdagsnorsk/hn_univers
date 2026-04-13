<?php
declare(strict_types=1);

/*
|------------------------------------------------------------------
| RENDERER: OPEN TEXT
|------------------------------------------------------------------
| task_type: open_text
|
| payload:
| {
|   "prompt": "Skriv 3–5 setninger om ...",
|   "rows": 5,
|   "placeholder": "Skriv her ..."
| }
|------------------------------------------------------------------
*/

$prompt      = $payload['prompt']      ?? '';
$rows        = (int)($payload['rows'] ?? 4);
$placeholder = $payload['placeholder'] ?? '';

$taskId = (int)$task['id'];
?>

<section class="task task-open-text" data-task-id="<?= $taskId ?>">

    <?php if ($prompt): ?>
        <p class="task-prompt">
            <?= nl2br(htmlspecialchars($prompt)) ?>
        </p>
    <?php endif; ?>

    <textarea
        name="answers[<?= $taskId ?>]"
        rows="<?= max(2, $rows) ?>"
        placeholder="<?= htmlspecialchars($placeholder) ?>"
        class="task-textarea"
    ></textarea>

</section>
