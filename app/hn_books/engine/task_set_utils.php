<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------
| hn_books/engine/task_set_utils.php
| Felles hjelpefunksjoner for oppgavesett (publiseringsregler m.m.)
|
| Løsning B:
|  - Én felles regelmotor brukt både i admin og i deltakerspiller
|  - "Publisert" krever:
|      1) task_sets.active = 1
|      2) minst én oppgave i settet med status = 'approved'
|      3) minst én oppgave av type 'writing' i settet med status = 'approved'
|
| MERK:
|  - Denne fila skal kunne inkluderes både fra hn_books og hn_admin
|--------------------------------------------------------------------
*/

function tsu_fetch_task_set(PDO $pdo, int $setId): ?array
{
    $stmt = $pdo->prepare("
        SELECT
            s.id,
            s.text_id,
            s.title,
            s.description,
            s.active,
            s.created_at
        FROM task_sets s
        WHERE s.id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $setId]);
    $set = $stmt->fetch(PDO::FETCH_ASSOC);

    return $set ?: null;
}

function tsu_task_set_counts(PDO $pdo, int $setId): array
{
    // Total koblinger
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS c
        FROM task_set_items i
        WHERE i.task_set_id = :sid
    ");
    $stmt->execute(['sid' => $setId]);
    $total = (int)$stmt->fetchColumn();

    // Approved totalt
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS c
        FROM task_set_items i
        JOIN tasks t ON t.id = i.task_id
        WHERE i.task_set_id = :sid
          AND t.status = 'approved'
    ");
    $stmt->execute(['sid' => $setId]);
    $approved = (int)$stmt->fetchColumn();

    // Approved writing
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS c
        FROM task_set_items i
        JOIN tasks t ON t.id = i.task_id
        WHERE i.task_set_id = :sid
          AND t.status = 'approved'
          AND t.task_type = 'writing'
    ");
    $stmt->execute(['sid' => $setId]);
    $approvedWriting = (int)$stmt->fetchColumn();

    return [
        'total' => $total,
        'approved_total' => $approved,
        'approved_writing' => $approvedWriting,
    ];
}

function tsu_task_set_publish_status(PDO $pdo, int $setId): array
{
    $set = tsu_fetch_task_set($pdo, $setId);

    if (!$set) {
        return [
            'found' => false,
            'published' => false,
            'reasons' => ['Oppgavesett ikke funnet.'],
            'set' => null,
            'counts' => [
                'total' => 0,
                'approved_total' => 0,
                'approved_writing' => 0,
            ],
        ];
    }

    $counts = tsu_task_set_counts($pdo, $setId);

    $reasons = [];

    if ((int)$set['active'] !== 1) {
        $reasons[] = 'Oppgavesettet er ikke aktivt (active=0).';
    }

    if ((int)$counts['approved_total'] <= 0) {
        $reasons[] = 'Oppgavesettet har ingen godkjente oppgaver (status=approved).';
    }

    if ((int)$counts['approved_writing'] <= 0) {
        $reasons[] = 'Oppgavesettet mangler minst én godkjent skriveoppgave (task_type=writing).';
    }

    $published = (
        (int)$set['active'] === 1
        && (int)$counts['approved_total'] > 0
        && (int)$counts['approved_writing'] > 0
    );

    return [
        'found' => true,
        'published' => $published,
        'reasons' => $reasons,
        'set' => $set,
        'counts' => $counts,
    ];
}

function tsu_participant_link(int $setId): string
{
    // Standard deltakerlenke (absolutt path)
    return '/hn_books/engine/tasks_play.php?set_id=' . $setId;
}
