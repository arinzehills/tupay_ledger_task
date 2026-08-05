<?php

namespace App\Domains\Settlement\Controllers;

use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettlementDebugController
{
    public function generateSignature(Request $request): JsonResponse
    {
        if (! config('app.debug')) {
            return response()->json(['error' => 'Debug mode disabled'], 403);
        }

        $transactionId = (int) $request->query('transaction_id', '1');
        $status = $request->query('status', 'completed');
        $settledAt = $request->query('settled_at', now()->toIso8601String());

        $transaction = Transaction::find($transactionId);
        if (! $transaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        $payload = [
            'transaction_id' => $transactionId,
            'status' => $status,
            'settled_at' => $settledAt,
        ];

        $secret = (string) config('settlement.webhook_secret');
        $payloadJson = json_encode($payload);
        if ($payloadJson === false) {
            throw new \RuntimeException('Failed to encode payload');
        }
        $signature = hash_hmac('sha256', $payloadJson, $secret);

        return response()->json([
            'payload' => $payload,
            'signature' => $signature,
            'curl_example' => $this->generateCurlExample($payload, $signature),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function generateCurlExample(array $payload, string $signature): string
    {
        $data = array_merge($payload, ['signature' => $signature]);
        $dataJson = json_encode($data);
        if ($dataJson === false) {
            throw new \RuntimeException('Failed to encode curl example data');
        }

        return sprintf(
            "curl -X 'POST' '%s' -H 'Content-Type: application/json' -d '%s'",
            config('settlement.webhook_url'),
            str_replace("'", "\\'", $dataJson)
        );
    }
}
