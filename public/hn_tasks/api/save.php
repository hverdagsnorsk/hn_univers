<?php
declare(strict_types=1);

session_start();

require_once dirname(__DIR__, 3) . '/app/hn_core/inc/bootstrap.php';

use HnCore\Database\DatabaseManager;
use HnTasks\Controller\TaskController;

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not_logged_in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['error' => 'invalid_data']);
    exit;
}

if (!isset($data['level'], $data['score'], $data['answers'])) {
    echo json_encode(['error' => 'missing_fields']);
    exit;
}

$pdo = DatabaseManager::get('main');

$controller = new TaskController($pdo);

$controller->saveResult($data);

echo json_encode(['success' => true]);