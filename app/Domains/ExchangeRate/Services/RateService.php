<?php

namespace App\Domains\ExchangeRate\Services;

use Illuminate\Support\Facades\Redis;

class RateService
{
    private const CACHE_KEY = 'exchange_rate:ngn_cny';
    private const FRESH_TTL = 60; // seconds
    private const STALE_TTL = 300; // seconds

    public function getRate(): float
    {
        $cached = Redis::get(self::CACHE_KEY);

        if ($cached) {
            $data = json_decode($cached, true);
            if ($data['fresh_until'] > time()) {
                return $data['rate'];
            }
        }

        $freshRate = $this->fetchRate();

        Redis::setex(
            self::CACHE_KEY,
            self::STALE_TTL,
            json_encode([
                'rate' => $freshRate,
                'fresh_until' => time() + self::FRESH_TTL,
            ])
        );

        return $freshRate;
    }

    public function getStaleRate(): ?float
    {
        $cached = Redis::get(self::CACHE_KEY);
        if ($cached) {
            $data = json_decode($cached, true);
            return $data['rate'];
        }
        return null;
    }

    private function fetchRate(): float
    {
        // Mock endpoint - returns NGN to CNY rate
        return 0.0048; // 1 NGN = 0.0048 CNY
    }
}