<?php
declare(strict_types=1);

/* ==========================================================
   ERROR LOGGING
========================================================== */

$logDir  = dirname(__DIR__, 3) . '/logs';
$logFile = $logDir . '/php_error.log';

if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}

ini_set('log_errors', '1');
ini_set('error_log', $logFile);
error_reporting(E_ALL);


/* ==========================================================
   BOOTSTRAP GUARD
========================================================== */

if (defined('HN_BOOTSTRAPPED')) {
    return;
}

define('HN_BOOTSTRAPPED', true);


/* ==========================================================
   PATHS
========================================================== */

define('HN_ROOT', dirname(__DIR__, 3));     // /www
define('HN_APP_ROOT', HN_ROOT . '/app');    // /www/app
define('HN_CORE', '/home/7/h/hverdagsnorsk/www/app/hn_core');


/* ==========================================================
   AUTOLOAD (PSR-4 ENTRY POINT)
========================================================== */

$autoload = HN_APP_ROOT . '/vendor/autoload.php';

if (!is_file($autoload)) {
    throw new RuntimeException('Autoload not found: ' . $autoload);
}

require $autoload;


/* ==========================================================
   ENV (MÅ LASTES FØR APP)
========================================================== */

use HnCore\Env\EnvLoader;

$envPath = HN_ROOT . '/.env';

if (!is_file($envPath)) {
    throw new RuntimeException('.env not found: ' . $envPath);
}

EnvLoader::load($envPath);


/* ==========================================================
   SESSION (WEB ONLY)
========================================================== */

if (php_sapi_name() !== 'cli') {

    if (session_status() === PHP_SESSION_NONE) {

        session_start([
            'cookie_httponly' => true,
            'cookie_secure'   => !empty($_SERVER['HTTPS']),
            'use_strict_mode' => true,
            'cookie_samesite' => 'Lax'
        ]);
    }
}


/* ==========================================================
   APP BOOT (HER SKAL RESTEN LASTES)
========================================================== */

HnCore\Bootstrap\App::boot();
require_once HN_APP_ROOT . '/hn_core/helpers/cache.php';
require_once HN_APP_ROOT . '/hn_core/helpers/html.php';


/* ==========================================================
   BASE PATHS
========================================================== */

defined('HN_BASE')         || define('HN_BASE', '');
defined('HN_COURSE_BASE') || define('HN_COURSE_BASE', '/hn_courses');
defined('HN_BOOKS_BASE')  || define('HN_BOOKS_BASE', '/hn_books');
defined('HN_LEX_BASE')    || define('HN_LEX_BASE', '/hn_lex');
defined('HN_FLASH_BASE')  || define('HN_FLASH_BASE', '/hn_flash');
defined('HN_ADMIN_BASE')  || define('HN_ADMIN_BASE', '/hn_admin');