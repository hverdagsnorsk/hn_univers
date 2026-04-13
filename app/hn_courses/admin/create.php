<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/hn_core/inc/bootstrap.php';
require_once dirname(__DIR__, 2) . '/hn_core/auth/admin.php';

use HnCourses\Service\CourseProvisionService;
use function HnCourses\Support\course_db;

$pdo = course_db();

$error = null;

/* =========================
   HANDLE POST
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $service = new CourseProvisionService(
            $pdo,
            dirname(__DIR__, 2) . '/hn_books/books'
        );

        $result = $service->create([
            'title'       => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? ''
        ]);

        // redirect til kurs
        header('Location: /hn_courses/public/course.php?slug=' . urlencode($result['slug']));
        exit;

    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

/* =========================
   VIEW
========================= */

$page_title = "Opprett kurs";

require_once dirname(__DIR__, 2) . '/hn_core/layout/header.php';
?>

<div class="admin-container">

    <h1>Opprett nytt kurs</h1>

    <?php if ($error): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" class="form">

        <div class="form-group">
            <label>Tittel</label>
            <input type="text" name="title" required>
        </div>

        <div class="form-group">
            <label>Beskrivelse</label>
            <textarea name="description" rows="4"></textarea>
        </div>

        <button type="submit" class="btn-primary">
            Opprett kurs
        </button>

    </form>

</div>

<style>
.admin-container {
    max-width: 700px;
    margin: 40px auto;
}

.form-group {
    margin-bottom: 20px;
}

input, textarea {
    width: 100%;
    padding: 10px;
    font-size: 14px;
}

.btn-primary {
    background: #2f8485;
    color: white;
    border: none;
    padding: 12px 20px;
    cursor: pointer;
}

.error {
    background: #ffe0e0;
    padding: 10px;
    margin-bottom: 20px;
}
</style>

<?php
require_once dirname(__DIR__, 2) . '/hn_core/layout/footer.php';