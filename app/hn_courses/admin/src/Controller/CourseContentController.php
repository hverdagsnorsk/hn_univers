<?php
declare(strict_types=1);

namespace HnCourses\Admin\Controller;

use HnCourses\Repository\ResourceRepository;
use HnCourses\Repository\EventRepository;
use HnCourses\Service\ResourceService;

class CourseContentController
{
    public function index(): void
    {
        $pdo = db('courses');

        $resourceRepo = new ResourceRepository($pdo);
        $eventRepo    = new EventRepository($pdo);

        $courseId = (int)($_GET['course_id'] ?? 0);
        $tab = $_GET['tab'] ?? 'leksjon';

        if (!$courseId) {
            die("Mangler course_id");
        }

        // 🔥 AssetLoader context (VIKTIG)
        $page_context = 'course';

        /* =========================
           COURSE
        ========================= */
        $stmt = $pdo->prepare("SELECT * FROM hn_course_courses WHERE id=?");
        $stmt->execute([$courseId]);
        $course = $stmt->fetch();

        if (!$course) {
            die("Kurs ikke funnet");
        }

        /* =========================
           📅 SCHEDULE TAB
        ========================= */
        if ($tab === 'schedule') {

            $events = $eventRepo->findByCourse($courseId);

            require dirname(__DIR__, 2) . '/templates/course_content.php';
            return;
        }

        /* =========================
           RESOURCE TABS
        ========================= */
        $config = $this->getTabConfig($tab);

        $resources = $resourceRepo->getLibraryForTab($courseId, $tab);
        $attached  = $resourceRepo->getAttachedForTab($courseId, $tab);

        require dirname(__DIR__, 2) . '/templates/course_content.php';
    }

    /* =========================
       🔥 UPDATED UPLOAD (SERVICE)
    ========================= */

    public function upload(): void
    {
        $pdo = db('courses');

        $repo = new ResourceRepository($pdo);
        $service = new ResourceService($repo);

        $courseId = (int)($_GET['course_id'] ?? 0);
        $tab = $_GET['tab'] ?? 'leksjon';

        if (!$courseId) {
            die('Missing course_id');
        }

        $config = $this->getTabConfig($tab);

        try {

            if (!empty($_POST['youtube_url'])) {
                $resourceId = $service->createYoutube($_POST['youtube_url']);
            }

            elseif ($tab === 'links') {
                $resourceId = $service->createLink(
                    $_POST['external_url'] ?? '',
                    $_POST['title'] ?? ''
                );
            }

            else {
                $resourceId = $service->uploadFile($_FILES['file'], $courseId, $tab);
            }

            $repo->attachToCourse($courseId, $resourceId, $config['category']);

        } catch (\Throwable $e) {
            die($e->getMessage());
        }

        $this->redirect($courseId, $tab);
    }

    /* =========================
       REST (URØRT)
    ========================= */

    public function attach(): void
    {
        $pdo = db('courses');
        $repo = new ResourceRepository($pdo);

        $courseId = (int)$_POST['course_id'];
        $resourceId = (int)$_POST['resource_id'];
        $category = $_POST['category'];
        $tab = $_POST['tab'] ?? 'leksjon';

        $repo->attachToCourse($courseId, $resourceId, $category);

        $this->redirect($courseId, $tab);
    }

    public function delete_resource_map(): void
    {
        $pdo = db('courses');
        $repo = new ResourceRepository($pdo);

        $repo->deleteFromCourse((int)$_POST['id']);

        $this->redirect((int)$_POST['course_id']);
    }

    private function getTabConfig(string $tab): array
    {
        return match ($tab) {
            'leksjon' => ['category' => 'leksjon'],
            'documents' => ['category' => 'documents'],
            'lesson_video' => ['category' => 'lesson_video'],
            'video' => ['category' => 'video'],
            'links' => ['category' => 'links'],
            'schedule' => ['category' => 'schedule'],
            default => ['category' => 'leksjon']
        };
    }

    private function redirect($courseId, $tab = 'leksjon'): void
    {
        header("Location: ?action=course_content&course_id={$courseId}&tab={$tab}");
        exit;
    }
}