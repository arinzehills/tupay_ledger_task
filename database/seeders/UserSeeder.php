<?php

namespace Database\Seeders;

use App\Domains\Auth\Services\TotpService;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $totpService = new TotpService();

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