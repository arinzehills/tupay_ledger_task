<?php

namespace App\Domains\Swap\Actions;

use App\Domains\ExchangeRate\Services\RateService;
use App\Shared\ValueObjects\Money;

class CalculateSwapAction
{
    private const SLIPPAGE_BASE = 0.005; // 0.5%

    private const SLIPPAGE_TIER = 0.001; // 0.1% per 500,000 NGN

    private const SLIPPAGE_THRESHOLD = 100000000; // 1,000,000 NGN in kobo

    private RateService $rateService;

    public function __construct(RateService $rateService)
    {
        $this->rateService = $rateService;
    }

    public function execute(Money $sourceAmount): Money
    {
        $rate = $this->rateService->getRate();
        $destinationAmount = (int) bcmul(
            (string) $sourceAmount->getAmount(),
            (string) $rate,
            0
        );

        $slippagePercent = $this->calculateSlippage($sourceAmount->getAmount());
        $slippageFee = (int) bcmul(
            (string) $destinationAmount,
            $slippagePercent,
            2
        );

        $finalAmount = $destinationAmount - $slippageFee;

        return Money::fromSubunits($finalAmount);
    }

    private function calculateSlippage(int $sourceAmount): string
    {
        if ($sourceAmount <= self::SLIPPAGE_THRESHOLD) {
            return (string) self::SLIPPAGE_BASE;
        }

        $excess = $sourceAmount - self::SLIPPAGE_THRESHOLD;
        $tiersDiv = bcdiv((string) $excess, '50000000', 0);
        if (! is_string($tiersDiv)) {
            throw new \RuntimeException('BCMath calculation failed');
        }
        $tiers = (int) $tiersDiv;
        $additionalSlippage = bcmul((string) $tiers, (string) self::SLIPPAGE_TIER, 3);
        if (! is_string($additionalSlippage)) {
            throw new \RuntimeException('BCMath calculation failed');
        }

        $result = bcadd((string) self::SLIPPAGE_BASE, $additionalSlippage, 3);
        if (! is_string($result)) {
            throw new \RuntimeException('BCMath calculation failed');
        }

        return $result;
    }
}
