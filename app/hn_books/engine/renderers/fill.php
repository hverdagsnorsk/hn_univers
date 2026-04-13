<?php
/**
 * Renderer: Fill-in (ett svar)
 *
 * Forventet payload:
 * {
 *   "prompt": "Sett inn riktig ord",
 *   "sentence": "Jeg ___ på jobb.",
 *   "answer": "jobber",
 *   "feedback": {
 *     "correct": "Riktig!",
 *     "incorrect": "Prøv igjen."
 *   }
 * }
 *
 * Variabler tilgjengelig:
 * - $task
 * - $payload
 */

$taskId = (int)($task['id'] ?? 0);
$sentence = $payload['sentence'] ?? '';
$answer   = $payload['answer']   ?? null;

if ($taskId <= 0 || $sentence === '' || $answer === null) {
    echo '<p class="task-error">Ugyldig utfyllingsoppgave.</p>';
    return;
}
?>

<div
    class="task-fill"
    data-answer="<?= htmlspecialchars((string)$answer) ?>"
>

    <div class="task-fill-sentence">
        <?= htmlspecialchars($sentence) ?>
    </div>

    <input
        type="text"
        class="task-input"
        autocomplete="off"
        spellcheck="false"
        placeholder="Skriv svaret her"
    >

    <div class="task-actions" style="margin-top:10px">
        <button
            type="button"
            data-action="check"
        >
            Sjekk
        </button>
    </div>

</div>
