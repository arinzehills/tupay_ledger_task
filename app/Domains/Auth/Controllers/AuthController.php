<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Actions\IssueEatAction;
use App\Domains\Auth\DTOs\ChallengeDTO;
use App\Domains\Auth\DTOs\LoginDTO;
use App\Domains\Auth\Services\TotpService;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController
{
    private TotpService $totpService;
    private IssueEatAction $issueEatAction;

    public function __construct(TotpService $totpService, IssueEatAction $issueEatAction)
    {
        $this->totpService = $totpService;
        $this->issueEatAction = $issueEatAction;
    }

    public function login(Request $request): JsonResponse
    {
        $dto = LoginDTO::fromRequest($request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]));

        $user = User::where('email', $dto->email)->first();

        if (!$user || !Hash::check($dto->password, $user->password)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $token = $user->createToken('api')->plainTextToken;

        $user->load('wallets');

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'middle_name' => $user->middle_name,
            ],
            'wallets' => $user->wallets->map(function ($wallet) {
                return [
                    'id' => $wallet->id,
                    'currency' => $wallet->currency,
                    'balance' => $wallet->getBalance(),
                ];
            }),
        ]);
    }

    public function challenge(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!$user->totp_secret) {
            return response()->json(['error' => 'TOTP not set up'], 400);
        }

        $dto = ChallengeDTO::fromRequest($request->validate([
            'totp_code' => 'required',
            'action_payload' => 'required|array',
        ]));

        if (!$this->totpService->verifyCode($user->totp_secret, $dto->totp_code)) {
            return response()->json(['error' => 'Invalid TOTP code'], 401);
        }

        $eatToken = $this->issueEatAction->execute($user, $dto->action_payload);

        return response()->json(['eat_token' => $eatToken]);
    }
}