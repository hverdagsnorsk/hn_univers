<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/hn_core/inc/bootstrap.php';

if (!isset(db('courses'))) {
    exit('Courses DB missing.');
}

$courseId = (int)($_GET['course_id'] ?? 0);

if (!$courseId) {
    exit('Missing course.');
}

/* ==========================================================
   LOAD COURSE
========================================================== */

$stmt = db('courses')->prepare("
    SELECT id, title
    FROM hn_course_courses
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$courseId]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    exit('Course not found.');
}

/* ==========================================================
   ADD VIDEO
========================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_video'])) {

    $title = trim($_POST['title'] ?? '');
    $url   = trim($_POST['url'] ?? '');

    if ($title !== '' && $url !== '') {

        $stmt = db('courses')->prepare("
            INSERT INTO hn_course_videos
            (course_id, title, url)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([$courseId, $title, $url]);

        header("Location: videos.php?course_id=" . $courseId);
        exit;
    }
}

/* ==========================================================
   DELETE VIDEO
========================================================== */

if (isset($_GET['delete'])) {

    $id = (int)$_GET['delete'];

    db('courses')->prepare("
        DELETE FROM hn_course_videos
        WHERE id = ?
          AND course_id = ?
    ")->execute([$id, $courseId]);

    header("Location: videos.php?course_id=" . $courseId);
    exit;
}

/* ==========================================================
   LOAD VIDEOS
========================================================== */

$stmt = db('courses')->prepare("
    SELECT *
    FROM hn_course_videos
    WHERE course_id = ?
    ORDER BY id ASC
");

$stmt->execute([$courseId]);
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Videoer – ' . $course['title'];

$layout_mode = 'admin';
require_once $root . '/hn_core/layout/header.php';
?>

<main class="page">

<header class="page-header">
    <h1><?= h($course['title']) ?></h1>
    <p>Administrer videoer</p>
</header>

<!-- ==========================================================
     ADD VIDEO
========================================================== -->

<section class="card" style="margin-bottom:2rem;max-width:600px;">
    <h2>Legg til video</h2>

    <form method="post" style="display:grid;gap:10px;">

        <input type="hidden" name="add_video" value="1">

        <input type="text"
               name="title"
               placeholder="Videotittel"
               required>

        <input type="url"
               name="url"
               placeholder="YouTube-lenke"
               required>

        <button class="btn-primary">
            Legg til
        </button>

    </form>
</section>

<!-- ==========================================================
     LIST VIDEOS
========================================================== -->

<section class="card">
    <h2>Eksisterende videoer</h2>

    <?php if (!$videos): ?>
        <p>Ingen videoer lagt til.</p>
    <?php else: ?>

        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:1px solid #ddd;">
                    <th align="left">Tittel</th>
                    <th align="left">Lenke</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>

            <?php foreach ($videos as $v): ?>
                <tr style="border-bottom:1px solid #eee;">
                    <td><?= h($v['title']) ?></td>
                    <td>
                        <a href="<?= h($v['url']) ?>" target="_blank">
                            Se
                        </a>
                    </td>
                    <td align="right">
                        <a class="btn-light"
                           href="?course_id=<?= $courseId ?>&delete=<?= (int)$v['id'] ?>"
                           onclick="return confirm('Slette video?')">
                           Slett
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
