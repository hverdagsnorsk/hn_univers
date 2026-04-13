<?php
declare(strict_types=1);

namespace HnCourses\Service;

use HnCourses\Repository\ResourceRepository;

final class ResourceService
{
    public function __construct(
        private ResourceRepository $repo
    ) {}

    /* =========================
       📁 FILE UPLOAD
    ========================= */

    public function uploadFile(array $file, int $courseId, string $tab): int
    {
        if (empty($file['tmp_name'])) {
            throw new \RuntimeException('No file uploaded');
        }

        $originalName = basename($file['name']);
        $storedName   = uniqid('', true) . '_' . $originalName;

        $isShared = $tab !== 'leksjon';
        $folder   = $isShared ? 'shared' : "course_{$courseId}";

        $uploadDir = $this->getUploadDir($folder);

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $targetPath = $uploadDir . '/' . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new \RuntimeException('Upload failed');
        }

        return $this->repo->createResource([
            'title'             => $originalName,
            'stored_filename'  => $storedName,
            'original_filename'=> $originalName,
            'mime_type'        => $file['type'] ?? '',
            'file_size'        => $file['size'] ?? 0,
            'course_id'        => $isShared ? null : $courseId,
            'is_shared'        => $isShared,
            'resource_type'    => $this->resolveType($tab)
        ]);
    }

    /* =========================
       🔗 LINK
    ========================= */

    public function createLink(string $url, string $title = ''): int
    {
        return $this->repo->createResource([
            'title' => $title ?: $url,
            'external_url' => $url,
            'resource_type' => 'link',
            'is_shared' => 1
        ]);
    }

    /* =========================
       🎬 YOUTUBE
    ========================= */

    public function createYoutube(string $url): int
    {
        preg_match('/(?:v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $m);

        if (empty($m[1])) {
            throw new \RuntimeException('Invalid YouTube URL');
        }

        return $this->repo->createResource([
            'title' => $url,
            'resource_type' => 'youtube',
            'youtube_id' => $m[1],
            'is_shared' => 1
        ]);
    }

    /* =========================
       🧠 HELPERS
    ========================= */

    private function resolveType(string $tab): string
    {
        return match ($tab) {
            'lesson_video', 'video' => 'video',
            default => 'file'
        };
    }

    private function getUploadDir(string $folder): string
    {
        return dirname(__DIR__, 2) . "/uploads/{$folder}";
    }
}