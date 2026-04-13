<?php
declare(strict_types=1);

final class V8AdaptiveSelector
{

    public static function selectTasks(PDO $pdo,int $textId,string $email,int $limit=5): array
    {

        /*
        --------------------------------------------------
        1. Finn brukerens nivå
        --------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT
                t.difficulty,
                AVG(r.is_correct) AS score,
                COUNT(*) attempts
            FROM responses r
            JOIN attempts a ON a.id = r.attempt_id
            JOIN tasks t ON t.id = r.task_id
            WHERE a.participant_email = ?
            AND t.text_id = ?
            GROUP BY t.difficulty
        ");

        $stmt->execute([$email,$textId]);

        $level = 2;

        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){

            $difficulty = (int)$row["difficulty"];
            $score      = (float)$row["score"];
            $attempts   = (int)$row["attempts"];

            /*
            Krev minimum 3 oppgaver før nivåjustering
            */

            if($attempts < 3){
                continue;
            }

            if($score > 0.80){
                $level = max($level,$difficulty + 1);
            }

            if($score < 0.40){
                $level = min($level,$difficulty - 1);
            }

        }

        $level = max(1,min(4,$level));

        $min = max(1,$level - 1);
        $max = min(4,$level + 1);


        /*
        --------------------------------------------------
        2. Hent oppgaver rundt nivå
        --------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT
                t.id,
                t.task_type,
                t.difficulty,
                t.payload_json
            FROM tasks t
            WHERE t.text_id = ?
            AND t.difficulty BETWEEN ? AND ?
            AND t.status = 'approved'
            AND NOT EXISTS (

                SELECT 1
                FROM responses r
                JOIN attempts a ON a.id = r.attempt_id
                WHERE r.task_id = t.id
                AND a.participant_email = ?

            )
            ORDER BY t.difficulty, t.id
            LIMIT $limit
        ");

        $stmt->execute([
            $textId,
            $min,
            $max,
            $email
        ]);

        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);


        /*
        --------------------------------------------------
        3. FALLBACK #1
        Alle vanskelighetsgrader
        --------------------------------------------------
        */

        if(!$tasks){

            $stmt = $pdo->prepare("
                SELECT
                    t.id,
                    t.task_type,
                    t.difficulty,
                    t.payload_json
                FROM tasks t
                WHERE t.text_id = ?
                AND t.status = 'approved'
                AND NOT EXISTS (

                    SELECT 1
                    FROM responses r
                    JOIN attempts a ON a.id = r.attempt_id
                    WHERE r.task_id = t.id
                    AND a.participant_email = ?

                )
                ORDER BY t.difficulty,t.id
                LIMIT $limit
            ");

            $stmt->execute([$textId,$email]);

            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        }


        /*
        --------------------------------------------------
        4. FALLBACK #2
        Hvis brukeren har gjort ALLE oppgaver
        → start på nytt tilfeldig
        --------------------------------------------------
        */

        if(!$tasks){

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    task_type,
                    difficulty,
                    payload_json
                FROM tasks
                WHERE text_id = ?
                AND status = 'approved'
                ORDER BY RAND()
                LIMIT $limit
            ");

            $stmt->execute([$textId]);

            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        }


        return $tasks;

    }

}