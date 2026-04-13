<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);

require_once $root . '/hn_core/inc/bootstrap.php';

if (!isset(db('courses'))) {
    exit('Courses DB missing.');
}

$id = (int)($_GET['id'] ?? 0);

$stmt = db('courses')->prepare("
    SELECT *
    FROM hn_course_courses
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    exit('Course not found.');
}

/* ==========================================================
   HANDLE UPDATE
========================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $book  = trim($_POST['book_slug'] ?? '');
    $code  = trim($_POST['access_code'] ?? '');
    $active = isset($_POST['is_active']) ? 1 : 0;

    $hash = $course['access_code_hash'];

    if ($code !== '') {
        $hash = password_hash($code, PASSWORD_DEFAULT);
    }

    $stmt = db('courses')->prepare("
        UPDATE hn_course_courses
        SET title = ?,
            book_slug = ?,
            access_code_hash = ?,
            is_active = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $title,
        $book ?: null,
        $hash,
        $active,
        $id
    ]);

    header("Location: index.php");
    exit;
}

$page_title = 'Rediger kurs';

$layout_mode = 'admin';
require_once $root . '/hn_core/layout/header.php';
?>

<main class="page">

<section class="card" style="max-width:600px;margin:0 auto;">

<h2>Rediger kurs</h2>

<form method="post" style="display:grid;gap:10px;">

    <input type="text"
           name="title"
           value="<?= h($course['title']) ?>"
           required>

    <input type="text"
           name="book_slug"
           value="<?= h($course['book_slug'] ?? '') ?>"
           placeholder="Bok-slug">

    <input type="text"
           name="access_code"
           placeholder="Ny kurskode (la stå tom for uendret)">

    <label>
        <input type="checkbox"
               name="is_active"
               <?= $course['is_active'] ? 'checked' : '' ?>>
        Aktivt kurs
    </label>

    <button class="btn-primary">Lagre</button>

</form>

</section>

</main>

<?php require_once $root . '/hn_core/layout/footer.php'; ?>
