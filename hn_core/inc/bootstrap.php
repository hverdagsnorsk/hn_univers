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

$envPath = getenv('HOME').'/www/.env';

hn_load_env($envPath);

function env(string $key, bool $required=true): ?string
{
    $value = $_ENV[$key] ?? getenv($key) ?? null;

    if ($required && ($value === null || $value === '')) {
        throw new RuntimeException("Missing ENV variable: ".$key);
    }

    return $value;
}
require_once HN_CORE_ROOT.'/services/database/db.php';

$databases = [

    'main'    => 'DB_MAIN',
    'lex'     => 'DB_LEX',
    'grammar' => 'DB_GRAMMAR',
    'courses' => 'DB_COURSES'

];

foreach ($databases as $key => $prefix) {

    try {

        $host = env($prefix.'_HOST',false);
        $name = env($prefix.'_NAME',false);
        $user = env($prefix.'_USER',false);
        $pass = env($prefix.'_PASS',false);

        if ($host && $name && $user) {

            $pdo = hn_pdo($host,$name,$user,$pass ?? '');

            DB::set($key,$pdo);

        }

    } catch (Throwable $e) {

        error_log("HN DB connection failed [$key]: ".$e->getMessage());

    }

}
require_once HN_CORE_ROOT.'/services/cache/cache.php';

function cache(): HNCache
{
    static $cache;

    if(!$cache){
        $cache = new HNCache();
    }

    return $cache;
}
