<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Composer autoload (PHPWord)
|--------------------------------------------------------------------------
| Midlertidig lastet fra hn_translate
| Senere kan dette flyttes til felles hn_core/bootstrap.php
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/../hn_translate/vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| HN Core – felles parser
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/../hn_core/word/docx_parser.php';
require_once __DIR__ . '/../hn_core/text/html_parser.php';

$uploadDir = __DIR__ . '/data/original';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$error = null;
$success = null;

/**
 * Blokker → enkel HTML
 * Bevisst enkel, stabil og forutsigbar
 */
function blocks_to_html(array $blocks): string
{
    $html = '';

    foreach ($blocks as $b) {
        switch ($b['type']) {
            case 'h':
                $html .= '<h2>' . htmlspecialchars($b['content'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</h2>\n";
                break;

            case 'li':
                $html .= '<li>' . htmlspecialchars($b['content'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</li>\n";
                break;

            default:
                $html .= '<p>' . htmlspecialchars($b['content'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</p>\n";
        }
    }

    // Pakk sammen sammenhengende <li> til <ul>
    $html = preg_replace(
        '/(<li>.*<\/li>\s*)+/s',
        "<ul>\n$0</ul>\n",
        $html
    );

    return $html;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_FILES['doc']) || $_FILES['doc']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Ingen fil valgt, eller opplasting feilet.';
    } else {

        $tmp  = $_FILES['doc']['tmp_name'];
        $name = $_FILES['doc']['name'];

        if (!preg_match('/\.docx$/i', $name)) {
            $error = 'Kun Word-filer (.docx) er tillatt.';
        } else {

            $id = strtolower(pathinfo($name, PATHINFO_FILENAME));
            $id = preg_replace('/[^a-z0-9_-]+/', '-', $id);
            $id = trim($id, '-');

            if ($id === '') {
                $error = 'Ugyldig filnavn.';
            } else {
                try {
                    // 1. DOCX → blokker (hn_core)
                    $blocks = parse_docx_to_blocks($tmp);

                    if (empty($blocks)) {
                        throw new RuntimeException('Dokumentet inneholder ingen lesbar tekst.');
                    }

                    // 2. Blokker → HTML
                    $html = blocks_to_html($blocks);

                    // 3. Lagre HTML
                    file_put_contents($uploadDir . '/' . $id . '.html', $html);

                    $success = "Dokument importert som {$id}.html";

                } catch (Throwable $e) {
                    $error = $e->getMessage();
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="no">
<head>
<meta charset="utf-8">
<title>HN Lex – last opp Word-dokument</title>
<style>
body { font-family: system-ui; max-width: 700px; margin: 2rem auto; }
button { padding:.6rem 1rem; background:#2f8485; color:#fff; border:0; border-radius:4px; }
.success { background:#e6f6f6; border:1px solid #2f8485; padding:.75rem; }
.error { background:#fdeaea; border:1px solid #c00; padding:.75rem; }
</style>
</head>
<body>

<h1>Last opp Word-dokument</h1>

<p>
Last opp <strong>.docx</strong>. Dokumentet behandles via HN Core og gjøres klart for HN Lex.
</p>

<form method="post" enctype="multipart/form-data">
  <input type="file" name="doc" accept=".docx" required>
  <button type="submit">Last opp</button>
</form>

<?php if ($success): ?>
  <div class="success"><?= htmlspecialchars($success, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($error): ?>
  <div class="error"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
<?php endif; ?>

<a href="index.php">← Tilbake</a>

</body>
</html>
