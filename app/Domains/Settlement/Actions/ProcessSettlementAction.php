<?php

namespace App\Domains\Settlement\Actions;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class ProcessSettlementAction
{
    public function execute(string $transactionId, string $status, string $settledAt): Transaction
    {
        $transaction = Transaction::where('id', $transactionId)->first();

        if (!$transaction) {
            throw new \InvalidArgumentException('Transaction not found');
        }

        $newStatus = $status === 'completed' ? 'completed' : 'failed';

        DB::transaction(function () use ($transaction, $newStatus, $settledAt) {
            $transaction->update([
                'status' => $newStatus,
                'settled_at' => $settledAt,
            ]);
        });

        return $transaction->refresh();
    }
}