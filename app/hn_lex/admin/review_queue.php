<?php
declare(strict_types=1);

session_start();

if (empty($_SESSION['admin'])) {
    header('Location: /hn_admin/login.php');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'].'/hn_core/inc/bootstrap.php';

use HnCore\Database\DatabaseManager;

$pdo = DatabaseManager::get('lex');

/* ==========================================================
   REVIEW QUEUE (AI-PAYLOAD SOM KAN GODKJENNES)
========================================================== */

$reviewStmt = $pdo->query("
    SELECT id, lemma, ai_payload, created_at
    FROM lex_review_queue
    WHERE status = 'pending'
    ORDER BY created_at DESC
    LIMIT 50
");

$reviewItems = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);

/* ==========================================================
   GENERATION QUEUE (ORD SOM KOMMER FRA OPPSLAG)
========================================================== */

$generationStmt = $pdo->query("
    SELECT id, word, language, level, status, error_message, created_at, updated_at
    FROM lex_generation_queue
    WHERE status = 'pending'
    ORDER BY created_at DESC
    LIMIT 100
");

$generationItems = $generationStmt->fetchAll(PDO::FETCH_ASSOC);

/* ==========================================================
   COUNTS
========================================================== */

$reviewCount = count($reviewItems);
$generationCount = count($generationItems);

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function selectedIf(string $value, ?string $current): string {
    return $value === (string)$current ? ' selected' : '';
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>Lex Review</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f5f5f5;
    padding: 20px;
    color: #222;
}
h1, h2 {
    margin-top: 0;
}
.summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
    margin-bottom: 24px;
}
.summary-card {
    background: white;
    padding: 16px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,.08);
}
.summary-card .label {
    font-size: 13px;
    color: #666;
}
.summary-card .value {
    font-size: 28px;
    font-weight: bold;
    margin-top: 6px;
}
.notice {
    background: #fff8d7;
    border: 1px solid #ecd98a;
    padding: 14px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.section {
    margin-top: 30px;
}
.card {
    background: white;
    padding: 20px;
    margin-bottom: 15px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,.08);
}
.meta {
    color: #666;
    font-size: 13px;
    margin-bottom: 12px;
}
textarea {
    width: 100%;
    min-height: 220px;
    font-family: monospace;
    font-size: 13px;
    box-sizing: border-box;
}
button {
    padding: 10px 15px;
    margin-top: 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
.approve {
    background: green;
    color: white;
}
.reject {
    background: #c62828;
    color: white;
    margin-left: 8px;
}
.preview {
    background: #fafafa;
    padding: 12px;
    margin-top: 12px;
    border-radius: 5px;
    border: 1px solid #eee;
}
label {
    font-weight: bold;
}
select {
    padding: 6px;
    min-width: 180px;
}
.table-wrap {
    background: white;
    border-radius: 8px;
    overflow: auto;
    box-shadow: 0 1px 3px rgba(0,0,0,.08);
}
table {
    width: 100%;
    border-collapse: collapse;
}
th, td {
    text-align: left;
    padding: 10px 12px;
    border-bottom: 1px solid #eee;
    vertical-align: top;
}
th {
    background: #fafafa;
}
.small {
    font-size: 12px;
    color: #666;
}
.empty {
    background: white;
    padding: 18px;
    border-radius: 8px;
    color: #666;
    box-shadow: 0 1px 3px rgba(0,0,0,.08);
}
.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: bold;
    background: #eee;
}
.badge-pending {
    background: #fff3cd;
    color: #8a6d3b;
}
</style>

<script>
function updatePreview(textareaId, previewId) {
    const textarea = document.getElementById(textareaId);
    const preview = document.getElementById(previewId);

    if (!textarea || !preview) return;

    try {
        const data = JSON.parse(textarea.value);

        let html = '';
        html += '<b>Lemma:</b> ' + (data.lemma || '-') + '<br>';
        html += '<b>Ordklasse:</b> ' + (data.word_class || '-') + '<br>';

        if (data.grammar && typeof data.grammar === 'object') {
            html += '<b>Grammatikk:</b><br>';
            for (const k in data.grammar) {
                html += k + ': ' + data.grammar[k] + '<br>';
            }
        }

        if (Array.isArray(data.senses) && data.senses.length) {
            const first = data.senses[0] || {};
            html += '<b>Forklaring:</b><br>';
            html += (first.definition || '-') + '<br>';

            if (first.example1) {
                html += '<i>' + first.example1 + '</i><br>';
            }
        }

        preview.innerHTML = html;

    } catch (e) {
        preview.innerHTML = '❌ Ugyldig JSON';
    }
}

function rejectItem(id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/hn_lex/api/admin/review_save.php';

    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'id';
    idInput.value = id;

    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'reject';

    form.appendChild(idInput);
    form.appendChild(actionInput);

    document.body.appendChild(form);
    form.submit();
}
</script>
</head>
<body>

<h1>Lex admin – review og køstatus</h1>

