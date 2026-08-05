<?php

namespace App\Domains\Swap\Services;

use App\Domains\Swap\Actions\ExecuteSwapAction;
use App\Models\Transaction;
use App\Models\User;
use App\Shared\ValueObjects\Money;
use App\Support\Enums\Currency;

class SwapService
{
    private ExecuteSwapAction $executeSwap;

    public function __construct(ExecuteSwapAction $executeSwap)
    {
        $this->executeSwap = $executeSwap;
    }

    public function swap(
        User $user,
        Currency $sourceCurrency,
        Currency $destinationCurrency,
        Money $sourceAmount,
        string $referenceId
    ): Transaction {
        // Prevent self-swaps (swapping same currency or to same wallet)
        if ($sourceCurrency === $destinationCurrency) {
            throw new \InvalidArgumentException('Cannot swap currency to itself');
        }

        $sourceWallet = $user->wallets()
            ->where('currency', $sourceCurrency->value)
            ->firstOrFail();

        $destinationWallet = $user->wallets()
            ->where('currency', $destinationCurrency->value)
            ->firstOrFail();

        // Additional safety check - prevent same wallet ID (edge case)
        if ($sourceWallet->id === $destinationWallet->id) {
            throw new \InvalidArgumentException('Source and destination wallets must be different');
        }

        return $this->executeSwap->execute(
            $user,
            $sourceWallet,
            $destinationWallet,
            $sourceAmount,
            $referenceId
        );
    }
}
