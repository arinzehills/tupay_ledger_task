<?php

namespace App\Domains\Auth\Services;

use OTPHP\TOTP;

class TotpService
{
    public function generateSecret(): string
    {
        return TOTP::create()->getSecret();
    }

    public function verifyCode(string $secret, string $code): bool
    {
        try {
            $totp = TOTP::create($secret);

            return $totp->verify($code);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getCode(string $secret): string
    {
        $totp = TOTP::create($secret);

        return $totp->now();
    }
}
