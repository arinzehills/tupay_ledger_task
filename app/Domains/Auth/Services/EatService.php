<?php

namespace App\Domains\Auth\Services;

use App\Models\User;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class EatService
{
    private const TTL_SECONDS = 60;

    public function issueToken(User $user, string $actionHash): string
    {
        $token = Str::random(64);
        $redisKey = "eat:{$token}";

        Redis::setex(
            $redisKey,
            self::TTL_SECONDS,
            json_encode([
                'user_id' => $user->id,
                'action_hash' => $actionHash,
            ])
        );

        return $token;
    }

    public function consumeToken(string $token, string $expectedActionHash): ?array
    {
        $redisKey = "eat:{$token}";
        $data = Redis::get($redisKey);

        if (!$data) {
            return null;
        }

        $payload = json_decode($data, true);

        if ($payload['action_hash'] !== $expectedActionHash) {
            return null;
        }

        Redis::del($redisKey);

        return $payload;
    }
}