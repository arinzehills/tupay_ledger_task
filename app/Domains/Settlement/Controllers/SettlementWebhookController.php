<?php

namespace App\Domains\Settlement\Controllers;

use App\Domains\Settlement\Actions\ProcessSettlementAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettlementWebhookController
{
    private ProcessSettlementAction $processSettlement;

    public function __construct(ProcessSettlementAction $processSettlement)
    {
        $this->processSettlement = $processSettlement;
    }

    public function handle(Request $request): JsonResponse
    {
        $validation = $this->validateWebhook($request);
        if ($validation instanceof JsonResponse) {
            return $validation;
        }

        try {
            $this->processSettlement->execute(
                $validation['transaction_id'],
                $validation['status'],
                $validation['settled_at']
            );

            return response()->json(['message' => 'Settlement processed successfully'], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Settlement processing failed'], 500);
        }
    }

    /**
     * @return array<string, mixed>|JsonResponse
     */
    private function validateWebhook(Request $request): array|JsonResponse
    {
        $all = $request->all();

        if (! isset($all['transaction_id']) || ! isset($all['status']) || ! isset($all['settled_at']) || ! isset($all['signature'])) {
            return response()->json(['error' => 'Missing required fields'], 422);
        }

        $webhookSecret = config('settlement.webhook_secret');

        // Validate webhook secret is configured (empty is production misconfiguration)
        if (empty($webhookSecret)) {
            return response()->json(['error' => 'Webhook not properly configured'], 503);
        }

        $payload = $request->except('signature');
        // Use JSON_UNESCAPED_SLASHES for consistent encoding with clients
        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($payloadJson === false) {
            return response()->json(['error' => 'Failed to encode payload'], 500);
        }
        $expectedSignature = hash_hmac('sha256', $payloadJson, $webhookSecret);

        if (! hash_equals($all['signature'], $expectedSignature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $validated = $request->validate([
            'transaction_id' => 'required|integer',
            'status' => 'required|in:completed,failed',
            'settled_at' => 'required|date_format:Y-m-d\TH:i:s\Z',
        ]);

        // Ensure transaction_id is int for proper type handling
        $validated['transaction_id'] = (int) $validated['transaction_id'];

        return $validated;
    }
}
