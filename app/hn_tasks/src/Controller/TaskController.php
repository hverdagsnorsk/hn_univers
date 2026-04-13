<?php
declare(strict_types=1);

namespace HnTasks\Controller;

use PDO;

class TaskController
{
    public function __construct(private PDO $db) {}

    /* ==========================================================
       HENT OPPGAVER (FLERE FILER PER NIVÅ)
    ========================================================== */

    public function getTasks(string $level): array
    {
        $dir = dirname(__DIR__, 2) . "/data/{$level}";

        if (!is_dir($dir)) {
            return ['error' => 'Level not found'];
        }

        $files = glob($dir . "/*.json");

        $allTasks = [];
        $options = [];
        $title = "Oppgaver";
        $instruction = "Velg riktig alternativ";

        foreach ($files as $file) {

            $json = json_decode(file_get_contents($file), true);

            if (!$json || !isset($json['tasks'])) {
                continue;
            }

            $title = $json['title'] ?? $title;
            $instruction = $json['instruction'] ?? $instruction;

            if (isset($json['options'])) {
                $options = array_unique(array_merge($options, $json['options']));
            }

            foreach ($json['tasks'] as $t) {
                $allTasks[] = $t;
            }
        }

        if (empty($allTasks)) {
            return ['error' => 'No tasks found'];
        }

        shuffle($allTasks);

        $selected = array_slice($allTasks, 0, 10);

        foreach ($selected as $i => &$task) {
            $task['id'] = $i + 1;
        }

        return [
            'title' => $title,
            'instruction' => $instruction,
            'options' => $options,
            'tasks' => $selected
        ];
    }

    /* ==========================================================
       LAGRING
    ========================================================== */

    public function saveResult(array $data): void
    {
        $this->logAnswers($data);
        $this->updateProgress($data);
    }

    private function logAnswers(array $data): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO hn_task_answers
            (email, level, task_id, user_answer, correct_answer, is_correct, created_at)
            VALUES (:email, :level, :task_id, :user_answer, :correct_answer, :is_correct, NOW())
        ");

        foreach ($data['answers'] as $i => $answer) {

            $correct = $data['correct'][$i] ?? null;

            $stmt->execute([
                'email' => $_SESSION['user']['email'],
                'level' => $data['level'],
                'task_id' => $data['task_ids'][$i] ?? 0,
                'user_answer' => $answer,
                'correct_answer' => $correct,
                'is_correct' => ($answer === $correct) ? 1 : 0
            ]);
        }
    }

    /* ==========================================================
       PROGRESJON (STRENGERE)
    ========================================================== */

    private function updateProgress(array $data): void
    {
        $completed = ($data['score'] >= 8) ? 1 : 0;

        $stmt = $this->db->prepare("
            INSERT INTO hn_task_progress
            (email, level, last_score, completed, updated_at)
            VALUES (:email, :level, :score, :completed, NOW())
            ON DUPLICATE KEY UPDATE
            last_score = :score,
            completed = :completed,
            updated_at = NOW()
        ");

        $stmt->execute([
            'email' => $_SESSION['user']['email'],
            'level' => $data['level'],
            'score' => $data['score'],
            'completed' => $completed
        ]);
    }
}