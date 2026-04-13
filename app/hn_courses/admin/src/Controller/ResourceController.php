<?php
namespace HnCourses\Admin\Controller;

use HnCourses\Admin\Service\UploadService;

class ResourceController
{
    public function index()
    {
        $pdo = db('courses');

        $resources = $pdo->query("
            SELECT id, original_filename, stored_filename, resource_type
            FROM hn_resources
            ORDER BY id DESC
            LIMIT 100
        ")->fetchAll(\PDO::FETCH_ASSOC);

        require dirname(__DIR__, 2) . '/templates/resources.php';
    }

    public function upload()
    {
        if (!isset($_FILES['file'])) {
            die("Ingen fil");
        }

        $service = new UploadService();
        $filename = $service->upload($_FILES['file']);

        $pdo = db('courses');

        $stmt = $pdo->prepare("
            INSERT INTO hn_resources (
                title,
                stored_filename,
                original_filename,
                resource_type,
                uniq_hash
            ) VALUES (?, ?, ?, 'file', ?)
        ");

        $stmt->execute([
            $_FILES['file']['name'],
            $filename,
            $_FILES['file']['name'],
            hash('sha256', $filename)
        ]);

        header("Location: ?action=resources");
        exit;
    }
}
