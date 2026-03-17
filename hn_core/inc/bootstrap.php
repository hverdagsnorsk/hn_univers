<?php
declare(strict_types=1);

if (defined('HN_BOOTSTRAPPED')) {
    return;
}

define('HN_BOOTSTRAPPED', true);

ini_set('display_errors','1');
error_reporting(E_ALL);

define('HN_CORE_ROOT', dirname(__DIR__));
define('HN_ROOT', dirname(HN_CORE_ROOT));

require_once HN_CORE_ROOT.'/inc/env.php';

$envPath = HN_ROOT.'/config/.env';

hn_load_env($envPath);

function env(string $key, bool $required=true): ?string
{
    $value = $_ENV[$key] ?? getenv($key) ?? null;

    if ($required && ($value === null || $value === '')) {
        throw new RuntimeException("Missing ENV variable: ".$key);
    }

    return $value;
}
