<?php
declare(strict_types=1);

namespace HnLex\Action;

use HnCore\Database\DatabaseManager;
use HnLex\Service\LexStorageService;
use HnLex\Validation\LexEntryValidator;
use RuntimeException;
use Throwable;

class ApproveEntryAction
{
    public function handle(array $post): array
    {
        if (empty($post['ids']) || !is_array($post['ids'])) {
            throw new RuntimeException('No IDs provided');
        }

        $ids = array_map('intval', $post['ids']);

        $pdo = DatabaseManager::get('lex');
        $storage = new LexStorageService($pdo);
        $validator = new LexEntryValidator();

        $errors = [];
        $approved = [];

        foreach ($ids as $id) {

            $stmt = $pdo->prepare("
                SELECT *
                FROM lex_entries_staging
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->execute([$id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$row || $row['status'] !== 'pending') {
                continue;
            }

            /* JSON */
            if (!empty($post['payload'][$id])) {
                $data = json_decode($post['payload'][$id], true);
            } else {
                $data = json_decode($row['payload_json'], true);
            }

            if (!is_array($data)) {
                $errors[$id] = 'Invalid JSON';
                continue;
            }

            /* VALIDATION */
            try {
                $validator->validate($data);
            } catch (Throwable $e) {
                $errors[$id] = $e->getMessage();
                continue;
            }

            /* STORE */
            $storage->storeStructured($data);

            $stmt = $pdo->prepare("
                UPDATE lex_entries_staging
                SET status = 'approved',
                    approved_at = NOW()
                WHERE id = ?
            ");

            $stmt->execute([$id]);

            $approved[] = $id;
        }

        return [
            'approved' => $approved,
            'errors' => $errors
        ];
    }
}