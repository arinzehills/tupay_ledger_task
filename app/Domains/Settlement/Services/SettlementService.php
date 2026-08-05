<?php

namespace App\Domains\Settlement\Services;

use App\Models\Transaction;
use Illuminate\Support\Collection;

class SettlementService
{
    /**
     * @return Collection<int, Transaction>
     */
    public function getPendingSettlements(): Collection
    {
        return Transaction::where('status', 'pending')
            ->where('settled_at', null)
            ->get();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function generateWebhookSignature(array $payload): string
    {
        $secret = (string) config('settlement.webhook_secret', '');
        $payloadJson = json_encode($payload);
        if ($payloadJson === false) {
            throw new \RuntimeException('Failed to encode payload');
        }
        return hash_hmac('sha256', $payloadJson, $secret);
    }

    public function getSettlementStatus(Transaction $transaction): string
    {
        return $transaction->status === 'completed' ? 'settled' : 'pending';
    }
}