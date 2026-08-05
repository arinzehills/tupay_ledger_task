<?php

namespace App\Domains\Ledger\Actions;

use App\Models\LedgerEntry;
use App\Models\Wallet;
use App\Shared\ValueObjects\Money;

class PostLedgerEntriesAction
{
    public function execute(
        Wallet $debitWallet,
        Money $debitAmount,
        Wallet $creditWallet,
        Money $creditAmount,
        string $referenceId
    ): void {
        LedgerEntry::create([
            'wallet_id' => $debitWallet->id,
            'type' => 'debit',
            'amount' => $debitAmount->getAmount(),
            'reference_id' => $referenceId,
        ]);

        LedgerEntry::create([
            'wallet_id' => $creditWallet->id,
            'type' => 'credit',
            'amount' => $creditAmount->getAmount(),
            'reference_id' => $referenceId,
        ]);
    }
}
