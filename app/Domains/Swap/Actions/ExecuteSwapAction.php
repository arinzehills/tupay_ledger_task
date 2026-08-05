<?php

namespace App\Domains\Swap\Actions;

use App\Domains\Ledger\Actions\PostLedgerEntriesAction;
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

    public function __construct(
        DistributedLockService $lockService,
        ValidateBalanceAction $validateBalance,
        CalculateSwapAction $calculateSwap,
        PostLedgerEntriesAction $postLedgerEntries
    ) {
        $this->lockService = $lockService;
        $this->validateBalance = $validateBalance;
        $this->calculateSwap = $calculateSwap;
        $this->postLedgerEntries = $postLedgerEntries;
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

        // Retry logic for deadlock handling (max 3 attempts)
        $maxAttempts = 3;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            try {
                $attempt++;

                return $this->lockService->withLock($lockKeys, function () use (
                    $user,
                    $sourceWallet,
                    $destinationWallet,
                    $sourceAmount,
                    $referenceId
                ) {
                    // Set isolation level BEFORE transaction starts to avoid timing issues
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

                        if (! $this->validateBalance->execute($sourceWallet, $sourceAmount)) {
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
            } catch (\Exception $e) {
                // Check if this is a deadlock error (MySQL error code 1213)
                if ($attempt < $maxAttempts && $this->isDeadlock($e)) {
                    usleep(100000 * $attempt); // Exponential backoff: 100ms, 200ms, 300ms

                    continue;
                }
                throw $e;
            }
        }

        throw new \RuntimeException('Swap failed after maximum retry attempts');
    }

    private function isDeadlock(\Throwable $exception): bool
    {
        // MySQL deadlock error code
        return strpos($exception->getMessage(), '1213') !== false
            || strpos($exception->getMessage(), 'Deadlock found') !== false;
    }
}
