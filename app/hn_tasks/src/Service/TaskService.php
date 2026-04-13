<?php
declare(strict_types=1);

namespace HnTasks\Service;

final class TaskService
{
    public function loadSet(string $level): array
    {
        $file = HN_ROOT . "/app/hn_tasks/data/{$level}.json";

        if (!file_exists($file)) {
            return [];
        }

        $data = json_decode(file_get_contents($file), true);

        if (!is_array($data)) {
            return [];
        }

        // 🔥 RANDOMISER OPPGAVER
        if (isset($data['tasks'])) {
            shuffle($data['tasks']);
            $data['tasks'] = array_slice($data['tasks'], 0, 10);
        }

        return $data;
    }
}