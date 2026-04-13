<?php
declare(strict_types=1);

namespace HnCourses\Repository;

use PDO;
use Throwable;

final class EventRepository
{
    public function __construct(private PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * 🔥 ADMIN – ALLE events
     */
    public function forCourse(int $courseId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                id,
                course_id,
                start_datetime,
                end_datetime,
                location,
                meeting_url,
                description,
                summary,
                sort_order
            FROM hn_course_events
            WHERE course_id = :course_id
            ORDER BY 
                sort_order ASC,
                start_datetime ASC
        ");

        $stmt->execute([
            'course_id' => $courseId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 🌍 FRONTEND – kun kommende
     */
    public function upcomingForCourse(int $courseId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM hn_course_events
            WHERE 
                course_id = :course_id
                AND start_datetime >= NOW()
            ORDER BY 
                start_datetime ASC
        ");

        $stmt->execute([
            'course_id' => $courseId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 🔁 BACKWARDS COMPATIBILITY
     */
    public function findByCourse(int $courseId): array
    {
        return $this->upcomingForCourse($courseId);
    }

    /**
     * ➕ Opprett enkelt event
     */
    public function create(int $courseId, array $data): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO hn_course_events (
                course_id,
                start_datetime,
                end_datetime,
                location,
                meeting_url,
                description,
                summary,
                sort_order
            ) VALUES (
                :course_id,
                :start_datetime,
                :end_datetime,
                :location,
                :meeting_url,
                :description,
                :summary,
                :sort_order
            )
        ");

        $stmt->execute([
            'course_id'      => $courseId,
            'start_datetime' => $data['start_datetime'],
            'end_datetime'   => $data['end_datetime'] ?: null,
            'location'       => $data['location'] ?? null,
            'meeting_url'    => $data['meeting_url'] ?? null,
            'description'    => $data['description'] ?? null,
            'summary'        => $data['summary'] ?? null,
            'sort_order'     => $this->nextSortOrderForCourse($courseId),
        ]);
    }

    /**
     * ❌ Slett
     */
    public function delete(int $courseId, int $id): void
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM hn_course_events
            WHERE id = :id AND course_id = :course_id
        ");

        $stmt->execute([
            'id' => $id,
            'course_id' => $courseId
        ]);
    }

    /**
     * 📥 BULK INSERT (ICS / batch)
     */
    public function bulkInsertSafe(int $courseId, array $events): int
    {
        if ($courseId <= 0 || empty($events)) {
            return 0;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO hn_course_events (
                course_id,
                start_datetime,
                end_datetime,
                location,
                meeting_url,
                description,
                summary,
                sort_order
            ) VALUES (
                :course_id,
                :start_datetime,
                :end_datetime,
                :location,
                :meeting_url,
                :description,
                :summary,
                :sort_order
            )
        ");

        $this->pdo->beginTransaction();

        $inserted = 0;

        try {
            $sortOrder = $this->nextSortOrderForCourse($courseId);

            foreach ($events as $event) {
                try {
                    $stmt->execute([
                        'course_id'      => $courseId,
                        'start_datetime' => $event['start'] ?? null,
                        'end_datetime'   => $event['end'] ?? null,
                        'location'       => $event['location'] ?? null,
                        'meeting_url'    => $event['url'] ?? null,
                        'description'    => $event['description'] ?? null,
                        'summary'        => $event['summary'] ?? null,
                        'sort_order'     => $sortOrder++,
                    ]);

                    $inserted++;

                } catch (Throwable $e) {
                    error_log("ICS insert failed: " . $e->getMessage());
                }
            }

            $this->pdo->commit();

        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return $inserted;
    }

    /**
     * 🔢 Neste sort_order
     */
    private function nextSortOrderForCourse(int $courseId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(MAX(sort_order), -1) + 1
            FROM hn_course_events
            WHERE course_id = :course_id
        ");

        $stmt->execute([
            'course_id' => $courseId
        ]);

        return (int)$stmt->fetchColumn();
    }
}