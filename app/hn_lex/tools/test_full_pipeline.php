<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/contracts/LexContract.php';
require_once __DIR__ . '/../inc/lex_save.php';

echo "=== TESTER FULL PIPELINE (uten AI) ===\n";

$aiData = [
  'lemma' => 'snakke',
  'ordklasse' => 'verb',
  'language' => 'no',
  'grammar' => [
    'infinitive' => 'snakke',
    'present'    => 'snakker',
    'past'       => 'snakket',
    'perfect'    => 'har snakket',
    'passive'    => 'snakkes',
  ],
  'explanations' => [
    'A2' => [
      'forklaring' => 'Å bruke ord for å si noe.',
      'example'    => 'Jeg snakker norsk.',
      'source'     => 'manual',
    ],
  ],
];

$contract = LexContract::fromAI($aiData);
$id = saveLexEntry($pdo, $contract);

echo "Lagret entry_id={$id}\n";

$stmt = $pdo->prepare("SELECT * FROM lex_verbs WHERE entry_id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) die("FEIL: ingen rad i lex_verbs\n");
if (empty($row['present'])) die("FEIL: present er tom i lex_verbs\n");

echo "SUCCESS: grammatikk er lagret i lex_verbs\n";
print_r($row);
