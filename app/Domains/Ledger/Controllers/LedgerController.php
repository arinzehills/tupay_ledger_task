<?php

namespace App\Domains\Ledger\Controllers;

use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LedgerController
{
    public function show(Request $request, Wallet $wallet): JsonResponse
    {
        $user = Auth::user();

        if (!$user || $wallet->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $page = (int) $request->query('page', '1');
        $perPage = (int) $request->query('per_page', '50');

        $entries = $wallet->ledgerEntries()
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'wallet_id' => $wallet->id,
            'currency' => $wallet->currency,
            'balance' => $wallet->getBalance(),
            'entries' => $entries->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'type' => $entry->type,
                    'amount' => $entry->amount,
                    'reference_id' => $entry->reference_id,
                    'created_at' => $entry->created_at,
                ];
            }),
            'pagination' => [
                'total' => $entries->total(),
                'per_page' => $entries->perPage(),
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
            ],
        ]);
    }
}