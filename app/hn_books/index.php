<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| hn_books/index.php
|--------------------------------------------------------------------------
| - Responsive grid
| - Like cover-ratio
| - Tittel overlay
|--------------------------------------------------------------------------
*/

$root = dirname(__DIR__);

require_once $root . '/hn_core/inc/bootstrap.php';

$page_title = 'HN Books';

require_once $root . '/hn_core/layout/header.php';

/* ==========================================================
   FIND BOOK PROJECTS (NO CACHE FOR NOW)
========================================================== */

$booksDir = dirname(__DIR__, 2) . '/app/hn_books/books';

$books = [];

if (is_dir($booksDir)) {

    foreach (scandir($booksDir) as $project) {

        if ($project === '.' || $project === '..') {
            continue;
        }

        $projectDir = $booksDir . '/' . $project;
        $indexFile  = $projectDir . '/index.php';

        if (!is_dir($projectDir) || !is_file($indexFile)) {
            continue;
        }

        $title = ucwords(str_replace(['-', '_'], ' ', $project));

        $html = @file_get_contents($indexFile);

        if ($html !== false &&
            preg_match('/<title>(.*?)<\/title>/is', $html, $m)) {

            $title = trim(html_entity_decode(
                $m[1],
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            ));
        }

        $coverPath = $projectDir . '/cover.png';

        $coverUrl = is_file($coverPath)
            ? '/hn_books/books/' . rawurlencode($project) . '/cover.png'
            : null;

        $books[] = [
            'title' => $title,
            'url'   => '/hn_books/books/' . rawurlencode($project) . '/index.php',
            'cover' => $coverUrl
        ];
    }
}

usort($books, fn($a, $b) => strcasecmp($a['title'], $b['title']));
?>

<main class="page">

<header class="page-header">
<h1><?= h($page_title) ?></h1>
</header>

<?php if (!$books): ?>

<p>Ingen bøker funnet.</p>

<?php else: ?>

<section class="book-grid">

<?php foreach ($books as $b): ?>

<a class="book-card" href="<?= h($b['url']) ?>">

<?php if ($b['cover']): ?>
<img src="<?= h($b['cover']) ?>" alt="" loading="lazy">
<?php else: ?>
<div class="book-placeholder"></div>
<?php endif; ?>

<div class="book-overlay">
<?= h($b['title']) ?>
</div>

</a>

<?php endforeach; ?>

</section>

<?php endif; ?>

</main>

<style>
.book-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.book-card {
    position: relative;
    display: block;
    aspect-ratio: 3 / 4;
    overflow: hidden;
    border-radius: 14px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.book-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 14px 34px rgba(0,0,0,0.25);
}

.book-card img,
.book-placeholder {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    background: #ddd;
}

.book-overlay {
    position: absolute;
    bottom: 0;
    width: 100%;
    padding: 1rem;
    background: linear-gradient(
        to top,
        rgba(0,0,0,0.75),
        rgba(0,0,0,0)
    );
    color: #fff;
    font-weight: 600;
    font-size: 1.05rem;
}
</style>

<?php require_once $root . '/hn_core/layout/footer.php'; ?>