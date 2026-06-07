<?php

namespace App\Service\Cache;

use App\Service\Logging\ElasticsearchLogger;
use Exception;
use Redis;

class RedisCacheService
{
    private ?Redis $redis = null;
    private const int DEFAULT_TTL = 3600; // 1 hour default TTL
    private const string KEY_PREFIX = 'pattern_search:';
    private const string DEFAULT_REDIS_HOST = '127.0.0.1';
    private const int DEFAULT_REDIS_PORT = 6379;

    public function __construct(
        private readonly ElasticsearchLogger $logger,
        string                               $redisDsn
    ) {
        try {
            // Parse Redis DSN (e.g., redis://localhost:6379)
            $parsed = parse_url($redisDsn);

            $this->redis = new Redis();
            $this->redis->connect(
                $parsed['host'] ?? self::DEFAULT_REDIS_HOST,
                $parsed['port'] ?? self::DEFAULT_REDIS_PORT
            );

            // Test connection
            $this->redis->ping();

            $this->logger->info('Redis cache service initialized', [
                'service' => '[RedisCacheService]',
                'host' => $parsed['host'] ?? self::DEFAULT_REDIS_HOST,
                'port' => $parsed['port'] ?? self::DEFAULT_REDIS_PORT,
            ]);
        } catch (Exception $e) {
            $this->logger->warning('Redis connection failed, caching disabled', [
                'error' => $e->getMessage()
            ]);
            $this->redis = null;
        }
    }

    /**
     * Get cached value by key
     *
     * @param string $key Cache key
     * @return mixed|null Cached value or null if not found
     */
    public function get(string $key): mixed
    {
        if ($this->redis === null) {
            return null;
        }

        try {
            $value = $this->redis->get(self::KEY_PREFIX . $key);
            if ($value === null) {
                return null;
            }
            return json_decode($value, true);
        } catch (Exception $e) {
            $this->logger->warning('Redis get failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Set cache value with optional TTL
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int|null $ttl Time to live in seconds (null = default TTL)
     * @return bool Success status
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if ($this->redis === null) {
            return false;
        }

        try {
            $ttl = $ttl ?? self::DEFAULT_TTL;
            $serialized = json_encode($value);
            $this->redis->setex(self::KEY_PREFIX . $key, $ttl, $serialized);
            return true;
        } catch (Exception $e) {
            $this->logger->warning('Redis set failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Atomically claim a one-shot marker. Returns true only if the marker did not
     * already exist. Used for dispatch-time dedup so we never enqueue a second message
     * for a key that already has one pending or in-flight.
     *
     * Fails open: if Redis is unavailable we return true so indexing is never fully
     * stalled by a cache outage (the per-language lock still guards execution).
     *
     * @param string $key Marker key
     * @param int $ttl Safety-net TTL in seconds; the marker self-heals if a holder dies
     * @return bool True if the marker was newly acquired, false if one already existed
     */
    public function acquirePendingMarker(string $key, int $ttl): bool
    {
        if ($this->redis === null) {
            return true;
        }

        try {
            return $this->redis->set(self::KEY_PREFIX . $key, '1', ['nx', 'ex' => $ttl]) === true;
        } catch (Exception $e) {
            $this->logger->warning('Redis acquirePendingMarker failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return true;
        }
    }

    /**
     * Delete cache entry
     *
     * @param string $key Cache key
     * @return bool Success status
     */
    public function delete(string $key): bool
    {
        if ($this->redis === null) {
            return false;
        }

        try {
            $this->redis->del(self::KEY_PREFIX . $key);
            return true;
        } catch (Exception $e) {
            $this->logger->warning('Redis delete failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Generate cache key from parameters
     *
     * @param string $prefix Key prefix
     * @param array $params Parameters to hash
     * @return string Cache key
     */
    public function generateKey(string $prefix, array $params): string
    {
        return $prefix . ':' . md5(json_encode($params));
    }

    /**
     * Clear all pattern search cache entries
     *
     * @return bool Success status
     */
    public function clearPatternSearchCache(): bool
    {
        if ($this->redis === null) {
            return false;
        }

        try {
            $keys = $this->redis->keys(self::KEY_PREFIX . '*');
            if (!empty($keys)) {
                $this->redis->del(...$keys);
            }
            return true;
        } catch (Exception $e) {
            $this->logger->warning('Redis cache clear failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
