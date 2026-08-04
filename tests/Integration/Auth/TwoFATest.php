<?php

namespace Tests\Integration\Auth;

use App\Domains\Auth\Services\TotpService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TwoFATest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected TotpService $totpService;
    private array $redisStore = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRedis();

        $this->totpService = new TotpService();

        $secret = $this->totpService->generateSecret();

        $this->user = User::create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'User',
            'totp_secret' => $secret,
        ]);
    }

    private function mockRedis(): void
    {
        $redisStore = &$this->redisStore;

        \Illuminate\Support\Facades\Redis::shouldReceive('setex')
            ->andReturnUsing(function ($key, $ttl, $value) use (&$redisStore) {
                $redisStore[$key] = $value;
                return true;
            });

        \Illuminate\Support\Facades\Redis::shouldReceive('get')
            ->andReturnUsing(function ($key) use (&$redisStore) {
                return $redisStore[$key] ?? null;
            });

        \Illuminate\Support\Facades\Redis::shouldReceive('del')
            ->andReturnUsing(function ($key) use (&$redisStore) {
                unset($redisStore[$key]);
                return 1;
            });
    }

    public function test_challenge_with_valid_totp_code()
    {
        $code = $this->generateValidTotpCode($this->user->totp_secret);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/2fa/challenge', [
                'totp_code' => $code,
                'action_payload' => ['type' => 'swap', 'amount' => 1000],
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['eat_token'])
            ->assertJsonMissing(['error']);

        $token = $response->json('eat_token');
        $this->assertNotNull($token);
    }

    public function test_challenge_with_invalid_totp_code()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/2fa/challenge', [
                'totp_code' => '000000',
                'action_payload' => ['type' => 'swap', 'amount' => 1000],
            ]);

        $response->assertStatus(401)
            ->assertJsonPath('error', 'Invalid TOTP code');
    }

    public function test_challenge_without_totp_setup()
    {
        $userWithoutTotp = User::create([
            'email' => 'no-totp@example.com',
            'password' => Hash::make('password123'),
            'first_name' => 'No',
            'last_name' => 'TOTP',
        ]);

        $response = $this->actingAs($userWithoutTotp, 'sanctum')
            ->postJson('/api/2fa/challenge', [
                'totp_code' => '123456',
                'action_payload' => ['type' => 'swap', 'amount' => 1000],
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('error', 'TOTP not set up');
    }

    public function test_challenge_requires_authentication()
    {
        $response = $this->postJson('/api/2fa/challenge', [
            'totp_code' => '123456',
            'action_payload' => ['type' => 'swap', 'amount' => 1000],
        ]);

        $response->assertStatus(401);
    }

    public function test_eat_token_is_single_use()
    {
        $code = $this->generateValidTotpCode($this->user->totp_secret);
        $actionPayload = ['type' => 'swap', 'amount' => 1000];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/2fa/challenge', [
                'totp_code' => $code,
                'action_payload' => $actionPayload,
            ]);

        $eatToken = $response->json('eat_token');
        $redisKey = "eat:{$eatToken}";

        $this->assertNotNull(\Illuminate\Support\Facades\Redis::get($redisKey));

        \Illuminate\Support\Facades\Redis::del($redisKey);

        $this->assertNull(\Illuminate\Support\Facades\Redis::get($redisKey));
    }

    protected function generateValidTotpCode(string $secret): string
    {
        $totp = \OTPHP\TOTP::create($secret);
        return $totp->now();
    }
}