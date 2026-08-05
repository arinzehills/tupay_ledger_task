<?php

namespace Database\Seeders;

use App\Domains\Auth\Services\TotpService;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed test users with valid TOTP secrets for 2FA testing.
     *
     * Test Users:
     * 1. test@example.com / password123
     *    - Valid TOTP secret (generated randomly)
     *    - For testing basic 2FA flow
     *
     * 2. user@example.com / password123
     *    - Valid TOTP secret (generated randomly)
     *    - For testing concurrent swap scenarios
     *
     * To retrieve TOTP secrets for manual testing:
     *   SELECT email, totp_secret FROM users;
     *
     * To generate valid TOTP code (30s window):
     *   php artisan tinker
     *   $user = User::where('email', 'test@example.com')->first();
     *   echo TOTP::create($user->totp_secret)->now();
     */
    public function run(): void
    {
        $totpService = new TotpService;

        User::create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'User',
            'totp_secret' => $totpService->generateSecret(),
        ]);

        User::create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'first_name' => 'John',
            'last_name' => 'Doe',
            'middle_name' => 'Michael',
            'totp_secret' => $totpService->generateSecret(),
        ]);
    }
}
