<?php
declare(strict_types=1);

namespace HnCourses\Service;

use RuntimeException;
use HnCourses\Repository\CourseRepository;

final class CoursePageService
{
    public function __construct(
        private CourseRepository $repo
    ) {}

    /**
     * Hent alle kurs (listevisning)
     */
    public function getAllCourses(): array
    {
        return $this->repo->findAll();
    }

    /**
     * Hent komplett kursside (wrapper for controller)
     */
    public function getCoursePage(string $slug): ?array
    {
        try {
            return $this->load($slug);
        } catch (RuntimeException) {
            return null;
        }
    }

    /**
     * 🔥 NY: Hent klasseside
     */
    public function getClassPage(int $id): ?array
    {
        $class = $this->repo->findClassById($id);

        if (!$class) {
            return null;
        }

        $course = $this->repo->findById((int)$class['course_id']);

        return [
            'class'  => $class,
            'course' => $course
        ];
    }

    /**
     * Intern lastelogikk (kursside)
     */
    public function load(string $slug): array
    {
        $course = $this->repo->findBySlug($slug);

        if (!$course) {
            throw new RuntimeException('Course not found');
        }

        $events = $this->repo->getEvents((int)$course['id']);
        $resources = $this->repo->getResources((int)$course['id']);

        $now = date('Y-m-d H:i:s');
        $nextEvent = null;

        foreach ($events as $e) {
            if ($e['start_datetime'] >= $now) {
                $nextEvent = $e;
                break;
            }
        }

        return [
            'course'     => $course,
            'events'     => $events,
            'resources'  => $resources,
            'next_event' => $nextEvent
        ];
    }
}