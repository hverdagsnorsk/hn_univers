<?php
declare(strict_types=1);

namespace HnBooks\Service;

use PDO;
use RuntimeException;

final class TextService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    public function save(array $data): string
    {
        $bookKey = $this->normalizeKey(
            $data['book_key_select'] ?? $data['book_key'] ?? $data['book_key_new'] ?? ''
        );

        if ($bookKey === '') {
            throw new RuntimeException('Velg bok.');
        }

        $title   = trim($data['title'] ?? '');
        $level   = trim($data['level'] ?? '');
        $rawHtml = $data['raw_html'] ?? $data['content'] ?? '';

        if ($title === '' || $rawHtml === '') {
            throw new RuntimeException('Tittel og tekst må fylles ut.');
        }

        $rawHtml = $this->normalizeNorwegianSpacing($rawHtml);

        $textKey = $this->nextTextKey($bookKey);

        // 👉 KRITISK: bruker din eksisterende generator
        require_once __DIR__ . '/../../../../hn_admin/text_generate.php';

        $sourcePath = generate_text_html_v2(
            bookKey: $bookKey,
            textKey: $textKey,
            title:   $title,
            rawHtml: $rawHtml,
            level:   ($level !== '' ? $level : null)
        );

        $stmt = $this->pdo->prepare("
            INSERT INTO texts (
                book_key,
                text_key,
                title,
                level,
                source_path,
                active,
                created_at
            ) VALUES (
                :book_key,
                :text_key,
                :title,
                :level,
                :source_path,
                1,
                NOW()
            )
        ");

        $stmt->execute([
            'book_key'    => $bookKey,
            'text_key'    => $textKey,
            'title'       => $title,
            'level'       => ($level !== '' ? $level : null),
            'source_path' => $sourcePath
        ]);

        return $sourcePath;
    }

    private function normalizeKey(string $s): string
    {
        $s = trim($s);
        $s = preg_replace('/\s+/', '-', $s);
        $s = preg_replace('/[^A-Za-z0-9\-]/', '', $s);
        $s = preg_replace('/\-+/', '-', $s);
        return strtolower(trim($s, '-'));
    }

    private function nextTextKey(string $bookKey): string
    {
        $stmt = $this->pdo->prepare("
            SELECT text_key
            FROM texts
            WHERE book_key = :bk
            ORDER BY id DESC
            LIMIT 200
        ");

        $stmt->execute(['bk' => $bookKey]);

        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $max = 0;

        foreach ($rows as $tk) {
            if (preg_match('/-(\d{3})$/', (string)$tk, $m)) {
                $n = (int)$m[1];
                if ($n > $max) $max = $n;
            }
        }

        return ucfirst($bookKey) . '-' . str_pad((string)($max + 1), 3, '0', STR_PAD_LEFT);
    }

    private function normalizeNorwegianSpacing(string $html): string
    {
        $html = preg_replace('/(?:\x{00A0}|\s)+([.,;:!?])/u', '$1', $html);
        $html = preg_replace('/(?:\x{00A0}|\s)+([)\]\}»”])/u', '$1', $html);
        $html = preg_replace('/([(\[\{«“])(?:\x{00A0}|\s)+/u', '$1');
        $html = preg_replace('/[ \t]{2,}/u', ' ', $html);
        return $html ?? '';
    }
}