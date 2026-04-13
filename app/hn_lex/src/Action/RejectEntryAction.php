<?php
declare(strict_types=1);

namespace HnLex\Action;

use HnCore\Database\DatabaseManager;
use RuntimeException;

class RejectEntryAction
{
    public function handle(array $post): void
    {
        if (empty($post['ids']) || !is_array($post['ids'])) {
            throw new RuntimeException('No IDs provided');
        }

        $ids = array_map('intval', $post['ids']);

        $pdo = DatabaseManager::get('lex');

        $in = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $pdo->prepare("
            UPDATE lex_entries_staging
            SET status = 'rejected',
                rejected_at = NOW()
            WHERE id IN ($in)
        ");

        $stmt->execute($ids);
    }
}