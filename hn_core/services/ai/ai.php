<?php
declare(strict_types=1);

class HNAI
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY');
        $this->model  = env('AI_MODEL_TEXT', false) ?? 'gpt-4o-mini';
    }

    private function request(array $payload): array
    {
        $ch = curl_init('https://api.openai.com/v1/chat/completions');

        curl_setopt_array($ch, [

            CURLOPT_RETURNTRANSFER => true,

            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer '.$this->apiKey
            ],

            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload)

        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new RuntimeException('OpenAI request failed');
        }

        curl_close($ch);

        return json_decode($response,true);
    }

    public function complete(string $prompt): string
    {
        $cacheKey = 'ai_' . md5($prompt);

        if (cache()->has($cacheKey)) {
            return cache()->get($cacheKey);
        }

        $payload = [

            'model' => $this->model,

            'messages' => [
                ['role'=>'user','content'=>$prompt]
            ],

            'temperature' => 0.2

        ];

        $data = $this->request($payload);

        $result = $data['choices'][0]['message']['content'] ?? '';

        cache()->set($cacheKey, $result, 3600);

        return $result;
    }
}