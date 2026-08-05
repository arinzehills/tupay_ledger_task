<?php

namespace App\Domains\Auth\Actions;

use App\Domains\Auth\Services\EatService;
use App\Models\User;

class IssueEatAction
{
    private EatService $eatService;

    public function __construct(EatService $eatService)
    {
        $this->eatService = $eatService;
    }

    /**
     * @param array<string, mixed> $actionPayload
     */
    public function execute(User $user, array $actionPayload): string
    {
        ksort($actionPayload);
        $payloadJson = json_encode($actionPayload);
        $actionHash = hash('sha256', is_string($payloadJson) ? $payloadJson : '{}');
        return $this->eatService->issueToken($user, $actionHash);
    }
}