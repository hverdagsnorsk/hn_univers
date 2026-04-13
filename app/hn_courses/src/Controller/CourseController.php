<?php
declare(strict_types=1);

namespace HnCourses\Controller;

use HnCore\Database\DatabaseManager;
use HnCourses\Repository\CourseRepository;
use HnCourses\Service\CoursePageService;

final class CourseController
{
    private CoursePageService $service;

    public function __construct()
    {
        $pdo = DatabaseManager::get('courses');
        $repo = new CourseRepository($pdo);

        $this->service = new CoursePageService($repo);
    }

    /**
     * Kursliste
     */
    public function index(): void
    {
        $courses = $this->service->getAllCourses();

        require HN_APP_ROOT . '/hn_courses/templates/index.php';
    }

    /**
     * Kursside
     */
    public function show(string $slug): void
    {
        $data = $this->service->getCoursePage($slug);

        if (!$data) {
            http_response_code(404);
            echo 'Course not found';
            return;
        }

        extract($data);

        require HN_APP_ROOT . '/hn_courses/templates/show.php';
    }

    /**
     * 🔥 NY: Klasseside
     */
    public function class(int $id): void
    {
        $data = $this->service->getClassPage($id);

        if (!$data) {
            http_response_code(404);
            echo 'Class not found';
            return;
        }

        extract($data);

        require HN_APP_ROOT . '/hn_courses/templates/class.php';
    }
}