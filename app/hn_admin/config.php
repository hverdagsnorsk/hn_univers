<?php
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'passord123');

define('BASE', dirname(__DIR__));
define('DOC_FILE', BASE . '/data/docs.json');
define('VID_FILE', BASE . '/data/videos.json');
define('SCH_FILE', BASE . '/data/schedule.json');
define('CONTENT_FILE', BASE . '/data/content.json');

function read_json(string $file): array {
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?: [];
}

function save_json(string $file, array $data): void {
    file_put_contents(
        $file,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

define('TEACHER_EMAIL', 'svar@hverdagsnorsk.no');
define('SITE_NAME', 'Hverdagsnorsk');
define('SITE_URL', 'https://hverdagsnorsk.no');