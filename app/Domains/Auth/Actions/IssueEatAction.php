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

    public function execute(User $user, array $actionPayload): string
    {
        $actionHash = hash('sha256', json_encode($actionPayload, JSON_SORT_KEYS));
        return $this->eatService->issueToken($user, $actionHash);
    }
}