<?php
declare(strict_types=1);

require_once __DIR__.'/../engine/TextExtractor.php';
require_once __DIR__.'/../engine/TaskEngine.php';

require_once __DIR__.'/../../hn_core/inc/bootstrap.php';

$book = $_GET["book"] ?? "";
$text = $_GET["text"] ?? "";

$file = __DIR__."/../books/$book/texts/$text.html";

if(!file_exists($file)){
    exit("Text not found");
}

$html = file_get_contents($file);

$textContent = TextExtractor::extract($html);

$engine = new TaskEngine($pdo_lex);

$analysis = $engine->analyzeText($textContent);

$tasks = $engine->generateTasks($analysis);

header("Content-Type: application/json");

echo json_encode([
    "analysis"=>$analysis,
    "tasks"=>$tasks
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);