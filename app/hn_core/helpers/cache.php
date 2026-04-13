<?php
declare(strict_types=1);

use HnCore\Service\CacheService;

if (!function_exists('cache')) {

    function cache(): CacheService
    {
        static $instance = null;

        if ($instance === null) {
            $instance = new CacheService();
        }

        return $instance;
    }
}