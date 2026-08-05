<?php

namespace App\Domains\Settlement\Services;

use App\Models\Transaction;
use Illuminate\Support\Collection;

class SettlementService
{
    public function getPendingSettlements(): Collection
    {
        return Transaction::where('status', 'pending')
            ->where('settled_at', null)
            ->get();
    }

    public function generateWebhookSignature(array $payload): string
    {
        $secret = config('settlement.webhook_secret', '');
        return hash_hmac('sha256', json_encode($payload), $secret);
    }

    public function getSettlementStatus(Transaction $transaction): string
    {
        return $transaction->status === 'completed' ? 'settled' : 'pending';
    }
}