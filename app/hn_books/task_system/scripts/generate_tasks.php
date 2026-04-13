<?php
declare(strict_types=1);

$root = dirname(__DIR__,3);

require_once $root.'/hn_core/inc/bootstrap.php';
require_once $root.'/hn_books/task_system/engine/TaskEngine.php';

echo "\n==============================\n";
echo "HN TASK GENERATOR\n";
echo "==============================\n\n";

/* --------------------------------------------------
DB
-------------------------------------------------- */

$pdoMain = db('main');
$pdoLex  = db('lex');

/* --------------------------------------------------
HENT TEKSTER
-------------------------------------------------- */

$stmt = $pdoMain->query("
SELECT
    id,
    book_key,
    text_key
FROM texts
WHERE active = 1
ORDER BY id
");

$texts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Fant ".count($texts)." tekster\n\n";

/* --------------------------------------------------
TASK ENGINE
-------------------------------------------------- */

$engine = new TaskEngine($pdoLex);

/* --------------------------------------------------
PREPARE INSERT
-------------------------------------------------- */

$insert = $pdoMain->prepare("
INSERT INTO tasks
(
    text_id,
    task_type,
    status,
    section,
    difficulty,
    tags,
    question,
    answer,
    options,
    source_sentence,
    lemma,
    pos,
    generator,
    payload_json
)
VALUES
(
    :text_id,
    :task_type,
    'draft',
    :section,
    :difficulty,
    :tags,
    :question,
    :answer,
    :options,
    :sentence,
    :lemma,
    :pos,
    'task_engine',
    :payload
)
");

/* --------------------------------------------------
CHECK DUPLICATE
-------------------------------------------------- */

$exists = $pdoMain->prepare("
SELECT id
FROM tasks
WHERE text_id = :text_id
AND payload_json = :payload
LIMIT 1
");

/* --------------------------------------------------
OPPGAVETYPER VI TILLATER
-------------------------------------------------- */

$allowedTypes = [

    'fill',
    'multiple_choice',
    'true_false',
    'word_order',
    'preposition',
    'verb_inflection',
    'noun_inflection',
    'subjunction',
    'correct_error',
    'writing'

];

/* --------------------------------------------------
GENERATOR LOOP
-------------------------------------------------- */

$total = 0;
$skipped = 0;

foreach ($texts as $row) {

    $path =
        $root.
        "/hn_books/books/".
        $row['book_key'].
        "/texts/".
        $row['text_key'].
        ".html";

    if (!file_exists($path)) {

        echo "⚠ Fil finnes ikke: $path\n";
        continue;

    }

    echo "Leser tekst $path\n";

    $html = file_get_contents($path);

    if (!$html) {
        continue;
    }

    $tasks = $engine->generateFromText($html);

    if (!$tasks) {
        continue;
    }

    $section = 1;

    foreach ($tasks as $task) {

        $type = $task['type'] ?? 'generic';

        /* -------------------------
        FILTER OPPGAVETYPER
        ------------------------- */

        if (!in_array($type,$allowedTypes,true)) {
            continue;
        }

        /* -------------------------
        difficulty heuristikk
        ------------------------- */

        $difficulty = match($type) {

            'true_false' => 1,

            'fill',
            'multiple_choice',
            'preposition',
            'word_order' => 2,

            'verb_inflection',
            'noun_inflection',
            'subjunction',
            'correct_error' => 3,

            'writing' => 4,

            default => 2
        };

        $payload = json_encode($task,JSON_UNESCAPED_UNICODE);

        /* -------------------------
        DUPLIKAT-SJEKK
        ------------------------- */

        $exists->execute([
            ':text_id' => $row['id'],
            ':payload' => $payload
        ]);

        if ($exists->fetch()) {

            $skipped++;
            continue;

        }

        /* -------------------------
        INSERT
        ------------------------- */

        $insert->execute([

            ':text_id'   => $row['id'],
            ':task_type' => $type,
            ':section'   => $section,
            ':difficulty'=> $difficulty,

            ':tags'      => $task['lemma'] ?? null,

            ':question'  => $task['question'] ?? null,
            ':answer'    => $task['answer'] ?? null,

            ':options'   =>
                isset($task['options'])
                ? json_encode($task['options'],JSON_UNESCAPED_UNICODE)
                : null,

            ':sentence'  => $task['sentence'] ?? null,

            ':lemma'     => $task['lemma'] ?? null,
            ':pos'       => $task['pos'] ?? null,

            ':payload'   => $payload

        ]);

        $section++;
        $total++;

    }

}

echo "\n==============================\n";
echo "Oppgaver generert: $total\n";
echo "Duplikater hoppet over: $skipped\n";
echo "==============================\n\n";