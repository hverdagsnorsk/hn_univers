<?php
/**
 * Renderer: Multiple Choice (MCQ)
 *
 * Forventet payload:
 * {
 *   "prompt": "Instruksjon (vises i base)",
 *   "choices": ["A", "B", "C", "D"],
 *   "correct_index": 1,
 *   "feedback": {
 *     "correct": "Riktig!",
 *     "incorrect": "Feil svar."
 *   }
 * }
 *
 * Variabler tilgjengelig:
 * - $task    (array)
 * - $payload (array)
 */

$taskId        = (int)($task['id'] ?? 0);
$choices       = $payload['choices'] ?? [];
$correctIndex  = $payload['correct_index'] ?? null;

if (
    $taskId <= 0 ||
    !is_array($choices) ||
    count($choices) < 2 ||
    !is_int($correctIndex)
) {
    echo '<p class="task-error">Ugyldig flervalgsoppgave.</p>';
    return;
}
?>

<!--
  NB:
  data-correct-index brukes av tasks.js
-->
<div
    class="task-mcq-options"
    data-correct-index="<?= (int)$correctIndex ?>"
>

    <?php foreach ($choices as $i => $choice): ?>
        <label class="task-option">
            <input
                type="radio"
                name="task_<?= $taskId ?>"
                value="<?= (int)$i ?>"
            >
            <span><?= htmlspecialchars((string)$choice) ?></span>
        </label>
    <?php endforeach; ?>

</div>

<div class="task-actions" style="margin-top:10px">
    <button
        type="button"
        data-action="check"
    >
        Sjekk
    </button>
</div>