<div class="summary">
    <div class="summary-card">
        <div class="label">Pending i review-kø</div>
        <div class="value"><?= $reviewCount ?></div>
    </div>
    <div class="summary-card">
        <div class="label">Pending i generation-kø</div>
        <div class="value"><?= $generationCount ?></div>
    </div>
</div>

<?php if ($reviewCount === 0): ?>
    <div class="notice">
        <b>Ingen pending i lex_review_queue.</b><br>
        Oppslagene dine ser ut til å havne i <code>lex_generation_queue</code>, men ikke videre i <code>lex_review_queue</code> ennå.
        Det betyr at denne siden i seg selv ikke er feil – den viser bare at neste steg i pipeline ikke har kjørt.
    </div>
<?php endif; ?>

<div class="section">
    <h2>1. Pending AI-review (<code>lex_review_queue</code>)</h2>

    <?php if (!$reviewItems): ?>
        <div class="empty">Ingen rader klare for manuell godkjenning akkurat nå.</div>
    <?php else: ?>

        <?php foreach ($reviewItems as $item): ?>
            <?php
            $data = json_decode((string)$item['ai_payload'], true);
            if (!is_array($data)) {
                $data = [];
            }

            $suggestedWordClass = $data['word_class'] ?? '';
            $textareaId = 'payload_' . (int)$item['id'];
            $previewId  = 'preview_' . (int)$item['id'];
            ?>
            <div class="card">
                <h3><?= h($item['lemma']) ?></h3>
                <div class="meta">
                    ID: <?= (int)$item['id'] ?> · Opprettet: <?= h($item['created_at']) ?>
                </div>

                <form method="post" action="/hn_lex/api/admin/review_save.php">
                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">

                    <label for="wc_<?= (int)$item['id'] ?>">Word class:</label><br>
                    <select id="wc_<?= (int)$item['id'] ?>" name="word_class">
                        <option value="">-- velg --</option>
                        <option value="verb"<?= selectedIf('verb', $suggestedWordClass) ?>>verb</option>
                        <option value="substantiv"<?= selectedIf('substantiv', $suggestedWordClass) ?>>substantiv</option>
                        <option value="adjektiv"<?= selectedIf('adjektiv', $suggestedWordClass) ?>>adjektiv</option>
                        <option value="pronomen"<?= selectedIf('pronomen', $suggestedWordClass) ?>>pronomen</option>
                        <option value="tallord"<?= selectedIf('tallord', $suggestedWordClass) ?>>tallord</option>
                        <option value="preposisjon"<?= selectedIf('preposisjon', $suggestedWordClass) ?>>preposisjon</option>
                        <option value="adverb"<?= selectedIf('adverb', $suggestedWordClass) ?>>adverb</option>
                        <option value="konjunksjon"<?= selectedIf('konjunksjon', $suggestedWordClass) ?>>konjunksjon</option>
                        <option value="subjunksjon"<?= selectedIf('subjunksjon', $suggestedWordClass) ?>>subjunksjon</option>
                        <option value="interjeksjon"<?= selectedIf('interjeksjon', $suggestedWordClass) ?>>interjeksjon</option>
                    </select>

                    <br><br>

                    <label for="<?= h($textareaId) ?>">JSON (edit before save):</label>
                    <textarea
                        id="<?= h($textareaId) ?>"
                        name="payload"
                        oninput="updatePreview('<?= h($textareaId) ?>', '<?= h($previewId) ?>')"
                    ><?= h(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></textarea>

                    <div class="preview" id="<?= h($previewId) ?>"></div>

                    <button type="submit" class="approve" name="action" value="approve">Godkjenn</button>
                    <button type="button" class="reject" onclick="rejectItem(<?= (int)$item['id'] ?>)">Avvis</button>
                </form>
            </div>

            <script>
            updatePreview('<?= h($textareaId) ?>', '<?= h($previewId) ?>');
            </script>
        <?php endforeach; ?>

    <?php endif; ?>
</div>

<div class="section">
    <h2>2. Pending generation-kø (<code>lex_generation_queue</code>)</h2>

    <?php if (!$generationItems): ?>
        <div class="empty">Ingen pending rader i generation-kø akkurat nå.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ord</th>
                        <th>Språk</th>
                        <th>Nivå</th>
                        <th>Status</th>
                        <th>Opprettet</th>
                        <th>Oppdatert</th>
                        <th>Feilmelding</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($generationItems as $row): ?>
                        <tr>
                            <td><?= (int)$row['id'] ?></td>
                            <td><b><?= h($row['word']) ?></b></td>
                            <td><?= h($row['language']) ?></td>
                            <td><?= h($row['level']) ?></td>
                            <td><span class="badge badge-pending"><?= h($row['status']) ?></span></td>
                            <td><?= h($row['created_at']) ?></td>
                            <td><?= h($row['updated_at']) ?></td>
                            <td class="small"><?= h($row['error_message']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

</body>
</html>