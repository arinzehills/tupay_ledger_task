<?php

namespace Database\Seeders;

use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    /**
     * Create test wallets with subunit balances for each user.
     *
     * Balances initialized in SUBUNITS (not display amounts):
     * - NGN: kobo (1 NGN = 100 kobo)
     * - CNY: fen (1 CNY = 100 fen)
     *
     * Each test user gets:
     * - 500,000,000 kobo = 5,000,000 NGN
     * - 50,000,000 fen = 500,000 CNY
     *
     * Sufficient for testing:
     * - Single swaps (1M+ NGN transfers)
     * - Concurrent stress tests (10 parallel swaps)
     * - Balance verification and ledger integrity checks
     *
     * Currency pairs supported:
     * - NGN (Nigerian Naira) - primary source
     * - CNY (Chinese Yuan) - destination
     */
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
