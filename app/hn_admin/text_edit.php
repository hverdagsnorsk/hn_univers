<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| hn_admin/text_edit.php
|--------------------------------------------------------------------------
*/

$root = dirname(__DIR__);

require_once $root . '/hn_core/inc/bootstrap.php';
require_once $root . '/hn_core/auth/admin.php';

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/text_generate.php';

$page_title  = "Rediger tekst";
$layout_mode = 'admin';

/* ======================================================
   HELPERS
====================================================== */

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function extract_editable_html(string $fullHtml): string
{
    if (preg_match('/<!--\s*RAW_HTML_START\s*-->(.*?)<!--\s*RAW_HTML_END\s*-->/si', $fullHtml, $m)) {
        return trim($m[1]);
    }

    if (preg_match('/<main\b[^>]*>(.*?)<\/main>/si', $fullHtml, $m)) {
        $inner = trim($m[1]);
        if ($inner !== '') return $inner;
    }

    if (preg_match('/<body\b[^>]*>(.*?)<\/body>/si', $fullHtml, $m)) {
        $inner = trim($m[1]);
        if ($inner !== '') return $inner;
    }

    return trim($fullHtml);
}

/* ======================================================
   INPUT
====================================================== */

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    exit('Mangler eller ugyldig id.');
}

/* ======================================================
   FETCH TEXT
====================================================== */

$stmt = db()->prepare("
    SELECT id, book_key, text_key, title, level, source_path, active, created_at
    FROM texts
    WHERE id = :id
    LIMIT 1
");
$stmt->execute(['id' => $id]);
$text = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$text) {
    exit('Fant ikke teksten i DB.');
}

$bookKey    = (string)$text['book_key'];
$textKey    = (string)$text['text_key'];
$title      = (string)$text['title'];
$level      = (string)($text['level'] ?? '');
$sourcePath = (string)$text['source_path'];
$active     = (int)($text['active'] ?? 1);

/* ======================================================
   READ EXISTING FILE
====================================================== */

$diskPath = $root . '/' . ltrim($sourcePath, '/');

$editableHtml = '';

if (is_file($diskPath)) {
    $existingHtml = (string)file_get_contents($diskPath);
    $editableHtml = extract_editable_html($existingHtml);
}

/* ======================================================
   POST SAVE (MÅ KOMME FØR HEADER OUTPUT)
====================================================== */

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $newTitle  = trim((string)($_POST['title'] ?? ''));
    $newLevel  = trim((string)($_POST['level'] ?? ''));
    $newActive = isset($_POST['active']) ? 1 : 0;
    $rawHtml   = trim((string)($_POST['raw_html'] ?? ''));

    if ($newTitle === '') {
        $error = 'Tittel må fylles ut.';
    } elseif ($rawHtml === '') {
        $error = 'Teksten er tom.';
    }

    if ($error === '') {

        $newSourcePath = generate_text_html_v2(
            bookKey: $bookKey,
            textKey: $textKey,
            title:   $newTitle,
            rawHtml: $rawHtml,
            level:   ($newLevel !== '' ? $newLevel : null)
        );

        $u = db()->prepare("
            UPDATE texts
            SET
                title = :title,
                level = :level,
                active = :active,
                source_path = :source_path
            WHERE id = :id
            LIMIT 1
        ");

        $u->execute([
            'title'       => $newTitle,
            'level'       => ($newLevel !== '' ? $newLevel : null),
            'active'      => $newActive,
            'source_path' => $newSourcePath,
            'id'          => $id,
        ]);

        header('Location: text_edit.php?id=' . $id . '&saved=1');
        exit;
    }
}

if (isset($_GET['saved']) && $_GET['saved'] === '1') {
    $success = 'Teksten er lagret.';
}

/* ======================================================
   HER STARTER OUTPUT
====================================================== */

require_once $root . '/hn_core/layout/header.php';
?>

<main class="hn-container hn-admin">

<section class="hn-hero">
    <div class="hn-hero__content">
        <h1>Rediger tekst</h1>
        <p><?= h($bookKey) ?> · <?= h($textKey) ?></p>
    </div>
</section>

<?php if ($error !== ''): ?>
    <div class="hn-alert hn-alert--error"><?= h($error) ?></div>
<?php endif; ?>

<?php if ($success !== ''): ?>
    <div class="hn-alert hn-alert--success"><?= h($success) ?></div>
<?php endif; ?>

<form method="post" id="editForm" novalidate>

    <div class="hn-card">
        <h3>Metadata</h3>

        <label>Tittel</label>
        <input name="title" value="<?= h($title) ?>" required>

        <label>Nivå</label>
        <input name="level" value="<?= h($level) ?>" placeholder="A2 / B1 / B2">

        <label>
            <input type="checkbox" name="active" value="1" <?= $active ? 'checked' : '' ?>>
            Aktiv
        </label>
    </div>

    <div class="hn-card hn-card--full">
        <h3>Tekst</h3>

        <textarea id="wysiwyg"><?= h($editableHtml) ?></textarea>
        <input type="hidden" name="raw_html" id="raw_html">
    </div>

    <div class="hn-form-actions">
        <button type="submit" class="hn-btn hn-btn--primary hn-btn--large">
            Lagre tekst
        </button>
    </div>

</form>

</main>

<script src="/hn_core/vendor/tinymce/tinymce.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {

  const form = document.getElementById('editForm');
  const raw  = document.getElementById('raw_html');

  tinymce.init({
    selector: '#wysiwyg',
    license_key: 'gpl',
    height: 650,
    menubar: false,
    branding: false,
    plugins: 'lists link',
    toolbar: 'undo redo | blocks | bold italic | bullist numlist | link | hn_lyd',
    block_formats: 'Avsnitt=p; Overskrift 2=h2; Overskrift 3=h3',
    setup: (editor) => {

      editor.ui.registry.addButton('hn_lyd', {
        text: 'Sett inn lydpunkt',
        onAction: () => editor.insertContent('<span class="hn-audio-marker">[[LYD]]</span>')
      });

      form.addEventListener('submit', () => {
        raw.value = editor.getContent({ format: 'html' });
      });
    }
  });

});
</script>

<?php require_once $root . '/hn_core/layout/footer.php'; ?>