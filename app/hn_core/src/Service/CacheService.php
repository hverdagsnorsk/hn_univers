<?php
declare(strict_types=1);

namespace HnCore\Service;

class CacheService
{
    private string $path;

    public function __construct()
    {
        $this->path = HN_ROOT . '/cache';

        if (!is_dir($this->path)) {
            mkdir($this->path, 0777, true);
        }
    }

    private function file(string $key): string
    {
        return $this->path . '/' . md5($key) . '.cache';
    }

    public function get(string $key)
    {
        $file = $this->file($key);

        if (!file_exists($file)) {
            return null;
        }

        $data = file_get_contents($file);

        if ($data === false) {
            return null;
        }

        return unserialize($data);
    }

    public function set(string $key, $value): void
    {
        file_put_contents(
            $this->file($key),
            serialize($value),
            LOCK_EX
        );
    }

    public function remember(string $key, callable $callback)
    {
        $cached = $this->get($key);

        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();

        $this->set($key, $value);

        return $value;
    }

    public function clear(string $key): void
    {
        $file = $this->file($key);

        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function flush(): void
    {
        foreach (glob($this->path . '/*.cache') as $file) {
            unlink($file);
        }
    }
}