<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);

require_once $root . '/hn_admin/bootstrap.php';
require_once __DIR__ . '/../inc/TextAdminService.php';

$layout_mode = 'admin';
$page_title  = 'Opprett tekst';

$service = new TextAdminService(db());

$books = $service->getBooks();
if (!$books) {
    exit('Ingen bøker funnet.');
}

$book = $_GET['book'] ?? $books[0];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_verify_or_fail();

    $book       = trim($_POST['book'] ?? '');
    $title      = trim($_POST['title'] ?? '');
    $textKey    = trim($_POST['text_key'] ?? '');
    $sourcePath = trim($_POST['source_path'] ?? '');

    if ($book === '' || $title === '' || $textKey === '' || $sourcePath === '') {
        $error = 'Alle felter må fylles ut.';
    } else {
        try {
            $service->createText($book, $title, $textKey, $sourcePath);
            header('Location: index.php?book=' . urlencode($book));
            exit;
        } catch (Throwable $e) {
            $error = 'Databasefeil: ' . $e->getMessage();
        }
    }
}

require_once $root . '/hn_core/layout/header.php';
?>

<main class="admin-page">

<header class="page-header">
    <h1>Opprett tekst</h1>
    <p class="page-intro">Registrer ny tekst i valgt bok</p>
</header>

<section class="admin-card-section">

<?php if ($error): ?>
    <div class="alert-error"><?= h($error) ?></div>
<?php endif; ?>

<form method="post" class="admin-form-grid">

    <?= csrf_input() ?>

    <div>
        <label>Bok</label>
        <select name="book" required>
            <?php foreach ($books as $b): ?>
                <option value="<?= h($b) ?>" <?= $b === $book ? 'selected' : '' ?>>
                    <?= h($b) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label>Tittel</label>
        <input type="text" name="title" required>
    </div>

    <div>
        <label>Tekst-ID</label>
        <input type="text" name="text_key" required>
    </div>

    <div>
        <label>Source path (relativ sti)</label>
        <input type="text" name="source_path" required>
    </div>

    <div>
        <button class="btn-primary">Opprett tekst</button>
        <a href="index.php?book=<?= h($book) ?>" class="btn-light">Avbryt</a>
    </div>

</form>

</section>

</main>

<?php require_once $root . '/hn_core/layout/footer.php'; ?>
