<?php
declare(strict_types=1);

/*
|------------------------------------------------------------------
| RENDERER: DRAG SORT
|------------------------------------------------------------------
| task_type: drag_sort
|
| payload:
| {
|   "prompt": "Sorter ordene i riktige kategorier",
|   "categories": ["Klær","Natur","Inne"],
|   "words": [
|     {"word":"votter","category":"Klær"},
|     {"word":"grantrær","category":"Natur"}
|   ]
| }
|------------------------------------------------------------------
*/

$prompt     = $payload['prompt']     ?? '';
$categories = $payload['categories'] ?? [];
$words      = $payload['words']      ?? [];

$taskId = (int)$task['id'];
?>

<section class="task task-drag-sort" data-task-id="<?= $taskId ?>">

    <?php if ($prompt): ?>
        <p class="task-prompt">
            <?= htmlspecialchars($prompt) ?>
        </p>
    <?php endif; ?>

    <div class="drag-sort">

        <!-- Word bank -->
        <div class="drag-bank drag-column" data-category="__bank__">
            <h4>Ord</h4>

            <?php foreach ($words as $i => $w): ?>
                <div
                    class="drag-word"
                    draggable="true"
                    data-word="<?= htmlspecialchars($w['word']) ?>"
                    data-answer="<?= htmlspecialchars($w['category']) ?>"
                    id="w<?= $taskId ?>_<?= $i ?>"
                >
                    <?= htmlspecialchars($w['word']) ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Categories -->
        <?php foreach ($categories as $cat): ?>
            <div
                class="drag-column"
                data-category="<?= htmlspecialchars($cat) ?>"
            >
                <h4><?= htmlspecialchars($cat) ?></h4>
            </div>
        <?php endforeach; ?>

    </div>

    <!-- hidden answer -->
    <input
        type="hidden"
        name="answers[<?= $taskId ?>]"
        value=""
    >

</section>
