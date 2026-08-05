<?php

namespace Tests\Integration\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'User',
        ]);
    }

    public function test_login_with_valid_credentials()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'email',
                    'first_name',
                    'last_name',
                    'middle_name',
                ],
            ])
            ->assertJsonPath('user.email', 'test@example.com')
            ->assertJsonPath('user.first_name', 'Test')
            ->assertJsonPath('user.last_name', 'User');

        $this->assertNotNull($response->json('token'));
    }

    public function test_login_with_invalid_password()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('error', 'Unauthorized');
    }

    public function test_login_with_nonexistent_user()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('error', 'Unauthorized');
    }

    public function test_login_validation_missing_email()
    {
        $response = $this->postJson('/api/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_validation_missing_password()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422);
    }
}
