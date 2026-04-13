<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/hn_core/inc/bootstrap.php';

use HnCore\Database\DatabaseManager;
use HnLex\Service\LookupService;
use HnLex\Service\SenseGenerationService;
use HnLex\Service\LexStorageService;

/* ==========================================================
   INIT
========================================================== */

$pdo = DatabaseManager::get('lex');

/* ==========================================================
   DEPENDENCIES
========================================================== */

$storage = new LexStorageService($pdo);

$senseGenerator = new SenseGenerationService(
    $pdo,
    $storage,
    null // embedding AV for kontrollert test
);

$lookup = new LookupService(
    $pdo,
    $senseGenerator,
    null
);

/* ==========================================================
   TEST CASES (FLERE – VIKTIG)
========================================================== */

$tests = [
    [
        'word' => 'går',
        'prev' => 'jeg',
        'next' => 'til',
        'sentence' => 'jeg går til butikken'
    ],
    [
        'word' => 'går',
        'prev' => 'han',
        'next' => 'hjem',
        'sentence' => 'han går hjem'
    ],
    [
        'word' => 'går',
        'prev' => 'vi',
        'next' => 'på',
        'sentence' => 'vi går på skole'
    ]
];

/* ==========================================================
   RUN
========================================================== */

echo "<pre>";

foreach ($tests as $i => $context) {

    echo "============================\n";
    echo "TEST #" . ($i + 1) . "\n";
    echo "Context: " . json_encode($context, JSON_UNESCAPED_UNICODE) . "\n\n";

    $result = $lookup->lookup(
        $context['word'],
        'nb',
        'A2',
        $context
    );

    print_r($result);
    echo "\n";
}

echo "============================\n";
echo "DONE\n";

echo "</pre>";