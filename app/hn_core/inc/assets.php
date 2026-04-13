<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| HN Core – Global Asset Loader
|--------------------------------------------------------------------------
| - filemtime-based cache busting
| - Production safe
| - Works across entire HN universe
|--------------------------------------------------------------------------
*/

if (!defined('HN_ROOT')) {
    define('HN_ROOT', dirname(__DIR__, 2));
}

/**
 * Generate versioned asset URL.
 *
 * Example:
 *   <?= hn_asset('/hn_books/engine/reader.js') ?>
 */
function hn_asset(string $publicPath): string
{
    $publicPath = '/' . ltrim($publicPath, '/');

    $filePath = HN_ROOT . $publicPath;

    if (!file_exists($filePath)) {
        return $publicPath; // fail silently in prod
    }

    $version = filemtime($filePath);

    return $publicPath . '?v=' . $version;
}