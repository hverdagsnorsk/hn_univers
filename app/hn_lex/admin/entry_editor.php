<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);

require_once $root . '/hn_core/inc/bootstrap.php';
require_once $root . '/hn_core/auth/admin.php';
require_once $root . '/hn_lex/inc/contracts/LexContract.php';
require_once $root . '/hn_lex/inc/LexTerminology.php';

use HnLex\Contracts\LexContract;

$pdo_lex = db('lex');

$page_title  = "Lex – Rediger oppslag";
$layout_mode = 'admin';

require_once $root . '/hn_core/layout/header.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    echo "<p>Ugyldig ID.</p>";
    require_once $root . '/hn_core/layout/footer.php';
    exit;
}

$stmt = $pdo_lex->prepare("
SELECT e.*, wc.code AS ordklasse
FROM lex_entries e
JOIN lex_word_classes wc ON wc.id = e.word_class_id
WHERE e.id = ?
LIMIT 1
");

$stmt->execute([$id]);
$entry = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$entry) {
    echo "<p>Oppslag ikke funnet.</p>";
    require_once $root . '/hn_core/layout/footer.php';
    exit;
}

$table = LexContract::getGrammarTable($entry['ordklasse']);
$grammar = [];

if ($table) {

    $table = preg_replace('/[^a-z_]/i','',$table);

    $stmt = $pdo_lex->prepare("SELECT * FROM {$table} WHERE entry_id = ?");
    $stmt->execute([$id]);

    $grammar = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}
?>

<style>

.editor-grid{
display:grid;
grid-template-columns:200px 1fr;
gap:15px;
max-width:900px;
}

.editor-grid div{
padding:8px 0;
}

.editor-value{
font-weight:600;
}

.editable{
outline:none;
padding:4px;
border-radius:4px;
}

.editable:hover{
background:#f3f7fb;
}

.lex-ai-tools{
margin-top:25px;
display:flex;
gap:12px;
flex-wrap:wrap;
}

.lex-ai-tools button{
cursor:pointer;
}

</style>

<main class="page admin-page">

<header class="page-header">
<h1><?= htmlspecialchars($entry['lemma']) ?></h1>
<p><?= LexTerminology::label($entry['ordklasse']) ?></p>
</header>

<section class="admin-card-section">

<h2>Grunninformasjon</h2>

<div class="editor-grid">

<div>ID</div>
<div><?= $entry['id'] ?></div>

<div>Lemma</div>

<div
contenteditable="true"
class="editable editor-value"
data-type="entry"
data-field="lemma"
data-id="<?= $entry['id'] ?>">

<?= htmlspecialchars($entry['lemma']) ?>

</div>

<div>Språk</div>
<div><?= htmlspecialchars($entry['language']) ?></div>

</div>


<?php if ($table && $grammar): ?>

<h2 style="margin-top:40px;">Grammatikk</h2>

<div class="editor-grid">

<?php foreach ($grammar as $key => $value):

if (in_array($key,['id','entry_id','created_at','updated_at'],true)) {
    continue;
}

?>

<div><?= LexTerminology::label($key) ?></div>

<div
contenteditable="true"
class="editable"
data-type="grammar"
data-field="<?= htmlspecialchars($key) ?>"
data-id="<?= $entry['id'] ?>">

<?= htmlspecialchars((string)$value) ?>

</div>

<?php endforeach; ?>

</div>

<?php endif; ?>


<div class="lex-ai-tools">

<button class="hn-btn"
onclick="generateAI('explanations')">

Generer forklaringer (A1–C1)

</button>

<button class="hn-btn"
onclick="generateAI('inflection')">

Generer bøyning

</button>

<button class="hn-btn"
onclick="generateAI('examples')">

Generer eksempelsetninger

</button>

</div>


<p style="margin-top:30px;">

<a class="hn-btn"
href="explanations_list.php?entry_id=<?= $entry['id'] ?>">

Rediger forklaringer

</a>

</p>

</section>

</main>

<script>

document.querySelectorAll('.editable').forEach(cell => {

cell.addEventListener('blur',function(){

fetch('ajax/ajax_update_entry.php',{

method:'POST',
headers:{'Content-Type':'application/json'},

body:JSON.stringify({

id:this.dataset.id,
type:this.dataset.type,
field:this.dataset.field,
value:this.innerText.trim()

})

});

});

});

async function generateAI(type){

const res = await fetch('ajax/ajax_generate_ai.php',{

method:'POST',

headers:{
'Content-Type':'application/json'
},

body:JSON.stringify({

entry_id:<?= $entry['id'] ?>,
type:type

})

});

const json = await res.json();

if(json.success){

alert("AI-generering fullført");

location.reload();

}else{

alert("AI-feil");

}

}

</script>

<?php require_once $root . '/hn_core/layout/footer.php'; ?>