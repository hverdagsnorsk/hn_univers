<?php
namespace HnCourses\Admin\Controller;

class UploadController
{
    public function upload(): void
    {
        $uploadDir = '/home/7/h/hverdagsnorsk/www/hn_courses/uploads/';

        if (!isset($_FILES['file'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No file']);
            return;
        }

        $file = $_FILES['file'];

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newName = uniqid('file_', true) . '.' . $ext;

        $target = $uploadDir . $newName;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            http_response_code(500);
            echo json_encode(['error' => 'Upload failed']);
            return;
        }

        $pdo = db('courses');

        $stmt = $pdo->prepare("
            INSERT INTO hn_resources 
            (stored_filename, original_filename, resource_type)
            VALUES (?, ?, 'file')
        ");

        $stmt->execute([$newName, $file['name']]);

        echo json_encode([
            'success' => true,
            'id' => $pdo->lastInsertId(),
            'name' => $file['name']
        ]);
    }
}