<?php
namespace HnCore\Support;

class JsonResponse
{
    public static function send(array $data): void
    {
        if (ob_get_length()) {
            ob_clean();
        }

        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public static function error(string $message): void
    {
        self::send(['error' => $message]);
    }
}