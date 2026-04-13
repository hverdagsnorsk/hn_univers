<?php
declare(strict_types=1);

namespace HnBooks\Repository;

use PDO;
use HnCore\Database\DatabaseManager;

final class TextRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = DatabaseManager::get('main');
    }

    public function getAll(): array
    {
        return $this->pdo->query("
            SELECT
                t.id,
                t.title,
                t.book_key,
                t.text_key,
                t.level,
                t.status,
                t.active,
                t.created_at,
                t.updated_at,
                t.source_path
            FROM texts t
            ORDER BY t.id DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM texts
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function insert(array $data): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO texts (
                book_key,
                text_key,
                title,
                level,
                source_path,
                status,
                active,
                created_at
            ) VALUES (
                :book_key,
                :text_key,
                :title,
                :level,
                :source_path,
                :status,
                1,
                NOW()
            )
        ");

        $stmt->execute([
            'book_key'    => $data['book_key'],
            'text_key'    => $data['text_key'],
            'title'       => $data['title'],
            'level'       => $data['level'],
            'source_path' => $data['source_path'],
            'status'      => $data['status'] ?? 'draft',
        ]);
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE texts SET
                title = :title,
                level = :level,
                source_path = :source_path,
                status = :status,
                updated_at = NOW()
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            'id'          => $id,
            'title'       => $data['title'],
            'level'       => $data['level'],
            'source_path' => $data['source_path'],
            'status'      => $data['status'],
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM texts
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute(['id' => $id]);
    }

    public function toggleActive(int $id): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE texts
            SET active = 1 - active
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute(['id' => $id]);
    }

    public function getRecentKeys(string $bookKey): array
    {
        $stmt = $this->pdo->prepare("
            SELECT text_key
            FROM texts
            WHERE book_key = :bk
            ORDER BY id DESC
            LIMIT 200
        ");

        $stmt->execute(['bk' => $bookKey]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}