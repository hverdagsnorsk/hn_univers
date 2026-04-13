<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| lex_debug.php
|--------------------------------------------------------------------------
| FULL DEBUG AV LEX-PIPELINE
| - AI → LexContract → saveLexEntry → DB
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/contracts/LexContract.php';
require_once __DIR__ . '/../inc/lex_save.php';
require_once __DIR__ . '/../../hn_core/ai.php';

$word = $_GET['word'] ?? '';
$lang = $_GET['lang'] ?? 'no';

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

?>
<!doctype html>
<html lang="no">
<head>
<meta charset="utf-8">
<title>HN Lex – Debug</title>
<style>
body { font-family: system-ui; padding:2rem; background:#fafafa; }
pre  { background:#111; color:#eee; padding:1rem; overflow:auto; }
h2   { margin-top:2rem; }
.ok  { color:green; font-weight:bold; }
.err { color:red; font-weight:bold; }
</style>
</head>
<body>

<h1>Lex Debug</h1>

<form method="get">
    <label>
        Ord:
        <input name="word" value="<?= h($word) ?>" required>
    </label>
    <label>
        Språk:
        <input name="lang" value="<?= h($lang) ?>" size="3">
    </label>
    <button>Kjør</button>
</form>

<?php if ($word): ?>

<hr>

<?php
$lemma = mb_strtolower(trim($word));
?>

<h2>1️⃣ AI – rå respons</h2>
<?php
try {
    $aiRaw = ai_generate_lex_entry($lemma);
    echo '<div class="ok">OK</div>';
    echo '<pre>' . h(json_encode($aiRaw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
} catch (Throwable $e) {
    echo '<div class="err">AI-FEIL: ' . h($e->getMessage()) . '</div>';
    exit;
}
?>

<h2>2️⃣ LexContract – normalisert</h2>
<?php
try {
    $entry = LexContract::fromAI($aiRaw, $lang);
    echo '<div class="ok">OK</div>';
    echo '<pre>' . h(json_encode($entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
} catch (Throwable $e) {
    echo '<div class="err">KONTRAKTFEIL: ' . h($e->getMessage()) . '</div>';
    exit;
}
?>

<h2>3️⃣ DB – lagring</h2>
<?php
try {
    saveLexEntry($pdo, $entry);
    echo '<div class="ok">Lagret via saveLexEntry()</div>';
} catch (Throwable $e) {
    echo '<div class="err">DB-FEIL: ' . h($e->getMessage()) . '</div>';
    exit;
}
?>

<h2>4️⃣ Verifisering i DB</h2>
<?php
$stmt = $pdo->prepare(
    "SELECT e.id, e.lemma, e.word_class_id, wc.code
     FROM lex_entries e
     JOIN lex_word_classes wc ON wc.id = e.word_class_id
     WHERE e.lemma = ? AND e.language = ?"
);
$stmt->execute([$lemma, $lang]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    echo '<div class="ok">Funnet i lex_entries</div>';
    echo '<pre>' . h(json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
} else {
    echo '<div class="err">IKKE funnet i lex_entries</div>';
}
?>

<?php endif; ?>

</body>
</html>
