<?php
declare(strict_types=1);

namespace HnCourses\Admin\Controller;

use HnCourses\Repository\EventRepository;
use HnCourses\Admin\Service\IcsParser;
use RuntimeException;

final class ScheduleController
{
    private EventRepository $repo;

    public function __construct()
    {
        $this->repo = new EventRepository(db('courses'));
    }

    /**
     * ➕ Lagre event (fra course_content)
     */
    public function save(): void
    {
        $pdo = db('courses');

        $courseId = (int)($_POST['course_id'] ?? 0);

        if ($courseId <= 0) {
            throw new RuntimeException('Mangler course_id');
        }

        $stmt = $pdo->prepare("
            INSERT INTO hn_course_events
            (course_id, start_datetime, end_datetime, location, meeting_url, summary)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $courseId,
            $_POST['start'] ?? null,
            $_POST['end'] ?: null,
            $_POST['location'] ?: null,
            $_POST['meeting_url'] ?: null,
            $_POST['summary'] ?: null,
        ]);

        $this->redirect($courseId);
    }

    /**
     * ❌ Slett event
     */
    public function delete(int $courseId, int $id): void
    {
        $pdo = db('courses');

        $stmt = $pdo->prepare("
            DELETE FROM hn_course_events
            WHERE id = ? AND course_id = ?
        ");

        $stmt->execute([$id, $courseId]);

        $this->redirect($courseId);
    }

    /**
     * 📥 ICS import (koblet til repository)
     */
    public function importIcs(int $courseId): void
    {
        if ($courseId <= 0) {
            throw new RuntimeException('Mangler course_id');
        }

        if (empty($_FILES['ics']['tmp_name'])) {
            throw new RuntimeException('Ingen ICS-fil lastet opp');
        }

        $icsContent = file_get_contents($_FILES['ics']['tmp_name']);

        if ($icsContent === false) {
            throw new RuntimeException('Kunne ikke lese ICS-fil');
        }

        $events = IcsParser::parse($icsContent);

        $inserted = $this->repo->bulkInsertSafe($courseId, $events);

        error_log("ICS import OK: {$inserted} events");

        $this->redirect($courseId);
    }

    /* =========================
       🔁 Redirect
    ========================= */
    private function redirect(int $courseId): void
    {
        header("Location: ?action=course_content&course_id={$courseId}&tab=schedule");
        exit;
    }
}