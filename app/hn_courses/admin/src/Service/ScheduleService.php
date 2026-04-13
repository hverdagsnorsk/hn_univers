<?php
declare(strict_types=1);

namespace HnCourses\Admin\Service;

use HnCourses\Repository\EventRepository;
use Throwable;

final class ScheduleService
{
    private EventRepository $repo;

    public function __construct()
    {
        $pdo = db('courses');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

        $this->repo = new EventRepository($pdo);
    }

    public function importIcs(int $courseId, string $ics): array
    {
        $events = IcsParser::parse($ics, 'Europe/Oslo');

        if (!$events) {
            return [
                'inserted' => 0,
                'skipped'  => 0,
                'errors'   => ['No events parsed from ICS']
            ];
        }

        $cleanEvents = [];
        $errors = [];
        $skipped = 0;

        foreach ($events as $i => $event) {
            try {
                $normalized = $this->normalizeEvent($event);

                if (!$normalized) {
                    $skipped++;
                    continue;
                }

                $cleanEvents[] = $normalized;

            } catch (Throwable $e) {
                $errors[] = "Event #$i error: " . $e->getMessage();
            }
        }

        $inserted = $this->repo->bulkInsertSafe($courseId, $cleanEvents);

        return [
            'inserted' => $inserted,
            'skipped'  => $skipped,
            'errors'   => $errors
        ];
    }

    private function normalizeEvent(array $event): ?array
    {
        if (empty($event['start'])) {
            return null;
        }

        $start = $this->normalizeDate($event['start']);
        $end   = isset($event['end']) && $event['end'] !== null
            ? $this->normalizeDate($event['end'])
            : null;

        return [
            'start'       => $start,
            'end'         => $end,
            'summary'     => $event['summary'] ?? '',
            'location'    => $event['location'] ?? '',
            'description' => $event['description'] ?? '',
        ];
    }

    private function normalizeDate(string $date): string
    {
        $ts = strtotime($date);

        if ($ts === false) {
            throw new \RuntimeException("Invalid date: $date");
        }

        return date('Y-m-d H:i:s', $ts);
    }
}