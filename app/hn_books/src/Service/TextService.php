<?php
declare(strict_types=1);

namespace HnBooks\Service;

use PDO;
use RuntimeException;
use HnCore\Database\DatabaseManager;
use HnBooks\Generator\TextGenerator;

final class TextService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = DatabaseManager::get('main');
    }

    public function save(array $data): string
    {
        error_log('[TEXT SAVE INPUT] ' . print_r($data, true));

        $bookKey = trim(
            $data['book_key']
            ?? $data['book_key_select']
            ?? ''
        );

        if ($bookKey === '') {
            throw new RuntimeException('Velg bok.');
        }

        $title = trim((string)($data['title'] ?? ''));
        $level = trim((string)($data['level'] ?? ''));

        $rawHtml = (string)(
            $data['raw_html']
            ?? $data['editor']
            ?? $data['content']
            ?? ''
        );

        if ($title === '' || trim($rawHtml) === '') {
            throw new RuntimeException('Tittel og tekst må fylles ut.');
        }

        $rawHtml = $this->sanitizeHtml($rawHtml);
        $rawHtml = $this->normalizeNorwegianSpacing($rawHtml);

        $textKey = $this->nextTextKey($bookKey);

        error_log('[TEXT GENERATE] book=' . $bookKey . ' key=' . $textKey);

        $sourcePath = TextGenerator::generate(
            $bookKey,
            $textKey,
            $title,
            $rawHtml,
            ($level !== '' ? $level : null)
        );

        $fullPath = HN_ROOT . '/app' . $sourcePath;

        if (!is_file($fullPath)) {
            error_log('[TEXT ERROR] File not created: ' . $fullPath);
            throw new RuntimeException('Fil ble ikke opprettet: ' . $fullPath);
        }

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
            'source_path' => $sourcePath,
        ]);

        error_log('[TEXT SAVED] ' . $sourcePath);

        return $sourcePath;
    }

    private function sanitizeHtml(string $html): string
    {
        $html = preg_replace('#<(script|style)[^>]*>.*?</(script|style)>#is', '', $html);
        $html = preg_replace('#</?(html|body|head)[^>]*>#i', '', $html);
        $html = preg_replace('#<!DOCTYPE[^>]*>#i', '', $html);
        $html = preg_replace('#<img[^>]+src="data:image/[^"]+"[^>]*>#i', '', $html);

        return trim($html);
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
                if ($n > $max) {
                    $max = $n;
                }
            }
        }

        return ucfirst($bookKey) . '-' . str_pad((string)($max + 1), 3, '0', STR_PAD_LEFT);
    }

    private function normalizeNorwegianSpacing(string $html): string
    {
        $html = preg_replace('/(?:\x{00A0}|\s)+([.,;:!?])/u', '$1', $html);
        $html = preg_replace('/(?:\x{00A0}|\s)+([)\]\}»”])/u', '$1', $html);
        $html = preg_replace('/([(\[\{«“])(?:\x{00A0}|\s)+/u', '$1', $html);
        $html = preg_replace('/[ \t]{2,}/u', ' ', $html);

        return $html ?? '';
    }
}