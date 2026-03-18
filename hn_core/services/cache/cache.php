<?php
declare(strict_types=1);

class HNCache
{
    private array $cache = [];

    public function get(string $key)
    {
        return $this->cache[$key] ?? null;
    }

    public function set(string $key, $value): void
    {
        $this->cache[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($this->cache[$key]);
    }

    public function clear(): void
    {
        $this->cache = [];
    }
}
