<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../app/hn_core/inc/bootstrap.php';

use HnCore\Database\DatabaseManager;
use HnLex\Contracts\LexContract;

$pdo = DatabaseManager::get('lex');

$stmt = $pdo->query("
    SELECT * FROM lex_entries_staging
    WHERE status = 'pending'
    ORDER BY created_at ASC
");

$rows = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Lex Approval</title>
    <link rel="stylesheet" href="approval.css">
    <style>
        .preview-box {
            margin-top: 10px;
            padding: 10px;
            background: #f4f6f8;
            border-radius: 6px;
            font-size: 14px;
        }
        .preview-box pre {
            white-space: pre-wrap;
            word-break: break-word;
        }
    </style>
</head>
<body>

<h1>Pending lex entries</h1>

<div class="grid">

<?php foreach ($rows as $row): 
    $data = json_decode($row['payload_json'], true) ?? [];

    $lemma = $data['lemma'] ?? '';
    $entryWordClass = $data['word_class'] ?? '';
    $entrySubclass = $data['subclass'] ?? '';
    $senses = $data['senses'] ?? [];
?>

<div class="card">

<form method="POST" action="approval_action.php">

<input type="hidden" name="id" value="<?= (int)$row['id'] ?>">

<!-- ENTRY LEVEL -->

<label>Lemma</label>
<input name="lemma" value="<?= htmlspecialchars($lemma) ?>">

<label>Word class (entry)</label>
<select name="word_class">
<?php foreach (LexContract::WORD_CLASSES as $wc): ?>
<option value="<?= $wc ?>" <?= $wc === $entryWordClass ? 'selected' : '' ?>>
    <?= $wc ?>
</option>
<?php endforeach; ?>
</select>

<label>Subclass</label>
<input name="subclass" value="<?= htmlspecialchars($entrySubclass) ?>">

<hr>

<h3>Senses (<?= count($senses) ?>)</h3>

<?php foreach ($senses as $i => $sense): 

    $senseWordClass = $sense['word_class'] ?? '';
    $senseSubclass = $sense['subclass'] ?? '';
    $definition = $sense['definition'] ?? '';
    $example = $sense['explanations'][0]['example'] ?? '';
?>

<div class="sense">

<div class="sense-header">
    <span class="badge"><?= htmlspecialchars($senseWordClass) ?></span>
    <span class="sense-index">Sense <?= $i + 1 ?></span>
</div>

<label>Word class</label>
<select name="senses[<?= $i ?>][word_class]">
<?php foreach (LexContract::WORD_CLASSES as $wc): ?>
<option value="<?= $wc ?>" <?= $wc === $senseWordClass ? 'selected' : '' ?>>
    <?= $wc ?>
</option>
<?php endforeach; ?>
</select>

<label>Subclass</label>
<input name="senses[<?= $i ?>][subclass]" value="<?= htmlspecialchars($senseSubclass) ?>">

<label>Definition</label>
<textarea name="senses[<?= $i ?>][definition]"><?= htmlspecialchars($definition) ?></textarea>

<label>Example</label>
<textarea name="senses[<?= $i ?>][example]"><?= htmlspecialchars($example) ?></textarea>

</div>

<?php endforeach; ?>

<div class="actions">
    <button type="button" onclick="previewEntry(this)">Preview</button>
    <button name="action" value="approve">Approve</button>
    <button name="action" value="reject" class="danger">Reject</button>
</div>

</form>

<div class="preview-box" style="display:none;"></div>

</div>

<?php endforeach; ?>

</div>

<script>
async function previewEntry(btn) {

    const card = btn.closest('.card');
    const previewBox = card.querySelector('.preview-box');

    const word = card.querySelector('[name="lemma"]').value;
    const sentence = prompt("Test setning:");

    if (!sentence) return;

    const senses = [];

    card.querySelectorAll('.sense').forEach(s => {
        senses.push({
            word_class: s.querySelector('[name*="[word_class]"]').value,
            definition: s.querySelector('[name*="[definition]"]').value,
            explanations: [
                {
                    example: s.querySelector('[name*="[example]"]').value
                }
            ]
        });
    });

    const payload = {
        lemma: word,
        word_class: card.querySelector('[name="word_class"]').value,
        senses: senses
    };

    previewBox.style.display = 'block';
    previewBox.innerHTML = 'Laster...';

    try {

        const res = await fetch('/hn_lex/api/preview.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                word,
                sentence,
                override: payload
            })
        });

        const data = await res.json();

        previewBox.innerHTML = `
            <strong>Preview result:</strong>
            <pre>${JSON.stringify(data, null, 2)}</pre>
        `;

    } catch (e) {
        previewBox.innerHTML = 'Feil ved preview';
    }
}
</script>

</body>
</html>