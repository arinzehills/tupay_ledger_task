<?php

namespace App\Domains\Auth\DTOs;

class ChallengeDTO
{
    public string $totp_code;
    public array $action_payload;

    public function __construct(string $totp_code, array $action_payload)
    {
        $this->totp_code = $totp_code;
        $this->action_payload = $action_payload;
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            $data['totp_code'],
            $data['action_payload'],
        );
    }
}