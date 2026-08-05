<?php

namespace App\Domains\Settlement\Actions;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class ProcessSettlementAction
{
    public function execute(int $transactionId, string $status, string $settledAt): Transaction
    {
        return DB::transaction(function () use ($transactionId, $status, $settledAt) {
            $transaction = Transaction::where('id', (int) $transactionId)
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                throw new \InvalidArgumentException('Transaction not found');
            }

            // Idempotency: if already in terminal state, return as-is
            if (in_array($transaction->status, ['completed', 'failed'])) {
                return $transaction;
            }

            $newStatus = $status === 'completed' ? 'completed' : 'failed';

            // Validate state transition (prevent invalid transitions like failed→completed)
            $validTransitions = [
                'pending' => ['completed', 'failed'],
                'completed' => ['completed'], // idempotent only
                'failed' => ['failed'], // idempotent only
            ];

            if (! in_array($newStatus, $validTransitions[$transaction->status] ?? [])) {
                throw new \InvalidArgumentException(
                    "Invalid state transition: {$transaction->status} → {$newStatus}"
                );
            }

            $transaction->update([
                'status' => $newStatus,
                'settled_at' => $settledAt,
            ]);

            return $transaction->refresh();
        });
    }
}
