<?php

namespace Tests\Integration\Settlement;

use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettlementWebhookTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Transaction $transaction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'email' => 'settlement@example.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Settlement',
            'last_name' => 'Test',
        ]);

        $ngnWallet = Wallet::create([
            'user_id' => $this->user->id,
            'currency' => 'NGN',
        ]);

        $cnyWallet = Wallet::create([
            'user_id' => $this->user->id,
            'currency' => 'CNY',
        ]);

        LedgerEntry::create([
            'wallet_id' => $ngnWallet->id,
            'type' => 'credit',
            'amount' => 1000000,
        ]);

        LedgerEntry::create([
            'wallet_id' => $cnyWallet->id,
            'type' => 'credit',
            'amount' => 1000000,
        ]);

        $this->transaction = Transaction::create([
            'user_id' => $this->user->id,
            'source_wallet_id' => $ngnWallet->id,
            'destination_wallet_id' => $cnyWallet->id,
            'status' => 'pending',
            'source_amount' => 500000,
            'destination_amount' => 300000,
            'reference_id' => 'test-ref-'.uniqid(),
        ]);
    }

    public function test_settlement_webhook_with_valid_signature()
    {
        $payload = [
            'transaction_id' => $this->transaction->id,
            'status' => 'completed',
            'settled_at' => '2026-08-05T12:00:00Z',
        ];

        $secret = config('settlement.webhook_secret');
        $signature = hash_hmac('sha256', json_encode($payload), $secret);

        $response = $this->postJson('/api/webhook/settlement', array_merge($payload, [
            'signature' => $signature,
        ]));

        $this->assertEquals(200, $response->status());
        $this->transaction->refresh();
        $this->assertEquals('completed', $this->transaction->status);
    }

    public function test_settlement_webhook_with_invalid_signature()
    {
        $payload = [
            'transaction_id' => $this->transaction->id,
            'status' => 'completed',
            'settled_at' => '2026-08-05T12:00:00Z',
        ];

        $response = $this->postJson('/api/webhook/settlement', array_merge($payload, [
            'signature' => 'invalid-signature',
        ]));

        $this->assertEquals(401, $response->status());
        $this->transaction->refresh();
        $this->assertEquals('pending', $this->transaction->status);
    }

    public function test_settlement_webhook_with_invalid_transaction()
    {
        $payload = [
            'transaction_id' => 99999,
            'status' => 'completed',
            'settled_at' => '2026-08-05T12:00:00Z',
        ];

        $secret = config('settlement.webhook_secret');
        $signature = hash_hmac('sha256', json_encode($payload), $secret);

        $response = $this->postJson('/api/webhook/settlement', array_merge($payload, [
            'signature' => $signature,
        ]));

        $this->assertEquals(404, $response->status());
    }

    public function test_settlement_webhook_marks_transaction_as_failed()
    {
        $payload = [
            'transaction_id' => $this->transaction->id,
            'status' => 'failed',
            'settled_at' => '2026-08-05T12:00:00Z',
        ];

        $secret = config('settlement.webhook_secret');
        $signature = hash_hmac('sha256', json_encode($payload), $secret);

        $response = $this->postJson('/api/webhook/settlement', array_merge($payload, [
            'signature' => $signature,
        ]));

        $this->assertEquals(200, $response->status());
        $this->transaction->refresh();
        $this->assertEquals('failed', $this->transaction->status);
    }
}
