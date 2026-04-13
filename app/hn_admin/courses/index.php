<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/hn_core/inc/bootstrap.php';

if (!isset(db('courses'))) {
    exit('Courses DB missing.');
}

/* ==========================================================
   HANDLE CREATE
========================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $slug  = trim($_POST['slug'] ?? '');
    $book  = trim($_POST['book_slug'] ?? '');
    $code  = trim($_POST['access_code'] ?? '');

    if ($title !== '' && $slug !== '') {

        $hash = $code !== '' 
            ? password_hash($code, PASSWORD_DEFAULT) 
            : null;

        $stmt = db('courses')->prepare("
            INSERT INTO hn_course_courses
            (slug, title, book_slug, access_code_hash)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $slug,
            $title,
            $book ?: null,
            $hash
        ]);

        header("Location: index.php");
        exit;
    }
}

/* ==========================================================
   LOAD COURSES
========================================================== */

$stmt = db('courses')->query("
    SELECT *
    FROM hn_course_courses
    ORDER BY created_at DESC
");

$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Admin – Kurs';

$layout_mode = 'admin';
require_once $root . '/hn_core/layout/header.php';
?>

<main class="page">

<header class="page-header">
    <h1>Kursadministrasjon</h1>
</header>

<!-- ==========================================================
     CREATE COURSE
========================================================== -->

<section class="card" style="margin-bottom:2rem;">
    <h2>Opprett nytt kurs</h2>

    <form method="post" style="display:grid;gap:10px;max-width:500px;">

        <input type="text"
               name="title"
               placeholder="Kursnavn"
               required>

        <input type="text"
               name="slug"
               placeholder="Slug (f.eks. bua-a2)"
               required>

        <input type="text"
               name="book_slug"
               placeholder="Bok-slug (valgfritt)">

        <input type="text"
               name="access_code"
               placeholder="Kurskode (valgfritt)">

        <button class="btn-primary">
            Opprett
        </button>

    </form>
</section>

<!-- ==========================================================
     COURSE LIST
========================================================== -->

<section class="card">
    <h2>Eksisterende kurs</h2>

    <?php if (!$courses): ?>

        <p>Ingen kurs opprettet.</p>

    <?php else: ?>

        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:1px solid #ddd;">
                    <th align="left">Tittel</th>
                    <th align="left">Slug</th>
                    <th align="left">Aktiv</th>
                    <th align="right">Handlinger</th>
                </tr>
            </thead>
            <tbody>

            <?php foreach ($courses as $c): ?>
                <tr style="border-bottom:1px solid #eee;">
                    <td><?= h($c['title']) ?></td>
                    <td><?= h($c['slug']) ?></td>
                    <td>
                        <?= $c['is_active'] ? 
                            '<span class="badge">Aktiv</span>' :
                            '<span class="badge badge--light">Inaktiv</span>' ?>
                    </td>
                    <td align="right" style="white-space:nowrap;">

                        <a class="btn-primary"
                        href="dashboard.php?course_id=<?= (int)$c['id'] ?>">
                        Dashboard
                        </a>

                        <a class="btn-dark"
                        href="schedule.php?course_id=<?= (int)$c['id'] ?>">
                        Schedule
                        </a>

                        <a class="btn-primary"
                           href="<?= HN_COURSE_BASE ?>/course.php?course=<?= h($c['slug']) ?>"
                           target="_blank">
                           Vis
                        </a>

                    </td>
                </tr>
            <?php endforeach; ?>

            </tbody>
        </table>

    <?php endif; ?>

</section>

</main>

<?php require_once $root . '/hn_core/layout/footer.php'; ?>
