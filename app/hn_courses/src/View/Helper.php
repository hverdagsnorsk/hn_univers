<?php
declare(strict_types=1);

namespace HnCourses\View;

final class Helper
{
    public static function h(mixed $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    public static function resourceLink(array $r, int $courseId): string
    {
        $type = $r['resource_type'] ?? '';

        if ($type === 'link') {
            return '<a href="' . self::h($r['external_url'] ?? '') . '" target="_blank">
                        🔗 ' . self::h($r['external_url'] ?? '') . '
                    </a>';
        }

        if ($type === 'youtube') {
            return '<a href="https://www.youtube.com/watch?v=' . self::h($r['youtube_id'] ?? '') . '" target="_blank">
                        🎬 YouTube video
                    </a>';
        }

        $folder = !empty($r['is_shared'])
            ? 'shared'
            : 'course_' . $courseId;

        // 🔥 FIX: riktig path (FJERNET /resources/)
        $url = "/hn_courses/uploads/{$folder}/" . ($r['stored_filename'] ?? '');

        return '<a href="' . self::h($url) . '" target="_blank">
                    ' . self::h($r['original_filename'] ?? $r['title'] ?? 'Ressurs') . '
                </a>';
    }
}