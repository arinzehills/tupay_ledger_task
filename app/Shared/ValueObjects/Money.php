<?php

namespace App\Shared\ValueObjects;

class Money
{
    private int $amount;

    public function __construct(int $amount)
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Money amount cannot be negative');
        }
        $this->amount = $amount;
    }

    public static function fromSubunits(int $subunits): self
    {
        return new self($subunits);
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function add(Money $other): self
    {
        return new self($this->amount + $other->amount);
    }

    public function subtract(Money $other): self
    {
        $result = $this->amount - $other->amount;
        if ($result < 0) {
            throw new \InvalidArgumentException('Insufficient funds');
        }
        return new self($result);
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount;
    }

    public function isGreaterThan(Money $other): bool
    {
        return $this->amount > $other->amount;
    }

    public function isLessThan(Money $other): bool
    {
        return $this->amount < $other->amount;
    }

    public function isGreaterThanOrEqual(Money $other): bool
    {
        return $this->amount >= $other->amount;
    }
}