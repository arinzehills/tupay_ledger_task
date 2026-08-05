<?php

namespace App\Shared\Services;

use Illuminate\Support\Facades\Cache;

class DistributedLockService
{
    private const LOCK_TTL = 30; // seconds

    /**
     * @param  array<int, string>  $keys
     */
    public function acquireLock(array $keys, int $timeout = 10): bool
    {
        sort($keys);
        $lockKey = 'lock:'.implode(':', $keys);

        $startTime = time();
        while (time() - $startTime < $timeout) {
            if (Cache::add($lockKey, true, self::LOCK_TTL)) {
                return true;
            }
            usleep(100000); // 100ms
        }

        return false;
    }

    /**
     * @param  array<int, string>  $keys
     */
    public function releaseLock(array $keys): void
    {
        sort($keys);
        $lockKey = 'lock:'.implode(':', $keys);
        Cache::forget($lockKey);
    }

    /**
     * @template T
     *
     * @param  array<int, string>  $keys
     * @param  callable(): T  $callback
     * @return T
     */
    public function withLock(array $keys, callable $callback, int $timeout = 10): mixed
    {
        if (! $this->acquireLock($keys, $timeout)) {
            throw new \RuntimeException('Could not acquire lock');
        }

        try {
            return $callback();
        } finally {
            $this->releaseLock($keys);
        }
    }
}
