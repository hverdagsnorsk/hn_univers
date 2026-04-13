<?php
declare(strict_types=1);

namespace HnCore\Utils;

use PDO;

final class SlugService
{
    /**
     * Hovedmetode: genererer unik slug for kurs
     */
    public static function generateUniqueCourseSlug(PDO $pdo, string $title): string
    {
        $base = self::normalize($title);

        if ($base === '') {
            $base = 'kurs';
        }

        $slug = $base;
        $i = 2;

        while (self::exists($pdo, $slug)) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    /**
     * Normaliserer tekst → slug
     */
    public static function normalize(string $text): string
    {
        $text = mb_strtolower($text);

        // Norske tegn
        $map = [
            'æ' => 'ae',
            'ø' => 'o',
            'å' => 'aa'
        ];

        $text = strtr($text, $map);

        // Fjern alt som ikke er a-z0-9
        $text = preg_replace('/[^a-z0-9]+/u', '-', $text);

        // Trim dash
        $text = trim($text, '-');

        // Fjern doble dash
        $text = preg_replace('/-+/', '-', $text);

        return $text;
    }

    /**
     * Sjekker om slug finnes
     */
    private static function exists(PDO $pdo, string $slug): bool
    {
        $stmt = $pdo->prepare("
            SELECT 1 
            FROM hn_course_courses 
            WHERE slug = ? 
            LIMIT 1
        ");

        $stmt->execute([$slug]);

        return (bool)$stmt->fetchColumn();
    }
}