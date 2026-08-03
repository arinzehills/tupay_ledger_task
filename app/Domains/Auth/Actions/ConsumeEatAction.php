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
        $actionHash = hash('sha256', json_encode($actionPayload, JSON_SORT_KEYS));
        $payload = $this->eatService->consumeToken($token, $actionHash);

        return $payload !== null;
    }
}