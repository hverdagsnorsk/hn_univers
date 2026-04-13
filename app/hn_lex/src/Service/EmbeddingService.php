<?php
declare(strict_types=1);

namespace HnLex\Service;

use RuntimeException;

final class EmbeddingService
{
    private string $apiKey;
    private string $model;

    /** enkel in-memory cache per request */
    private static array $cache = [];

    public function __construct(
        ?string $apiKey = null,
        string $model = 'text-embedding-3-small'
    ) {
        $this->apiKey = $apiKey ?? ($_ENV['OPENAI_API_KEY'] ?? '');

        if ($this->apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEY missing');
        }

        $this->model = $model;
    }

    /* ==========================================================
       EMBED
    ========================================================== */

    public function embed(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        /* ======================================================
           CACHE
        ====================================================== */

        $cacheKey = md5($text);

        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        /* ======================================================
           SIZE GUARD
        ====================================================== */

        if (mb_strlen($text) > 3000) {
            $text = mb_substr($text, 0, 3000);
        }

        /* ======================================================
           REQUEST (med retry)
        ====================================================== */

        $payload = json_encode([
            "model" => $this->model,
            "input" => $text
        ], JSON_UNESCAPED_UNICODE);

        if ($payload === false) {
            error_log('[EMBED] JSON encode failed');
            return [];
        }

        $response = $this->requestWithRetry($payload, 2);

        if (!$response) {
            return [];
        }

        /* ======================================================
           PARSE
        ====================================================== */

        $data = json_decode($response, true);

        if (!is_array($data)) {
            error_log('[EMBED] Invalid JSON response');
            return [];
        }

        if (!isset($data['data'][0]['embedding'])) {
            error_log('[EMBED] Missing embedding in response');
            return [];
        }

        $embedding = $data['data'][0]['embedding'];

        /* ======================================================
           CACHE STORE
        ====================================================== */

        self::$cache[$cacheKey] = $embedding;

        return $embedding;
    }

    /* ==========================================================
       REQUEST HANDLER (med retry)
    ========================================================== */

    private function requestWithRetry(string $payload, int $retries = 2): ?string
    {
        $attempt = 0;

        while ($attempt <= $retries) {

            $ch = curl_init("https://api.openai.com/v1/embeddings");

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json",
                    "Authorization: Bearer {$this->apiKey}"
                ],
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 5
            ]);

            $response = curl_exec($ch);

            $error    = curl_error($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if ($response !== false && $httpCode >= 200 && $httpCode < 300) {
                return $response;
            }

            error_log("[EMBED] attempt {$attempt} failed (HTTP {$httpCode}): {$error}");

            $attempt++;

            usleep(200000); // 200ms pause
        }

        return null;
    }

    /* ==========================================================
       COSINE SIMILARITY
    ========================================================== */

    public static function cosine(array $a, array $b): float
    {
        if (empty($a) || empty($b)) {
            return 0.0;
        }

        $len = min(count($a), count($b));

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $len; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        if ($normA == 0.0 || $normB == 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}