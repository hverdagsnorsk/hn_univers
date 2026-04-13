<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);

require_once $root.'/hn_core/inc/bootstrap.php';
require_once $root.'/hn_core/auth/admin.php';
require_once $root.'/hn_lex/inc/LexTerminology.php';

$pdo_lex = db('lex');

$page_title  = "Lex – Oppslag";
$layout_mode = 'admin';

require_once $root.'/hn_core/layout/header.php';

/* ==========================================================
INPUT
========================================================== */

$q         = trim($_GET['q'] ?? '');
$wordClass = $_GET['word_class'] ?? '';
$sort      = $_GET['sort'] ?? 'id';
$dir       = strtolower($_GET['dir'] ?? 'desc');

$allowedSort = ['id','lemma','ordklasse','senses','missing_expl'];

if (!in_array($sort,$allowedSort,true)) {
    $sort = 'id';
}

$dirSql = $dir === 'asc' ? 'ASC' : 'DESC';

/* ==========================================================
WHERE
========================================================== */

$where  = [];
$params = [];

if ($q !== '') {
    $where[] = "e.lemma LIKE ?";
    $params[] = "%{$q}%";
}

if ($wordClass !== '') {
    $where[] = "wc.code = ?";
    $params[] = $wordClass;
}

$whereSql = $where ? "WHERE ".implode(' AND ',$where) : '';

/* ==========================================================
SORT
========================================================== */

$orderBy = match ($sort) {

    'lemma'        => 'e.lemma',
    'ordklasse'    => 'wc.code',
    'senses'       => 'senses',
    'missing_expl' => 'missing_expl',

    default        => 'e.id'
};

/* ==========================================================
QUERY
========================================================== */

$sql = "

SELECT
    e.id,
    e.lemma,
    wc.code AS ordklasse,
    e.language,

    (SELECT COUNT(*) FROM lex_senses s WHERE s.entry_id = e.id) AS senses,

    (SELECT COUNT(*) 
     FROM lex_explanations ex 
     WHERE ex.entry_id = e.id) AS explanations,

    CASE
        WHEN EXISTS (
            SELECT 1 FROM lex_explanations ex
            WHERE ex.entry_id = e.id
        )
        THEN 0
        ELSE 1
    END AS missing_expl

FROM lex_entries e
JOIN lex_word_classes wc ON wc.id = e.word_class_id

{$whereSql}

ORDER BY {$orderBy} {$dirSql}

LIMIT 200
";

$stmt = $pdo_lex->prepare($sql);
$stmt->execute($params);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==========================================================
HELPERS
========================================================== */

function sortLink($column,$label,$sort,$dir){

    $newDir = ($sort === $column && $dir === 'asc') ? 'desc' : 'asc';

    $query = $_GET;
    $query['sort'] = $column;
    $query['dir']  = $newDir;

    $qs = http_build_query($query);

    return "<a href=\"?{$qs}\">{$label}</a>";
}

function languageLabel(string $code):string{
    return match($code){
        'nb' => 'Norsk',
        default => strtoupper($code)
    };
}

?>

<style>

.lex-search-bar{
display:flex;
gap:12px;
margin-bottom:30px;
}

.lex-table tr:hover{
background:#f8fafc;
}

.lex-badge{
padding:4px 10px;
border-radius:20px;
font-size:12px;
font-weight:600;
background:#eef2f7;
}

.lex-warning{
background:#ffe5e5;
color:#9b1c1c;
padding:4px 8px;
border-radius:6px;
font-size:12px;
}

.lex-ok{
background:#e6f7ec;
color:#146c2e;
padding:4px 8px;
border-radius:6px;
font-size:12px;
}

.lex-actions a{
text-decoration:none;
font-size:13px;
margin-right:12px;
}

.editable{
font-weight:600;
outline:none;
}

</style>

<main class="page admin-page">

<header class="page-header">
<h1>Lex – Oppslag</h1>
<p>Administrasjon av hele HN-ordboka</p>
</header>

<section class="admin-card-section">

<form method="get" class="lex-search-bar">

<input
type="text"
name="q"
value="<?=htmlspecialchars($q)?>"
placeholder="Søk lemma..."
class="hn-input">

<select name="word_class" class="hn-input">

<option value="">Alle ordklasser</option>

<?php

$classes = $pdo_lex
->query("SELECT code FROM lex_word_classes ORDER BY code")
->fetchAll(PDO::FETCH_COLUMN);

foreach($classes as $wc){

$sel = $wc === $wordClass ? 'selected':'';

$label = LexTerminology::label($wc);

echo "<option value=\"{$wc}\" {$sel}>{$label}</option>";

}

?>

</select>

<button class="hn-btn">Filtrer</button>

</form>


<table class="hn-table lex-table">

<thead>

<tr>

<th><?=sortLink('id','ID',$sort,$dir)?></th>

<th><?=sortLink('lemma','Lemma',$sort,$dir)?></th>

<th><?=sortLink('ordklasse','Ordklasse',$sort,$dir)?></th>

<th>Språk</th>

<th><?=sortLink('senses','Senses',$sort,$dir)?></th>

<th><?=sortLink('missing_expl','Forklaring',$sort,$dir)?></th>

<th>Handling</th>

</tr>

</thead>

<tbody>

<?php foreach($rows as $row): ?>

<tr>

<td><?= $row['id'] ?></td>

<td
contenteditable="true"
class="editable"
data-id="<?= $row['id'] ?>"
data-field="lemma">

<?= htmlspecialchars($row['lemma']) ?>

</td>

<td>

<span class="lex-badge">

<?= htmlspecialchars(
LexTerminology::label($row['ordklasse'])
) ?>

</span>

</td>

<td>

<?= languageLabel($row['language']) ?>

</td>

<td>

<?= $row['senses'] ?>

</td>

<td>

<?php

if($row['missing_expl']){

echo "<span class='lex-warning'>Mangler</span>";

}else{

echo "<span class='lex-ok'>OK</span>";

}

?>

</td>

<td class="lex-actions">

<a href="entry_editor.php?id=<?=$row['id']?>">✏ Rediger</a>

<a href="explanations_list.php?entry_id=<?=$row['id']?>">📖 Forklaringer</a>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</section>

</main>

<script>

document.querySelectorAll('.editable').forEach(cell=>{

cell.addEventListener('blur',function(){

const id=this.dataset.id;
const field=this.dataset.field;
const value=this.innerText.trim();

fetch('ajax/ajax_update_entry.php',{

method:'POST',
headers:{'Content-Type':'application/json'},
body:JSON.stringify({id,field,value})

});

});

});

</script>

<?php require_once $root.'/hn_core/layout/footer.php'; ?>