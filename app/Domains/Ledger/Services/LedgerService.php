<?php

namespace App\Domains\Ledger\Services;

use App\Models\Wallet;
use App\Shared\ValueObjects\Money;

class LedgerService
{
    public function getWalletBalance(Wallet $wallet): Money
    {
        return Money::fromSubunits($wallet->getBalance());
    }

    public function canWithdraw(Wallet $wallet, Money $amount): bool
    {
        $balance = $this->getWalletBalance($wallet);

        return $balance->isGreaterThanOrEqual($amount);
    }
}
