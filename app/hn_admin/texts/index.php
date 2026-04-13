<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);

require_once $root . '/hn_admin/bootstrap.php';
require_once __DIR__ . '/../inc/TextAdminService.php';

$layout_mode = 'admin';
$page_title  = 'Tekster';

$service = new TextAdminService(db());

$books = $service->getBooks();
if (!$books) {
    exit('Ingen bøker funnet.');
}

$book = $_GET['book'] ?? $books[0];

$texts = $service->getTexts($book);
$unregistered = $service->getUnregistered($book);

require_once $root . '/hn_core/layout/header.php';
?>

<main class="admin-page">

<header class="page-header">
    <h1>Tekster</h1>
    <p class="page-intro">Administrer tekster for valgt bok</p>
</header>

<section class="admin-card-section">

    <form method="get" class="admin-form-grid">
        <select name="book" onchange="this.form.submit()">
            <?php foreach ($books as $b): ?>
                <option value="<?= h($b) ?>" <?= $b === $book ? 'selected' : '' ?>>
                    <?= h($b) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

</section>

<?php if ($unregistered): ?>
<section class="admin-card-section">
    <h2>Uregistrerte tekster</h2>
    <ul>
        <?php foreach ($unregistered as $file): ?>
            <li><code><?= h($file) ?></code></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<section class="admin-card-section">

    <h2>Registrerte tekster</h2>

    <?php if (!$texts): ?>
        <p class="muted">Ingen tekster registrert.</p>
    <?php else: ?>

        <table class="hn-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tittel</th>
                    <th>Tekst-ID</th>
                    <th>Oppgaver</th>
                    <th>Status</th>
                    <th>Opprettet</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($texts as $t): ?>
                <tr>
                    <td><?= (int)$t['id'] ?></td>
                    <td>
                        <strong><?= h($t['title']) ?></strong><br>
                        <small><?= h($t['source_path']) ?></small>
                    </td>
                    <td><?= h($t['text_key']) ?></td>
                    <td><?= (int)$t['task_count'] ?></td>
                    <td>
                        <span class="badge <?= $t['active'] ? 'badge-success' : 'badge-muted' ?>">
                            <?= $t['active'] ? 'Aktiv' : 'Inaktiv' ?>
                        </span>
                    </td>
                    <td><?= h($t['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>

</section>

</main>

<?php require_once $root . '/hn_core/layout/footer.php'; ?>
