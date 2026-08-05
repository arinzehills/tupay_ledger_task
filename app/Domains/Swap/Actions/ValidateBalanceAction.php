<?php

namespace App\Domains\Swap\Actions;

use App\Domains\Ledger\Services\LedgerService;
use App\Models\Wallet;
use App\Shared\ValueObjects\Money;

class ValidateBalanceAction
{
    private LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function execute(Wallet $wallet, Money $requiredAmount): bool
    {
        return $this->ledgerService->canWithdraw($wallet, $requiredAmount);
    }
}