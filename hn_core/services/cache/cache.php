<?php
declare(strict_types=1);

class HNCache
{
    private array $cache = [];

    public function get(string $key)
    {
        if (!isset($this->cache[$key])) {
            return null;
        }

        $entry = $this->cache[$key];

        if ($entry['expires'] !== null && $entry['expires'] < time()) {
            unset($this->cache[$key]);
            return null;
        }

        return $entry['value'];
    }

    public function set(string $key, $value, int $ttl = 0): void
    {
        $this->cache[$key] = [
            'value' => $value,
            'expires' => $ttl > 0 ? time() + $ttl : null
        ];
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function clear(): void
    {
        $this->cache = [];
    }
}
