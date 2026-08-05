<?php

namespace App\Domains\Swap\Actions;

use App\Domains\Ledger\Actions\PostLedgerEntriesAction;
use App\Domains\Ledger\Services\LedgerService;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Shared\Services\DistributedLockService;
use App\Shared\ValueObjects\Money;
use App\Support\Enums\TransactionStatus;
use Illuminate\Support\Facades\DB;

class ExecuteSwapAction
{
    private DistributedLockService $lockService;
    private ValidateBalanceAction $validateBalance;
    private CalculateSwapAction $calculateSwap;
    private PostLedgerEntriesAction $postLedgerEntries;
    private LedgerService $ledgerService;

    public function __construct(
        DistributedLockService $lockService,
        ValidateBalanceAction $validateBalance,
        CalculateSwapAction $calculateSwap,
        PostLedgerEntriesAction $postLedgerEntries,
        LedgerService $ledgerService
    ) {
        $this->lockService = $lockService;
        $this->validateBalance = $validateBalance;
        $this->calculateSwap = $calculateSwap;
        $this->postLedgerEntries = $postLedgerEntries;
        $this->ledgerService = $ledgerService;
    }

    public function execute(
        User $user,
        Wallet $sourceWallet,
        Wallet $destinationWallet,
        Money $sourceAmount,
        string $referenceId
    ): Transaction {
        $lockKeys = [
            (string) $user->id,
            (string) $sourceWallet->id,
            (string) $destinationWallet->id,
        ];
        sort($lockKeys);

        return $this->lockService->withLock($lockKeys, function () use (
            $user,
            $sourceWallet,
            $destinationWallet,
            $sourceAmount,
            $referenceId
        ) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            }

            return DB::transaction(function () use (
                $user,
                $sourceWallet,
                $destinationWallet,
                $sourceAmount,
                $referenceId
            ) {
                $sourceWallet->lockForUpdate()->first();
                $destinationWallet->lockForUpdate()->first();

                if (!$this->validateBalance->execute($sourceWallet, $sourceAmount)) {
                    throw new \InvalidArgumentException('Insufficient balance');
                }

                $destinationAmount = $this->calculateSwap->execute($sourceAmount);

                $status = TransactionStatus::COMPLETED->value;
                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'source_wallet_id' => $sourceWallet->id,
                    'destination_wallet_id' => $destinationWallet->id,
                    'status' => $status,
                    'source_amount' => $sourceAmount->getAmount(),
                    'destination_amount' => $destinationAmount->getAmount(),
                    'reference_id' => $referenceId,
                ]);

                $this->postLedgerEntries->execute(
                    $sourceWallet,
                    $sourceAmount,
                    $destinationWallet,
                    $destinationAmount,
                    $referenceId
                );

                return $transaction;
            });
        });
    }
}