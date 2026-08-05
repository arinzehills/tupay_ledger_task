<?php

namespace App\Domains\Auth\Actions;

use App\Domains\Auth\Services\EatService;

class ConsumeEatAction
{
    private EatService $eatService;

    public function __construct(EatService $eatService)
    {
        $this->eatService = $eatService;
    }

    /**
     * @param  array<string, mixed>  $actionPayload
     */
    public function execute(string $token, array $actionPayload): bool
    {
        ksort($actionPayload);
        $payloadJson = json_encode($actionPayload);

        // Fail explicitly if payload encoding fails (prevents security bypass)
        if ($payloadJson === false) {
            throw new \RuntimeException('Failed to encode action payload');
        }

        $actionHash = hash('sha256', $payloadJson);
        $payload = $this->eatService->consumeToken($token, $actionHash);

        return $payload !== null;
    }
}
