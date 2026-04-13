<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);

require_once $root . '/hn_core/inc/bootstrap.php';
require_once $root . '/hn_core/auth/admin.php';

/* ======================================================
   DATABASE
====================================================== */

$pdo_lex = db('lex');

/* ======================================================
   PAGE
====================================================== */

$page_title  = "Lex – Forklaringer";
$layout_mode = 'admin';

require_once $root . '/hn_core/layout/header.php';

/* ======================================================
   INPUT
====================================================== */

$entryId = (int)($_GET['entry_id'] ?? 0);

if ($entryId <= 0) {
    echo "<main class='page admin-page'><p>Ugyldig entry_id</p></main>";
    require_once $root . '/hn_core/layout/footer.php';
    exit;
}

/* ======================================================
   HENT LEMMA
====================================================== */

$stmt = $pdo_lex->prepare("
    SELECT lemma
    FROM lex_entries
    WHERE id = ?
");

$stmt->execute([$entryId]);

$lemma = (string)($stmt->fetchColumn() ?? '');

/* ======================================================
   HENT FORKLARINGER
====================================================== */

$stmt = $pdo_lex->prepare("
    SELECT id, level, explanation, example
    FROM lex_explanations
    WHERE entry_id = ?
    ORDER BY level
");

$stmt->execute([$entryId]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
?>

<style>

.explanation-block{
    margin-bottom:30px;
    padding:20px;
    background:#ffffff;
    border-radius:10px;
    box-shadow:0 4px 10px rgba(0,0,0,0.05);
}

.editable{
    outline:none;
    padding:8px;
    border-radius:6px;
    transition:0.2s;
    min-height:24px;
}

.editable:hover{
    background:#f3f7fb;
}

.editable.saving{
    background:#fff3cd;
}

.editable.saved{
    background:#d4edda;
}

</style>

<main class="page admin-page">

<header class="page-header">
    <h1><?= htmlspecialchars($lemma, ENT_QUOTES, 'UTF-8') ?></h1>
    <p>Forklaringer</p>
</header>

<section class="admin-card-section">

<?php foreach ($rows as $row): ?>

<?php
$level       = (string)($row['level'] ?? '');
$explanation = (string)($row['explanation'] ?? '');
$example     = (string)($row['example'] ?? '');
$id          = (int)($row['id'] ?? 0);
?>

<div class="explanation-block">

<h3>Nivå <?= htmlspecialchars($level, ENT_QUOTES, 'UTF-8') ?></h3>

<strong>Forklaring</strong>

<div contenteditable="true"
     class="editable"
     data-id="<?= $id ?>"
     data-field="explanation">
<?= htmlspecialchars($explanation, ENT_QUOTES, 'UTF-8') ?>
</div>

<br>

<strong>Eksempel</strong>

<div contenteditable="true"
     class="editable"
     data-id="<?= $id ?>"
     data-field="example">
<?= htmlspecialchars($example, ENT_QUOTES, 'UTF-8') ?>
</div>

</div>

<?php endforeach; ?>

<?php if (empty($rows)): ?>
<p>Ingen forklaringer funnet.</p>
<?php endif; ?>

</section>
</main>

<script>

document.querySelectorAll('.editable').forEach(cell => {

    cell.addEventListener('blur', async function(){

        const id    = this.dataset.id;
        const field = this.dataset.field;
        const value = this.innerText.trim();

        this.classList.add('saving');

        try {

            const res = await fetch('ajax/ajax_update_explanation.php', {
                method:'POST',
                headers:{ 'Content-Type':'application/json' },
                body:JSON.stringify({ id, field, value })
            });

            const json = await res.json();

            this.classList.remove('saving');

            if(json.success){
                this.classList.add('saved');
                setTimeout(()=>this.classList.remove('saved'),600);
            } else {
                alert('Lagring feilet');
            }

        } catch(e){

            this.classList.remove('saving');
            alert('Serverfeil');

        }

    });

});

</script>

<?php require_once $root . '/hn_core/layout/footer.php'; ?>