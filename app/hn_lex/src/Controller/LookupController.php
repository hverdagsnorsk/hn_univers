<?php
declare(strict_types=1);

namespace HnLex\Controller;

use Throwable;
use HnCore\Database\DatabaseManager;
use HnLex\Service\LookupService;
use HnLex\Service\SenseGenerationService;
use HnLex\Service\EmbeddingService;
use HnLex\Service\LexStorageService;

final class LookupController
{
    private string $logFile = '/home/7/h/hverdagsnorsk/www/logs/lookup_debug.log';

    private function log(string $msg): void
    {
        try {
            file_put_contents(
                $this->logFile,
                $msg . PHP_EOL,
                FILE_APPEND
            );
        } catch (Throwable $e) {
            error_log('[LOOKUP LOG FAIL] ' . $e->getMessage());
        }
    }

    public function handle(): void
    {
        header('Content-Type: application/json');

        $this->log('[CONTROLLER] START');

        try {
            $raw = file_get_contents('php://input');
            $input = json_decode($raw, true);

            if (!is_array($input)) {
                $input = $_GET;
            }

            $word     = trim((string)($input['word'] ?? ''));
            $language = trim((string)($input['language'] ?? 'nb'));
            $level    = trim((string)($input['level'] ?? 'A1'));

            $context = [
                'word'     => $word,
                'sentence' => (string)($input['sentence'] ?? ''),
                'prev'     => (string)($input['prev'] ?? ''),
                'next'     => (string)($input['next'] ?? '')
            ];

            $this->log('[CONTROLLER] WORD → ' . $word);

            if ($word === '') {
                $this->log('[CONTROLLER] EMPTY WORD');
                echo json_encode(['found' => false]);
                return;
            }

            $pdo = DatabaseManager::get('lex');
            $this->log('[CONTROLLER] DB OK');

            $embedding = null;

            if (getenv('OPENAI_API_KEY')) {
                try {
                    $embedding = new EmbeddingService();
                    $this->log('[CONTROLLER] EMBEDDING ON');
                } catch (Throwable $e) {
                    $this->log('[CONTROLLER] EMBEDDING OFF → ' . $e->getMessage());
                    $embedding = null;
                }
            }

            $storage = new LexStorageService($pdo);

            $senseGenerator = new SenseGenerationService(
                $pdo,
                $storage,
                $embedding
            );

            $lookup = new LookupService(
                $pdo,
                $senseGenerator,
                $embedding
            );

            $this->log('[CONTROLLER] BEFORE LOOKUP');

            $result = $lookup->lookup(
                $word,
                $language,
                $level,
                $context
            );

            $this->log('[CONTROLLER] AFTER LOOKUP → ' . json_encode($result, JSON_UNESCAPED_UNICODE));

            echo json_encode($result, JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {

            $this->log('[CONTROLLER ERROR] ' . $e->getMessage());

            http_response_code(500);

            echo json_encode([
                'found' => false,
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}