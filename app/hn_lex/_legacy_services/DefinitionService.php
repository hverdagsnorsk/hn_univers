<?php
declare(strict_types=1);

namespace HnLex\Service;

use Throwable;

final class DefinitionService
{
    private string $apiKey;
    private string $endpoint = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = getenv('OPENAI_API_KEY') ?: '';

        if ($this->apiKey === '') {
            throw new \RuntimeException('Missing OPENAI_API_KEY');
        }
    }

    public function generate(string $word, string $sentence = '', string $level = 'A1'): array
    {
        $prompt = $this->buildPrompt($word, $sentence, $level);

        $payload = [
            'model' => 'gpt-4o-mini',
            'temperature' => 0.4,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a Norwegian dictionary assistant. Return clean JSON only.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ];

        $response = $this->request($payload);

        return $this->parseResponse($response, $word, $sentence);
    }

    private function buildPrompt(string $word, string $sentence, string $level): string
    {
        return <<<PROMPT
Explain the Norwegian word "{$word}" for a {$level} learner.

Context sentence:
"{$sentence}"

Return JSON with:
- definition (short, simple)
- example (simple Norwegian sentence using the word)

Rules:
- Use very simple Norwegian (A1 level)
- No English
- No extra text
- JSON only

Example format:
{
  "definition": "...",
  "example": "..."
}
PROMPT;
    }

    private function request(array $payload): array
    {
        $ch = curl_init($this->endpoint);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 15
        ]);

        $result = curl_exec($ch);

        if ($result === false) {
            throw new \RuntimeException('OpenAI request failed: ' . curl_error($ch));
        }

        curl_close($ch);

        return json_decode($result, true) ?? [];
    }

    private function parseResponse(array $response, string $word, string $sentence): array
    {
        try {
            $content = $response['choices'][0]['message']['content'] ?? '';

            $json = json_decode($content, true);

            if (!is_array($json)) {
                throw new \RuntimeException('Invalid JSON from AI');
            }

            return [
                'definition' => trim((string)($json['definition'] ?? $word)),
                'example'    => trim((string)($json['example'] ?? $sentence))
            ];

        } catch (Throwable $e) {

            return [
                'definition' => $word,
                'example'    => $sentence
            ];
        }
    }
}