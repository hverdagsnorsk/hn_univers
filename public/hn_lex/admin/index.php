<?php
declare(strict_types=1);

$root = dirname(__DIR__, 3);

require_once $root . '/app/hn_core/inc/bootstrap.php';
require_once $root . '/app/hn_core/auth/admin.php';

use HnCore\Database\DatabaseManager;

/* ==========================================================
   CSRF TOKEN
========================================================== */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION['csrf_token'];

/* ==========================================================
   SESSION DATA (flash)
========================================================== */

$errors   = $_SESSION['lex_errors'] ?? [];
$approved = $_SESSION['lex_approved'] ?? [];

unset($_SESSION['lex_errors'], $_SESSION['lex_approved']);

/* ==========================================================
   DB
========================================================== */

$pdo = DatabaseManager::get('lex');

/* ==========================================================
   FETCH
========================================================== */

$stmt = $pdo->query("
    SELECT id, lemma, word_class, payload_json
    FROM lex_entries_staging
    WHERE status = 'pending'
    ORDER BY id DESC
");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==========================================================
   HELPERS
========================================================== */

function prettyJson(string $json): string {
    $data = json_decode($json, true);

    if (!$data) {
        return htmlspecialchars($json);
    }

    return htmlspecialchars(
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

/* ==========================================================
   HEADER
========================================================== */

$pageTitle = 'Lex Admin';
require_once $root . '/app/hn_core/layout/header.php';
?>

<style>
.container {
    max-width: 1100px;
    margin: 20px auto;
}

.entry {
    background: #fff;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 8px;
}

textarea {
    width: 100%;
    height: 240px;
    font-family: monospace;
    background: #111;
    color: #0f0;
    padding: 10px;
    border-radius: 4px;
}

button {
    padding: 8px 12px;
    margin-top: 10px;
    cursor: pointer;
}

.approve {
    background: #2ecc71;
    color: #fff;
}

.reject {
    background: #e74c3c;
    color: #fff;
}

.error {
    color: #e74c3c;
    font-weight: bold;
    margin-top: 6px;
}

.success {
    color: green;
    margin-bottom: 15px;
}

.invalid textarea {
    border: 2px solid red;
}
</style>

<div class="container">

<h1>Lex staging</h1>

<?php if (!empty($approved)): ?>
    <div class="success">
        Godkjent: <?= implode(', ', array_map('intval', $approved)) ?>
    </div>
<?php endif; ?>

<?php if (empty($rows)): ?>

    <div>Ingen oppføringer</div>

<?php else: ?>

<form id="form" method="POST" action="/hn_lex/admin/approve.php">

<input type="hidden" name="csrf_token" value="<?= $csrf ?>">

<button class="approve">Godkjenn valgte</button>
<button formaction="/hn_lex/admin/reject.php" class="reject">
    Avvis valgte
</button>

<?php foreach ($rows as $row): ?>

<div class="entry" id="entry-<?= $row['id'] ?>">

<label>
<input type="checkbox" class="select" name="ids[]" value="<?= $row['id'] ?>">
ID: <?= (int)$row['id'] ?> |
<?= htmlspecialchars($row['lemma']) ?> |
<?= htmlspecialchars($row['word_class'] ?? '-') ?>
</label>

<textarea
    name="payload[<?= $row['id'] ?>]"
    data-id="<?= $row['id'] ?>">
<?= prettyJson($row['payload_json']) ?>
</textarea>

<!-- Backend feil -->
<?php if (!empty($errors[$row['id']])): ?>
    <div class="error">
        ❌ <?= htmlspecialchars($errors[$row['id']]) ?>
    </div>
<?php endif; ?>

<!-- Frontend feil -->
<div id="error-<?= $row['id'] ?>" class="error" style="display:none;"></div>

<div>
    <button
        formaction="/hn_lex/admin/approve.php"
        name="ids[]"
        value="<?= $row['id'] ?>"
        class="approve">
        Godkjenn
    </button>

    <button
        formaction="/hn_lex/admin/reject.php"
        name="ids[]"
        value="<?= $row['id'] ?>"
        class="reject">
        Avvis
    </button>
</div>

</div>

<?php endforeach; ?>

</form>

<?php endif; ?>

</div>

<script>
const state = {}; // id -> valid true/false

function setInvalid(id, msg) {
    const box = document.getElementById('error-' + id);
    const entry = document.getElementById('entry-' + id);

    box.innerText = '❌ ' + msg;
    box.style.display = 'block';

    entry.classList.add('invalid');
    state[id] = false;
}

function setValid(id) {
    const box = document.getElementById('error-' + id);
    const entry = document.getElementById('entry-' + id);

    box.innerText = '';
    box.style.display = 'none';

    entry.classList.remove('invalid');
    state[id] = true;
}

function validate(textarea) {
    const id = textarea.dataset.id;
    let json;

    try {
        json = JSON.parse(textarea.value);
    } catch {
        return setInvalid(id, 'Ugyldig JSON');
    }

    if (!json.lemma) return setInvalid(id, 'lemma mangler');
    if (!json.word_class) return setInvalid(id, 'word_class mangler');
    if (!json.grammar || typeof json.grammar !== 'object') return setInvalid(id, 'grammar feil');
    if (!Array.isArray(json.senses) || json.senses.length === 0) return setInvalid(id, 'mangler senses');

    setValid(id);
}

document.querySelectorAll('textarea[data-id]').forEach(t => {
    validate(t);
    t.addEventListener('input', () => validate(t));
});

/* ==========================================================
   SUBMIT CONTROL (per entry)
========================================================== */

document.getElementById('form').addEventListener('submit', function(e) {

    const selected = document.querySelectorAll('.select:checked');

    for (const checkbox of selected) {
        const id = checkbox.value;

        if (state[id] === false) {
            alert('En valgt oppføring har feil. Rett før du godkjenner.');
            e.preventDefault();
            return;
        }
    }
});
</script>

<?php require_once $root . '/app/hn_core/layout/footer.php'; ?>