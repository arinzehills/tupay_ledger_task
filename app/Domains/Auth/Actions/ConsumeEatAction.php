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

    public function execute(string $token, array $actionPayload): bool
    {
        ksort($actionPayload);
        $actionHash = hash('sha256', json_encode($actionPayload));
        $payload = $this->eatService->consumeToken($token, $actionHash);

        return $payload !== null;
    }
}
