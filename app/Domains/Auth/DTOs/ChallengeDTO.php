<?php

namespace App\Domains\Auth\DTOs;

class ChallengeDTO
{
    public string $token;
    public string $totp_code;
    public array $action_payload;

    public function __construct(string $token, string $totp_code, array $action_payload)
    {
        $this->token = $token;
        $this->totp_code = $totp_code;
        $this->action_payload = $action_payload;
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            $data['token'],
            $data['totp_code'],
            $data['action_payload'],
        );
    }
}