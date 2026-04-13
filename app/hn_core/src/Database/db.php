<?php
declare(strict_types=1);

namespace HnCore\Database;

function db(string $name): \PDO {
    return DatabaseManager::get($name);
}