<?php

namespace App\Libraries;

use CodeIgniter\Cache\CacheInterface;

/**
 * CacheManager — Abstraction layer for CI4 cache.
 * Works with file handler in dev, Redis in production.
 * Provides tagged caching, statistics, and a Redis-like API.
 */
class CacheManager
{
    private CacheInterface $cache;
    private static ?CacheManager $instance = null;

    // Default TTLs (seconds)
    const TTL_SHORT  = 60;        // 1 min
    const TTL_MEDIUM = 300;       // 5 min
    const TTL_LONG   = 3600;      // 1 hour
    const TTL_DAY    = 86400;     // 24 hours

    public function __construct()
    {
        $this->cache = \Config\Services::cache();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get a value from cache
     */
    public function get(string $key, $default = null)
    {
        $value = $this->cache->get($key);
        return $value !== null ? $value : $default;
    }

    /**
     * Store a value in cache
     */
    public function set(string $key, $value, int $ttl = self::TTL_MEDIUM): bool
    {
        $this->trackKey($key);
        return $this->cache->save($key, $value, $ttl);
    }

    /**
     * Get from cache or execute callback and store result
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        $value = $this->cache->get($key);
        if ($value !== null) {
            $this->incrementStat('hits');
            return $value;
        }

        $this->incrementStat('misses');
        $value = $callback();
        $this->cache->save($key, $value, $ttl);
        $this->trackKey($key);
        return $value;
    }

    /**
     * Delete a specific key
     */
    public function forget(string $key): bool
    {
        $this->untrackKey($key);
        return $this->cache->delete($key);
    }

    /**
     * Delete a specific key (alias for forget)
     */
    public function delete(string $key): bool
    {
        return $this->forget($key);
    }

    /**
     * Delete all keys matching a tag/prefix
     */
    public function invalidateGroup(string $prefix): int
    {
        $keys = $this->getTrackedKeys();
        $count = 0;
        foreach ($keys as $key) {
            if (str_starts_with($key, $prefix)) {
                $this->cache->delete($key);
                $this->untrackKey($key);
                $count++;
            }
        }
        return $count;
    }

    /**
     * Flush all cache
     */
    public function flush(): bool
    {
        $this->cache->save('_cache_keys', []);
        $this->cache->save('_cache_stats', ['hits' => 0, 'misses' => 0, 'writes' => 0]);
        return $this->cache->clean();
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        $stats = $this->cache->get('_cache_stats') ?? ['hits' => 0, 'misses' => 0, 'writes' => 0];
        $keys = $this->getTrackedKeys();
        $info = $this->cache->getCacheInfo();

        return [
            'handler'    => config('Cache')->handler ?? 'file',
            'hits'       => $stats['hits'],
            'misses'     => $stats['misses'],
            'writes'     => $stats['writes'],
            'hit_rate'   => ($stats['hits'] + $stats['misses']) > 0
                ? round(($stats['hits'] / ($stats['hits'] + $stats['misses'])) * 100, 1)
                : 0,
            'keys_count' => count($keys),
            'is_redis'   => (config('Cache')->handler === 'redis'),
        ];
    }

    /**
     * Check if Redis is available
     */
    public function isRedisAvailable(): bool
    {
        return extension_loaded('redis') && config('Cache')->handler === 'redis';
    }

    // ── Internal helpers ──

    private function trackKey(string $key): void
    {
        if (str_starts_with($key, '_cache_')) return;
        $keys = $this->getTrackedKeys();
        if (!in_array($key, $keys)) {
            $keys[] = $key;
            $this->cache->save('_cache_keys', $keys, self::TTL_DAY);
        }
        $this->incrementStat('writes');
    }

    private function untrackKey(string $key): void
    {
        $keys = $this->getTrackedKeys();
        $keys = array_filter($keys, fn($k) => $k !== $key);
        $this->cache->save('_cache_keys', array_values($keys), self::TTL_DAY);
    }

    private function getTrackedKeys(): array
    {
        return $this->cache->get('_cache_keys') ?? [];
    }

    private function incrementStat(string $type): void
    {
        $stats = $this->cache->get('_cache_stats') ?? ['hits' => 0, 'misses' => 0, 'writes' => 0];
        $stats[$type] = ($stats[$type] ?? 0) + 1;
        $this->cache->save('_cache_stats', $stats, self::TTL_DAY);
    }
}
