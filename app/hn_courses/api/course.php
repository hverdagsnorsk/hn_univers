<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/../hn_core/inc/bootstrap.php';

use HnCourses\Repository\CourseRepository;
use HnCourses\Repository\EventRepository;
use function HnCourses\Support\course_db;

header('Content-Type: application/json');

try {
    $slug = $_GET['slug'] ?? '';

    if (!$slug) {
        http_response_code(400);
        echo json_encode(['error' => 'Mangler slug']);
        exit;
    }

    $pdo = course_db();

    $courseRepo = new CourseRepository($pdo);
    $eventRepo  = new EventRepository($pdo);

    /* =========================
       COURSE
    ========================= */

    $course = $courseRepo->findBySlug($slug);

    if (!$course) {
        http_response_code(404);
        echo json_encode(['error' => 'Kurs ikke funnet']);
        exit;
    }

    $courseId = (int)$course['id'];

    /* =========================
       EVENTS
    ========================= */

    $events = $eventRepo->findByCourse($courseId);

    /* =========================
       RESOURCES (REPO)
    ========================= */

    $resources = $courseRepo->getResources($courseId);

    /* =========================
       PARTNERS
    ========================= */

    $partners = $courseRepo->getPartners($courseId);

    /* =========================
       NEXT EVENT
    ========================= */

    $nextEvent = null;
    $now = new DateTimeImmutable();

    foreach ($events as $event) {
        if (new DateTimeImmutable($event['start_datetime']) >= $now) {
            $nextEvent = $event;
            break;
        }
    }

    /* =========================
       RESPONSE
    ========================= */

    echo json_encode([
        'course' => [
            'id'          => $courseId,
            'title'       => $course['title'],
            'slug'        => $course['slug'],
            'book_slug'   => $course['book_slug'],
            'description' => $course['description'] ?? ''
        ],

        'events' => array_map(function ($e) {
            return [
                'id'             => (int)$e['id'],
                'start_datetime' => $e['start_datetime'],
                'end_datetime'   => $e['end_datetime'],
                'location'       => $e['location'],
                'meeting_url'    => $e['meeting_url'],
                'description'    => $e['description'],
                'summary'        => $e['summary']
            ];
        }, $events),

        'resources'  => $resources,
        'partners'   => $partners,
        'next_event' => $nextEvent

    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'error' => 'Serverfeil',
        'debug' => $e->getMessage()
    ]);
}