<?php
declare(strict_types=1);

namespace HnLex\Admin;

use HnCore\Database\DatabaseManager;
use HnLex\Service\LexStorageService;
use PDO;
use RuntimeException;
use Throwable;

final class ApprovalController
{
    public function handle(): void
    {
        $pdo = DatabaseManager::get('lex');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $storage = new LexStorageService($pdo);

        $action = $_POST['action'] ?? null;
        $id     = isset($_POST['id']) ? (int)$_POST['id'] : null;

        $message = null;
        $error   = null;

        if ($action && $id) {
            try {

                $stmt = $pdo->prepare("
                    SELECT * FROM lex_entries_staging WHERE id = ?
                ");
                $stmt->execute([$id]);

                $entry = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$entry) {
                    throw new RuntimeException("Entry not found");
                }

                if ($action === 'approve') {

                    $data = json_decode($entry['payload_json'], true);

                    if (!$data) {
                        throw new RuntimeException("Invalid JSON");
                    }

                    /* ======================================================
                       🔥 HER SKAL DU IKKE TIL STAGING – MEN TIL PRODUKSJON
                    ====================================================== */

                    $entryId = $this->storeToProduction($pdo, $data);

                    $pdo->prepare("
                        UPDATE lex_entries_staging
                        SET status = 'approved',
                            approved_at = NOW()
                        WHERE id = ?
                    ")->execute([$id]);

                    $message = "Approved (entry {$entryId})";
                }

                if ($action === 'reject') {

                    $pdo->prepare("
                        UPDATE lex_entries_staging
                        SET status = 'rejected'
                        WHERE id = ?
                    ")->execute([$id]);

                    $message = "Rejected";
                }

            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }

        $rows = $pdo->query("
            SELECT id, lemma, word_class, created_at
            FROM lex_entries_staging
            WHERE status = 'pending'
            ORDER BY created_at ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/views/approval.view.php';
    }

    /**
     * 🔥 NY: faktisk lagring til lex_entries (ikke staging)
     */
    private function storeToProduction(PDO $pdo, array $data): int
    {
        $lemma = mb_strtolower(trim($data['lemma'] ?? ''));

        if ($lemma === '') {
            throw new RuntimeException("Missing lemma");
        }

        /* ======================================================
           WORD CLASS
        ====================================================== */

        $wordClass = $data['ordklasse'] ?? $data['word_class'] ?? null;

        if (!$wordClass) {
            throw new RuntimeException("Missing word class");
        }

        $stmt = $pdo->prepare("
            SELECT id FROM lex_word_classes WHERE code = ?
        ");
        $stmt->execute([$wordClass]);

        $wordClassId = $stmt->fetchColumn();

        if (!$wordClassId) {
            throw new RuntimeException("Word class not found: {$wordClass}");
        }

        /* ======================================================
           ENTRY
        ====================================================== */

        $stmt = $pdo->prepare("
            INSERT INTO lex_entries (lemma, language, word_class_id, created_at)
            VALUES (?, 'nb', ?, NOW())
            ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)
        ");

        $stmt->execute([$lemma, $wordClassId]);

        $entryId = (int)$pdo->lastInsertId();

        /* ======================================================
           SENSE (MINIMUM)
        ====================================================== */

        $stmt = $pdo->prepare("
            INSERT INTO lex_senses (entry_id, word_class_id, sense_order, created_at)
            VALUES (?, ?, 1, NOW())
        ");
        $stmt->execute([$entryId, $wordClassId]);

        $senseId = (int)$pdo->lastInsertId();

        /* ======================================================
           EXPLANATION (A2 fallback)
        ====================================================== */

        $forklaring = $data['explanations']['A2']['forklaring'] ?? null;
        $example    = $data['explanations']['A2']['example'] ?? null;

        if ($forklaring) {
            $stmt = $pdo->prepare("
                INSERT INTO lex_explanations (entry_id, sense_id, level, language, explanation)
                VALUES (?, ?, 'A2', 'nb', ?)
            ");
            $stmt->execute([$entryId, $senseId, $forklaring]);
        }

        if ($example) {
            $stmt = $pdo->prepare("
                INSERT INTO lex_examples (entry_id, sense_id, level, language, example)
                VALUES (?, ?, 'A2', 'nb', ?)
            ");
            $stmt->execute([$entryId, $senseId, $example]);
        }

        return $entryId;
    }
}