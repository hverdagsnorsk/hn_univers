<?php
/**
 * Renderer: short (åpent svar)
 *
 * Forventet payload:
 * {
 *   "prompt": "Spørsmålstekst"
 * }
 *
 * Variabler tilgjengelig:
 * - $task
 * - $payload
 */

$taskId = (int)($task['id'] ?? 0);

if ($taskId <= 0) {
    echo '<p class="task-error">Ugyldig kortsvarsoppgave.</p>';
    return;
}
?>

<div class="task-short">

    <textarea
        class="task-input"
        rows="3"
        placeholder="Skriv svaret ditt her"
    ></textarea>

    <div class="task-actions" style="margin-top:10px">
        <button
            type="button"
            data-action="submit"
        >
            Send svar
        </button>
    </div>

</div>
