<?php
declare(strict_types=1);

namespace HnLex\Service;

use PDO;
use RuntimeException;
use Throwable;

final class ApprovalService
{
    public function __construct(
        private PDO $pdo,
        private LexStorageService $storage
    ) {}

    public function approve(int $stagingId): int
    {
        $this->pdo->beginTransaction();

        try {

            /* ======================================================
               FETCH STAGING
            ====================================================== */

            $stmt = $this->pdo->prepare("
                SELECT * FROM lex_entries_staging WHERE id = ?
            ");
            $stmt->execute([$stagingId]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                throw new RuntimeException('Staging entry not found');
            }

            $data = json_decode($row['payload_json'], true);

            if (!$data) {
                throw new RuntimeException('Invalid payload JSON');
            }

            /* ======================================================
               🔥 PRODUKSJON VIA STORAGE SERVICE
            ====================================================== */

            $entryId = $this->storage->storeToProduction($data);

            /* ======================================================
               UPDATE STAGING
            ====================================================== */

            $stmt = $this->pdo->prepare("
                UPDATE lex_entries_staging
                SET status = 'approved',
                    approved_at = NOW()
                WHERE id = ?
            ");

            $stmt->execute([$stagingId]);

            $this->pdo->commit();

            return $entryId;

        } catch (Throwable $e) {

            $this->pdo->rollBack();

            throw new RuntimeException('[Approval failed] ' . $e->getMessage());
        }
    }

    public function reject(int $stagingId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE lex_entries_staging
            SET status = 'rejected'
            WHERE id = ?
        ");

        $stmt->execute([$stagingId]);
    }
}