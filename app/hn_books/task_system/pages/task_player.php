<?php
require_once __DIR__.'/../engine/AdaptiveEngine.php';

$book=$_GET['book'];
$text=$_GET['text'];

$jsonPath=$_SERVER['DOCUMENT_ROOT']
."/hn_books/task_system/data/$book/$text.json";

$data=json_decode(file_get_contents($jsonPath),true);

$level=$_SESSION['task_level'] ?? 1;

$tasks=AdaptiveEngine::selectTasks(
$data["tasks"],
$level,
10
);