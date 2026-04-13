<?php
declare(strict_types=1);

class TextAdminService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = $pdo;
    }

    public function getBooks(): array
    {
        return get_books();
    }

    public function getTexts(string $book): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                t.id,
                t.text_key,
                t.title,
                t.source_path,
                t.active,
                t.created_at,
                COUNT(k.id) AS task_count
            FROM texts t
            LEFT JOIN tasks k ON k.text_id = t.id
            WHERE t.book_key = :book
            GROUP BY t.id
            ORDER BY t.created_at DESC
        ");

        $stmt->execute(['book' => $book]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUnregistered(string $book): array
    {
        $files = get_texts_for_book($book);

        $fileNames = array_map(
            fn($f) => $f['id'] . '.html',
            $files
        );

        $stmt = $this->pdo->prepare("
            SELECT source_path
            FROM texts
            WHERE book_key = :book
        ");

        $stmt->execute(['book' => $book]);
        $registered = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $registeredFiles = array_map(
            fn($path) => basename($path),
            $registered
        );

        return array_diff($fileNames, $registeredFiles);
    }


public function createText(
    string $book,
    string $title,
    string $textKey,
    string $sourcePath
): void {

    $stmt = $this->pdo->prepare("
        INSERT INTO texts
        (book_key, title, text_key, source_path, active, created_at)
        VALUES (:book, :title, :text_key, :source_path, 1, NOW())
    ");

    $stmt->execute([
        'book' => $book,
        'title' => $title,
        'text_key' => $textKey,
        'source_path' => $sourcePath
    ]);
}
}

