<?php
declare(strict_types=1);

namespace HnCore\Ai\Transport;

use HnCore\Ai\Exceptions\AiException;

class OpenAiHttpClient
{
    private string $endpoint =
        'https://api.openai.com/v1/chat/completions';

    private int $maxRetries = 3;

    private function getEnv(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? getenv($key) ?? $default;
    }

    public function call(array $payload): array
    {
        $apiKey = $this->getEnv('OPENAI_API_KEY');

        if (!$apiKey) {
            throw new AiException('Missing OPENAI_API_KEY');
        }

        if (!isset($payload['model'])) {
            $payload['model'] =
                $this->getEnv('AI_MODEL_TEXT')
                ?: $this->getEnv('AI_MODEL')
                ?: 'gpt-4o-mini';
        }

        if (!isset($payload['temperature'])) {
            $payload['temperature'] =
                (float)($this->getEnv('AI_TEMPERATURE', 0.2));
        }

        $retry = 0;

        do {

            $ch = curl_init($this->endpoint);

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey
                ],
                CURLOPT_POSTFIELDS =>
                    json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_TIMEOUT => 120
            ]);

            $response = curl_exec($ch);

            if ($response === false) {
                throw new AiException(
                    'cURL error: ' . curl_error($ch)
                );
            }

            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if ($status === 200) {

                $json = json_decode($response, true);

                if (!is_array($json)) {
                    throw new AiException('Invalid OpenAI response');
                }

                return $json;
            }

            if ($status === 429 && $retry < $this->maxRetries) {

                sleep(2 * ($retry + 1));
                $retry++;
                continue;
            }

            throw new AiException(
                "OpenAI error (HTTP {$status}): {$response}"
            );

        } while ($retry < $this->maxRetries);

        throw new AiException('OpenAI retry limit reached');
    }

    public function chat(array $messages, array $options = []): string
    {
        $payload = array_merge($options, [
            'messages' => $messages
        ]);

        $json = $this->call($payload);

        return trim(
            $json['choices'][0]['message']['content'] ?? ''
        );
    }

    public function json(array $messages, array $options = []): array
    {
        $text = $this->chat($messages, $options);

        if (preg_match('/\{.*\}/s', $text, $m)) {
            $decoded = json_decode($m[0], true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new AiException(
            "AI did not return valid JSON:\n{$text}"
        );
    }
}