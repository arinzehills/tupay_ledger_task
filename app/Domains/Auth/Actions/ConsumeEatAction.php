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
        $actionHash = hash('sha256', is_string($payloadJson) ? $payloadJson : '{}');
        $payload = $this->eatService->consumeToken($token, $actionHash);

        return $payload !== null;
    }
}
