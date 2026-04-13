<?php
declare(strict_types=1);

namespace HnCourses\Service;

use PDO;
use RuntimeException;

final class CourseProvisionService
{
    public function __construct(
        private PDO $db,
        private string $booksBasePath // absolutt path til /hn_books/books
    ) {}

    /**
     * Oppretter kurs + bokstruktur
     */
    public function create(array $data): array
    {
        $title = trim($data['title'] ?? '');

        if ($title === '') {
            throw new RuntimeException('Tittel mangler');
        }

        /* =========================
           1. GENERER SLUG
        ========================= */

        $slug = $this->generateUniqueSlug($title);

        /* =========================
           2. DB INSERT
        ========================= */

        $stmt = $this->db->prepare("
            INSERT INTO hn_course_courses
                (slug, book_slug, title, description, is_active, created_at)
            VALUES
                (:slug, :book_slug, :title, :description, 1, NOW())
        ");

        $stmt->execute([
            'slug'        => $slug,
            'book_slug'   => $slug, // 🔑 alltid lik ved opprettelse
            'title'       => $title,
            'description' => $data['description'] ?? ''
        ]);

        $courseId = (int)$this->db->lastInsertId();

        /* =========================
           3. OPPRETT BOOK MAPPE
        ========================= */

        $this->createBookStructure($slug, $title);

        return [
            'id'        => $courseId,
            'slug'      => $slug,
            'book_slug' => $slug
        ];
    }

    /**
     * 🔐 Unik slug generator
     */
    private function generateUniqueSlug(string $title): string
    {
        $base = $this->slugify($title);
        $slug = $base;
        $i = 1;

        while ($this->slugExists($slug)) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    private function slugExists(string $slug): bool
    {
        $stmt = $this->db->prepare("
            SELECT id FROM hn_course_courses WHERE slug = ?
        ");
        $stmt->execute([$slug]);

        return (bool)$stmt->fetchColumn();
    }

    /**
     * 🧼 Slugify (robust)
     */
    private function slugify(string $text): string
    {
        $text = mb_strtolower($text);

        $map = [
            'æ' => 'ae',
            'ø' => 'o',
            'å' => 'a'
        ];

        $text = strtr($text, $map);

        $text = preg_replace('/[^a-z0-9]+/u', '-', $text);
        $text = trim($text, '-');

        return $text ?: 'kurs';
    }

    /**
     * 📁 Oppretter bokstruktur
     */
    private function createBookStructure(string $slug, string $title): void
    {
        $path = $this->booksBasePath . '/' . $slug;

        if (is_dir($path)) {
            throw new RuntimeException("Mappe finnes allerede: $slug");
        }

        if (!mkdir($path, 0775, true)) {
            throw new RuntimeException("Kunne ikke opprette mappe: $path");
        }

        /* =========================
           index.php
        ========================= */

        file_put_contents($path . '/index.php', $this->getIndexTemplate($slug, $title));

        /* =========================
           metadata.json (klar for fremtid)
        ========================= */

        file_put_contents($path . '/meta.json', json_encode([
            'title' => $title,
            'slug'  => $slug,
            'created_at' => date('c')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function getIndexTemplate(string $slug, string $title): string
    {
        return <<<PHP
<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/hn_core/inc/bootstrap.php';

?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="utf-8">
<title>{$title}</title>
<link rel="stylesheet" href="/hn_books/engine/reader_2026.css?v=2026-locked">
</head>
<body>

<h1>{$title}</h1>

<p>Ingen tekster opprettet ennå.</p>

<script type="module" src="/hn_books/engine/reader/index.js"></script>

</body>
</html>
PHP;
    }
}