<?php
declare(strict_types=1);

/*
|------------------------------------------------------------
| OpenAI-klient – HN Admin
|------------------------------------------------------------
| - Kaller OpenAI Chat Completions API
| - Returnerer KUN ren JSON (array)
| - Kaster exception ved ugyldig respons
|------------------------------------------------------------
*/

function call_openai(string $prompt): string
{
    if (!defined('OPENAI_API_KEY') || OPENAI_API_KEY === '') {
        throw new RuntimeException('OPENAI_API_KEY er ikke definert');
    }

    $payload = [
        'model' => 'gpt-4.1-mini',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Du er en strukturert oppgavegenerator som returnerer KUN gyldig JSON.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => 0.3,
        'max_tokens'  => 2500
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . OPENAI_API_KEY,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_THROW_ON_ERROR),
        CURLOPT_TIMEOUT        => 40
    ]);

    $result = curl_exec($ch);

    if ($result === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('cURL-feil: ' . $err);
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new RuntimeException('OpenAI API-feil (HTTP ' . $httpCode . ')');
    }

    $json = json_decode($result, true);

    if (!isset($json['choices'][0]['message']['content'])) {
        throw new RuntimeException('Ugyldig respons fra OpenAI');
    }

    $content = trim($json['choices'][0]['message']['content']);

    /*
     |--------------------------------------------------------
     | Rens respons:
     | - Fjern ```json ``` eller ``` ```
     | - Fjern tekst før/etter JSON
     |--------------------------------------------------------
     */

    // Fjern markdown code blocks
    $content = preg_replace('/^```(?:json)?/i', '', $content);
    $content = preg_replace('/```$/', '', $content);
    $content = trim($content);

    // Forsøk å hente ut første JSON-array
    if (!str_starts_with($content, '[')) {
        $start = strpos($content, '[');
        $end   = strrpos($content, ']');

        if ($start !== false && $end !== false && $end > $start) {
            $content = substr($content, $start, $end - $start + 1);
        }
    }

    // Valider at det faktisk er gyldig JSON
    $test = json_decode($content, true);

    if (!is_array($test)) {
        throw new RuntimeException(
            "AI returnerte ikke gyldig JSON:\n" . $content
        );
    }

    return json_encode($test, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
