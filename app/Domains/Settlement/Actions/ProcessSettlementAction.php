<?php

namespace App\Domains\Settlement\Actions;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class ProcessSettlementAction
{
    public function execute(string $transactionId, string $status, string $settledAt): Transaction
    {
        return DB::transaction(function () use ($transactionId, $status, $settledAt) {
            $transaction = Transaction::where('id', $transactionId)
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                throw new \InvalidArgumentException('Transaction not found');
            }

            if (in_array($transaction->status, ['completed', 'failed'])) {
                return $transaction;
            }

            $newStatus = $status === 'completed' ? 'completed' : 'failed';

            $transaction->update([
                'status' => $newStatus,
                'settled_at' => $settledAt,
            ]);

            return $transaction->refresh();
        });
    }
}
