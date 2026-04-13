<?php
declare(strict_types=1);

namespace HnCourses\Repository;

use PDO;

final class ResourceRepository
{
    public function __construct(private PDO $pdo) {}

    /* =========================
       TAB CONFIG
    ========================= */
    private function getTabConfig(string $tab): array
    {
        return match ($tab) {
            'leksjon' => [
                'category' => 'leksjon',
                'types' => ['file']
            ],
            'documents' => [
                'category' => 'documents',
                'types' => ['file']
            ],
            'lesson_video' => [
                'category' => 'lesson_video',
                'types' => ['video']
            ],
            'video' => [
                'category' => 'video',
                'types' => ['video', 'youtube']
            ],
            'links' => [
                'category' => 'links',
                'types' => ['link']
            ],
            default => [
                'category' => 'leksjon',
                'types' => ['file']
            ]
        };
    }

    /* =========================
       LIBRARY
    ========================= */
    public function getLibraryForTab(int $courseId, string $tab): array
    {
        $config = $this->getTabConfig($tab);

        $placeholders = implode(',', array_fill(0, count($config['types']), '?'));

        $stmt = $this->pdo->prepare("
            SELECT *
            FROM hn_resources
            WHERE (course_id = ? OR is_shared = 1)
            AND resource_type IN ($placeholders)
            ORDER BY id DESC
        ");

        $stmt->execute(array_merge([$courseId], $config['types']));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
       ATTACHED
    ========================= */
    public function getAttachedForTab(int $courseId, string $tab): array
    {
        $config = $this->getTabConfig($tab);

        $stmt = $this->pdo->prepare("
            SELECT 
                m.*,
                r.original_filename,
                r.resource_type,
                r.youtube_id,
                r.external_url
            FROM hn_course_resource_map m
            JOIN hn_resources r ON r.id = m.resource_id
            WHERE m.course_id = ?
            AND m.category = ?
            ORDER BY m.sort_order
        ");

        $stmt->execute([$courseId, $config['category']]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
       CREATE RESOURCE (FIXED)
    ========================= */
    public function createResource(array $data): int
{
    // 🔥 FIX: handle null + empty string
    $uniqHash = $data['uniq_hash'] ?? null;

    if (empty($uniqHash)) {
        $uniqHash = bin2hex(random_bytes(16));
    }

    $stmt = $this->pdo->prepare("
        INSERT INTO hn_resources (
            title,
            stored_filename,
            original_filename,
            mime_type,
            file_size,
            course_id,
            is_shared,
            resource_type,
            external_url,
            youtube_id,
            uniq_hash
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $data['title'] ?? null,
        $data['stored_filename'] ?? null,
        $data['original_filename'] ?? null,
        $data['mime_type'] ?? null,
        $data['file_size'] ?? null,
        $data['course_id'] ?? null,
        $data['is_shared'] ?? 1,
        $data['resource_type'],
        $data['external_url'] ?? null,
        $data['youtube_id'] ?? null,
        $uniqHash
    ]);

    return (int)$this->pdo->lastInsertId();
}
    /* =========================
       ATTACH
    ========================= */
    public function attachToCourse(
        int $courseId,
        int $resourceId,
        string $category
    ): void {

        // duplicate check
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM hn_course_resource_map
            WHERE course_id = ? AND resource_id = ? AND category = ?
        ");
        $stmt->execute([$courseId, $resourceId, $category]);

        if ($stmt->fetchColumn() > 0) {
            return;
        }

        // sort order
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(MAX(sort_order),0)+1 
            FROM hn_course_resource_map 
            WHERE course_id = ? AND category = ?
        ");
        $stmt->execute([$courseId, $category]);
        $sortOrder = (int)$stmt->fetchColumn();

        // insert
        $stmt = $this->pdo->prepare("
            INSERT INTO hn_course_resource_map
            (course_id, resource_id, category, sort_order, is_active)
            VALUES (?, ?, ?, ?, 1)
        ");

        $stmt->execute([
            $courseId,
            $resourceId,
            $category,
            $sortOrder
        ]);
    }

    /* =========================
       DELETE
    ========================= */
    public function deleteFromCourse(int $mapId): void
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM hn_course_resource_map
            WHERE id = ?
        ");

        $stmt->execute([$mapId]);
    }

    /* =========================
       CATEGORY
    ========================= */
    public function getCategory(string $tab): string
    {
        return $this->getTabConfig($tab)['category'];
    }
}