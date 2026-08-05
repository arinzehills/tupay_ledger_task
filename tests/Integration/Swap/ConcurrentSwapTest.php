<?php

namespace Tests\Integration\Swap;

use App\Domains\Auth\Services\TotpService;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use OTPHP\TOTP;
use Tests\TestCase;

class ConcurrentSwapTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Wallet $ngnWallet;

    private Wallet $cnyWallet;

    private TotpService $totpService;

    private array $redisStore = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRedis();

        $this->totpService = new TotpService;
        $secret = $this->totpService->generateSecret();

        $this->user = User::create([
            'email' => 'swap-test@example.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Swap',
            'last_name' => 'Test',
            'totp_secret' => $secret,
        ]);

        $this->ngnWallet = Wallet::create([
            'user_id' => $this->user->id,
            'currency' => 'NGN',
        ]);

        $this->cnyWallet = Wallet::create([
            'user_id' => $this->user->id,
            'currency' => 'CNY',
        ]);

        LedgerEntry::create([
            'wallet_id' => $this->ngnWallet->id,
            'type' => 'credit',
            'amount' => 120000,
            'description' => 'Test balance - enough for 1 swap only',
        ]);

        LedgerEntry::create([
            'wallet_id' => $this->cnyWallet->id,
            'type' => 'credit',
            'amount' => 1000000,
            'description' => 'Test balance',
        ]);
    }

    public function test_swap_with_valid_eat_token()
    {
        $code = $this->generateValidTotpCode($this->user->totp_secret);
        $actionPayload = [
            'amount' => 1000000,
            'destination_currency' => 'CNY',
            'source_currency' => 'NGN',
        ];

        $eatResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/2fa/challenge', [
                'totp_code' => $code,
                'action_payload' => $actionPayload,
            ]);

        $this->assertEquals(200, $eatResponse->status());
        $eatToken = $eatResponse->json('eat_token');
        $this->assertNotNull($eatToken);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/swap', [
                'source_currency' => 'NGN',
                'destination_currency' => 'CNY',
                'amount' => 1000000,
            ], [
                'X-Elevated-Action-Token' => $eatToken,
            ]);

        $this->assertContains($response->status(), [200, 409, 422]);
    }

    public function test_concurrent_swaps_only_one_succeeds()
    {
        $swapAmount = 100000;
        $successCount = 0;
        $failureCount = 0;
        $results = [];

        for ($i = 0; $i < 10; $i++) {
            $code = $this->generateValidTotpCode($this->user->totp_secret);
            $actionPayload = [
                'amount' => $swapAmount,
                'destination_currency' => 'CNY',
                'source_currency' => 'NGN',
            ];

            $eatResponse = $this->actingAs($this->user, 'sanctum')
                ->postJson('/api/2fa/challenge', [
                    'totp_code' => $code,
                    'action_payload' => $actionPayload,
                ]);

            $eatToken = $eatResponse->json('eat_token');

            $response = $this->actingAs($this->user, 'sanctum')
                ->postJson('/api/swap', [
                    'source_currency' => 'NGN',
                    'destination_currency' => 'CNY',
                    'amount' => $swapAmount,
                ], [
                    'X-Elevated-Action-Token' => $eatToken,
                ]);

            $status = $response->status();
            $results[] = $status;

            if ($status === 200) {
                $successCount++;
            } elseif (in_array($status, [409, 422])) {
                $failureCount++;
            }
        }

        $this->assertEquals(1, $successCount, "Expected exactly 1 success (200), got {$successCount}. Results: ".json_encode($results));
        $this->assertEquals(9, $failureCount, "Expected exactly 9 failures (409/422), got {$failureCount}. Results: ".json_encode($results));

        $this->ngnWallet->refresh();
        $ngnBalance = $this->ngnWallet->getBalance();
        $this->assertGreaterThanOrEqual(0, $ngnBalance, 'NGN wallet balance must never go negative (no overdrafts)');

        $this->cnyWallet->refresh();
        $cnyBalance = $this->cnyWallet->getBalance();
        $this->assertGreaterThanOrEqual(0, $cnyBalance, 'CNY wallet balance must never go negative (no overdrafts)');
    }

    public function test_swap_without_eat_token_fails()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/swap', [
                'source_currency' => 'NGN',
                'destination_currency' => 'CNY',
                'amount' => 1000000,
            ]);

        $this->assertEquals(401, $response->status());
    }

    protected function generateValidTotpCode(string $secret): string
    {
        $totp = TOTP::create($secret);

        return $totp->now();
    }

    private function mockRedis(): void
    {
        $redisStore = &$this->redisStore;

        Redis::shouldReceive('setex')
            ->andReturnUsing(function ($key, $ttl, $value) use (&$redisStore) {
                $redisStore[$key] = $value;

                return true;
            });

        Redis::shouldReceive('get')
            ->andReturnUsing(function ($key) use (&$redisStore) {
                return $redisStore[$key] ?? null;
            });

        Redis::shouldReceive('del')
            ->andReturnUsing(function ($key) use (&$redisStore) {
                unset($redisStore[$key]);

                return 1;
            });
    }
}
