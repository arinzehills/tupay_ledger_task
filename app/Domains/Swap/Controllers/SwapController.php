<?php

namespace App\Domains\Swap\Controllers;

use App\Domains\Auth\Actions\ConsumeEatAction;
use App\Domains\Swap\Services\SwapService;
use App\Shared\ValueObjects\Money;
use App\Support\Enums\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SwapController
{
    public function __construct(
        private SwapService $swapService,
        private ConsumeEatAction $consumeEatAction,
    ) {}

    public function swap(Request $request): JsonResponse
    {
        $user = Auth::user();
        $eatToken = $request->header('X-Elevated-Action-Token');

        if (!$eatToken) {
            return response()->json(['error' => 'Missing EAT token'], 401);
        }

        $validated = $request->validate([
            'source_currency' => 'required|string',
            'destination_currency' => 'required|string',
            'amount' => 'required|integer|min:1',
        ]);

        $actionPayload = [
            'destination_currency' => $validated['destination_currency'],
            'source_currency' => $validated['source_currency'],
            'amount' => $validated['amount'],
        ];

        if (!$this->consumeEatAction->execute($eatToken, $actionPayload)) {
            return response()->json(['error' => 'Invalid or expired EAT token'], 422);
        }

        try {
            $sourceAmount = Money::fromSubunits($validated['amount']);
            $sourceCurrency = Currency::from($validated['source_currency']);
            $destinationCurrency = Currency::from($validated['destination_currency']);

            $transaction = $this->swapService->swap(
                $user,
                $sourceCurrency,
                $destinationCurrency,
                $sourceAmount,
                $eatToken
            );

            return response()->json([
                'transaction_id' => $transaction->id,
                'status' => $transaction->status,
                'source_amount' => $transaction->source_amount,
                'destination_amount' => $transaction->destination_amount,
            ], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}