<?php
declare(strict_types=1);

namespace HnCourses\Repository;

use PDO;

final class CourseRepository
{
    public function __construct(
        private PDO $db
    ) {}

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM hn_course_courses
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute(['id' => $id]);

        $course = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        return $course ? $this->normalize($course) : null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM hn_course_courses
            WHERE slug = :slug
              AND is_active = 1
            LIMIT 1
        ");

        $stmt->execute(['slug' => $slug]);

        $course = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        return $course ? $this->normalize($course) : null;
    }

    public function findAll(): array
    {
        $stmt = $this->db->query("
            SELECT *
            FROM hn_course_courses
            ORDER BY title ASC, id ASC
        ");

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map([$this, 'normalize'], $rows);
    }

    public function findAllActive(): array
    {
        $stmt = $this->db->query("
            SELECT *
            FROM hn_course_courses
            WHERE is_active = 1
            ORDER BY title ASC, id ASC
        ");

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map([$this, 'normalize'], $rows);
    }

    public function getEvents(int $courseId): array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM hn_course_events
            WHERE course_id = :id
            ORDER BY start_datetime ASC
        ");

        $stmt->execute(['id' => $courseId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * 🔥 NY: Hent én spesifikk kursøkt (klasseside)
     */
    public function findClassById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM hn_course_events
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute(['id' => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * 🔥 REN RESOURCE PIPELINE
     */
    public function getResources(int $courseId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                r.id,

                COALESCE(
                    r.original_filename,
                    r.stored_filename,
                    'Ressurs'
                ) AS title,

                r.resource_type AS type,

                CASE 
                    WHEN r.resource_type = 'youtube' AND r.youtube_id IS NOT NULL
                        THEN CONCAT('https://www.youtube.com/watch?v=', r.youtube_id)

                    WHEN r.resource_type = 'link'
                        THEN r.external_url

                    WHEN r.resource_type = 'file'
                        THEN CONCAT('/hn_courses/uploads/', r.stored_filename)

                    ELSE NULL
                END AS url,

                map.category,
                map.sort_order

            FROM hn_course_resource_map map

            JOIN hn_resources r
                ON r.id = map.resource_id

            WHERE map.course_id = :course_id
              AND map.is_active = 1

            ORDER BY map.sort_order ASC
        ");

        $stmt->execute(['course_id' => $courseId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getPartners(int $courseId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                p.id,
                p.name,
                p.logo_path,
                p.website_url,
                map.sort_order

            FROM hn_course_partner_map map

            JOIN hn_course_partners p
                ON p.id = map.partner_id

            WHERE map.course_id = :course_id
              AND map.is_active = 1
              AND p.is_active = 1

            ORDER BY map.sort_order ASC, p.name ASC
        ");

        $stmt->execute([
            'course_id' => $courseId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function normalize(array $course): array
    {
        if (empty($course['book_slug'])) {
            $course['book_slug'] = $course['slug'];
        }

        return $course;
    }
}