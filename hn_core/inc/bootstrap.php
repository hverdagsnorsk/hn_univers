<?php
declare(strict_types=1);

/*
|-------------------------------------------------------
| HN PLATFORM BOOTSTRAP
|-------------------------------------------------------
| Starter hele Hverdagsnorsk-plattformen
*/

if (defined('HN_BOOTSTRAPPED')) {
    return;
}

define('HN_BOOTSTRAPPED', true);

/* =====================================================
   ERROR REPORTING
===================================================== */

ini_set('display_errors','1');
error_reporting(E_ALL);

/* =====================================================
   PATHS
===================================================== */

define('HN_CORE_ROOT', dirname(__DIR__));
define('HN_ROOT', dirname(HN_CORE_ROOT));

/* =====================================================
   ENVIRONMENT
===================================================== */

require_once HN_CORE_ROOT.'/inc/env.php';

$envPath = getenv('HOME').'/www/.env';

if (!file_exists($envPath)) {
    throw new RuntimeException("ENV file missing: ".$envPath);
}

hn_load_env($envPath);

/* =====================================================
   ENV HELPER
===================================================== */

function env(string $key, bool $required=true): ?string
{
    $value = $_ENV[$key] ?? getenv($key) ?? null;

    if ($value === false) {
        $value = null;
    }

    if ($required && ($value === null || $value === '')) {
        throw new RuntimeException("Missing ENV variable: ".$key);
    }

    return $value;
}

/* =====================================================
   DATABASE SERVICE
===================================================== */

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

/* =====================================================
   FALLBACK: COURSES → GRAMMAR
===================================================== */

if (!DB::exists('courses') && DB::exists('grammar')) {

    DB::set('courses', DB::get('grammar'));

}

/* =====================================================
   CACHE SERVICE
===================================================== */

require_once HN_CORE_ROOT.'/services/cache/cache.php';

function cache(): HNCache
{
    static $cache;

    if(!$cache){
        $cache = new HNCache();
    }

    return $cache;
}

/* =====================================================
   AI SERVICE
===================================================== */

require_once HN_CORE_ROOT.'/services/ai/ai.php';

function ai(): HNAI
{
    static $ai;

    if(!$ai){
        $ai = new HNAI();
    }

    return $ai;
}