<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/app/hn_core/inc/bootstrap.php';

use HnCore\Database\DatabaseManager;

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$pdo = DatabaseManager::get('main');

$stmt = $pdo->prepare("
    INSERT INTO hn_users (name, email, created_at)
    VALUES (:name, :email, NOW())
    ON DUPLICATE KEY UPDATE name = :name
");

$stmt->execute([
    'name' => $data['name'],
    'email' => $data['email']
]);

echo json_encode(['status' => 'ok']);