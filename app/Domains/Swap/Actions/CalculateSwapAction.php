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
        // Configure bcmath for precise financial calculations (8 decimal places)
        bcscale(8);

        $rate = $this->rateService->getRate();

        // Validate rate is positive (prevent zero/negative rate exploitation)
        if (! is_numeric($rate) || (float) $rate <= 0) {
            throw new \RuntimeException('Invalid exchange rate');
        }

        // Calculate destination amount with precision (using 8 decimal places)
        $destinationAmountStr = bcmul(
            (string) $sourceAmount->getAmount(),
            (string) $rate,
            8
        );

        if (! is_string($destinationAmountStr)) {
            throw new \RuntimeException('Exchange rate calculation failed');
        }

        $destinationAmount = (int) round((float) $destinationAmountStr, 0, PHP_ROUND_HALF_EVEN);

        $slippagePercent = $this->calculateSlippage($sourceAmount->getAmount());

        // Apply slippage with consistent precision (8 decimals, then round down to avoid overcharging)
        $slippageFeeStr = bcmul(
            (string) $destinationAmount,
            $slippagePercent,
            8
        );

        if (! is_string($slippageFeeStr)) {
            throw new \RuntimeException('Slippage calculation failed');
        }

        $slippageFee = (int) round((float) $slippageFeeStr, 0, PHP_ROUND_HALF_EVEN);

        $finalAmount = $destinationAmount - $slippageFee;

        // Validate final amount is positive (prevent negative amounts reaching Money constructor)
        if ($finalAmount <= 0) {
            throw new \InvalidArgumentException('Swap results in zero or negative destination amount after fees');
        }

        return Money::fromSubunits($finalAmount);
    }

    private function calculateSlippage(int $sourceAmount): string
    {
        if ($sourceAmount <= self::SLIPPAGE_THRESHOLD) {
            return (string) self::SLIPPAGE_BASE;
        }

        $excess = $sourceAmount - self::SLIPPAGE_THRESHOLD;

        // Use scale=1 to calculate tiers correctly (prevents rounding loss at boundaries)
        $tiersDiv = bcdiv((string) $excess, '50000000', 1);
        if (! is_string($tiersDiv)) {
            throw new \RuntimeException('Tier calculation failed');
        }

        // Floor to get complete tiers only (don't count partial tiers)
        $tiers = (int) floor((float) $tiersDiv);

        // Calculate additional slippage with 8 decimal precision
        $additionalSlippage = bcmul((string) $tiers, (string) self::SLIPPAGE_TIER, 8);
        if (! is_string($additionalSlippage)) {
            throw new \RuntimeException('Additional slippage calculation failed');
        }

        // Add base slippage with 8 decimal precision
        $result = bcadd((string) self::SLIPPAGE_BASE, $additionalSlippage, 8);
        if (! is_string($result)) {
            throw new \RuntimeException('Slippage sum calculation failed');
        }

        return $result;
    }
}
