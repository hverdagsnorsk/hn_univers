<?php
/**
 * Base renderer for alle oppgavetyper
 *
 * Forutsetter at følgende variabler er satt før inkludering:
 * - $task         (array)  → minst ['id', 'task_type']
 * - $payload      (array)  → normalisert payload
 * - $rendererFile (string) → full sti til type-spesifikk renderer
 */

if (!isset($task, $payload) || !is_array($payload)) {
    echo '<p class="task-error">Oppgave mangler data.</p>';
    return;
}

$taskId   = (int)($task['id'] ?? 0);
$type     = (string)($task['task_type'] ?? '');
$prompt   = $payload['prompt'] ?? '';
$feedback = is_array($payload['feedback'] ?? null)
    ? $payload['feedback']
    : [];

if ($taskId <= 0 || $type === '') {
    echo '<p class="task-error">Ugyldig oppgave.</p>';
    return;
}
?>

<section
    class="task task-<?= htmlspecialchars($type) ?>"
    data-task-id="<?= $taskId ?>"
    data-task-type="<?= htmlspecialchars($type) ?>"
>

    <?php if ($prompt !== ''): ?>
        <div class="task-prompt">
            <?= htmlspecialchars($prompt) ?>
        </div>
    <?php endif; ?>

    <div class="task-body">
        <?php
        /*
         * Her rendres den faktiske oppgaven.
         * Eksempler:
         * - mcq.php
         * - fill.php
         * - order.php
         * - match.php (drag-and-drop)
         */
        ?>

        <?php if (!empty($rendererFile) && is_readable($rendererFile)): ?>
            <?php include $rendererFile; ?>
        <?php else: ?>
            <p class="task-error">
                Ingen renderer funnet for oppgavetype
                <strong><?= htmlspecialchars($type) ?></strong>.
            </p>
        <?php endif; ?>
    </div>

    <div class="task-feedback" hidden>
        <div class="task-feedback-correct">
            <?= htmlspecialchars($feedback['correct'] ?? '') ?>
        </div>
        <div class="task-feedback-incorrect">
            <?= htmlspecialchars($feedback['incorrect'] ?? '') ?>
        </div>
    </div>

</section>
