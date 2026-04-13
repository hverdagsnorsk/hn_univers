<?php
declare(strict_types=1);

namespace HnCourses\Admin\Controller;

use HnCourses\Repository\CourseRepository;
use HnCourses\Service\CourseProvisionService;

final class CourseController
{
    public function index(): void
    {
        $repo = new CourseRepository(db('courses'));
        $courses = $repo->findAll();

        require __DIR__ . '/../../templates/courses.php';
    }

    public function create(): void
    {
        $error = null;

        require __DIR__ . '/../../templates/course_form.php';
    }

    public function store(): void
    {
        try {
            $service = new CourseProvisionService(
                db('courses'),
                dirname(__DIR__, 4) . '/hn_books/books'
            );

            $result = $service->create([
                'title'       => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? ''
            ]);

            header('Location: /hn_courses/public/course.php?slug=' . urlencode($result['slug']));
            exit;

        } catch (\Throwable $e) {
            $error = $e->getMessage();
            require __DIR__ . '/../../templates/course_form.php';
        }
    }

    public function edit(int $id): void
    {
        $repo = new CourseRepository(db('courses'));
        $course = $repo->findById($id);

        if (!$course) {
            echo "<div class='admin-error'>Kurs ikke funnet</div>";
            return;
        }

        require __DIR__ . '/../../templates/course_form.php';
    }

    public function update(int $id): void
    {
        $pdo = db('courses');

        $stmt = $pdo->prepare("
            UPDATE hn_course_courses
            SET title = ?, slug = ?, description = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $_POST['title'],
            $_POST['slug'],
            $_POST['description'],
            $id
        ]);

        header('Location: ?action=courses');
        exit;
    }
}