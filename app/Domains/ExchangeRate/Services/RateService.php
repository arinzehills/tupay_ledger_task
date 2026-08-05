<?php

namespace App\Domains\ExchangeRate\Services;

use Illuminate\Support\Facades\Redis;

class RateService
{
    private const CACHE_KEY = 'exchange_rate:ngn_cny';

    private const FRESH_TTL = 60; // seconds

    private const STALE_TTL = 300; // seconds

    public function getRate(): string
    {
        $cached = Redis::get(self::CACHE_KEY);

        if ($cached) {
            $data = json_decode($cached, true);
            if (isset($data['fresh_until'], $data['rate']) && $data['fresh_until'] > time()) {
                return (string) $data['rate'];
            }
        }

        $freshRate = $this->fetchRate();

        // Validate rate is positive (prevent zero/negative rate exploitation)
        if (! is_numeric($freshRate) || (float) $freshRate <= 0) {
            throw new \RuntimeException('Invalid exchange rate from endpoint');
        }

        Redis::setex(
            self::CACHE_KEY,
            self::STALE_TTL,
            json_encode([
                'rate' => $freshRate,
                'fresh_until' => time() + self::FRESH_TTL,
            ])
        );

        return (string) $freshRate;
    }

    public function getStaleRate(): ?string
    {
        $cached = Redis::get(self::CACHE_KEY);
        if ($cached) {
            $data = json_decode($cached, true);
            if (isset($data['rate'])) {
                return (string) $data['rate'];
            }
        }

        return null;
    }

    private function fetchRate(): float
    {
        // Mock endpoint - returns NGN to CNY rate
        // TODO: Replace with actual external API call to exchange rate provider
        return 0.0048; // 1 NGN = 0.0048 CNY
    }
}
