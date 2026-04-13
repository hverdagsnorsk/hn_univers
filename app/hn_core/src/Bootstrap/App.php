<?php
declare(strict_types=1);

namespace HnCore\Bootstrap;

final class App
{
    public static function boot(): void
    {
        ini_set('display_errors', '1');
        error_reporting(E_ALL);
    }
}