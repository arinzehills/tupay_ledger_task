<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use App\Models\LedgerEntry;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $ngnWallet = Wallet::create([
                'user_id' => $user->id,
                'currency' => 'NGN',
            ]);

            $cnyWallet = Wallet::create([
                'user_id' => $user->id,
                'currency' => 'CNY',
            ]);

            LedgerEntry::create([
                'wallet_id' => $ngnWallet->id,
                'type' => 'credit',
                'amount' => 500000000, // 5,000,000 NGN in kobo
                'description' => 'Initial balance',
            ]);

            LedgerEntry::create([
                'wallet_id' => $cnyWallet->id,
                'type' => 'credit',
                'amount' => 50000000, // 500,000 CNY in fen
                'description' => 'Initial balance',
            ]);
        }
    }
}