<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Services\TotpService;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DebugController
{
    private TotpService $totpService;

    public function __construct(TotpService $totpService)
    {
        $this->totpService = $totpService;
    }

    public function getTotpCode(): JsonResponse
    {
        if (! config('app.debug')) {
            return response()->json(['error' => 'Debug mode disabled'], 403);
        }

        $user = Auth::user();
        if (! $user instanceof User) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (! $user->totp_secret) {
            return response()->json(['error' => 'TOTP not configured'], 400);
        }

        $code = $this->totpService->getCode($user->totp_secret);

        return response()->json([
            'totp_code' => $code,
            'user_id' => $user->id,
            'email' => $user->email,
            'valid_for_seconds' => 30,
            'warning' => 'DEBUG ENDPOINT - Only available in development mode',
        ]);
    }
}
