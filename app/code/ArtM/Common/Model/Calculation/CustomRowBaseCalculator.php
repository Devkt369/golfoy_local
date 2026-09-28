<?php

namespace ArtM\Common\Model\Calculation;

use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Model\Calculation\RowBaseCalculator;

class CustomRowBaseCalculator extends RowBaseCalculator
{
    /**
     * @inheritdoc
     */
    protected function calculateWithTaxInPrice(QuoteDetailsItemInterface $item, $quantity, $round = true)
    {
        $priceInclTax = $item->getUnitPrice();
        $rate = $item->getTaxPercent();
        $customDiscount = $this->getCustomDiscount($item);

        // Apply custom discount
        $basePriceExcludingTax = $priceInclTax / (1 + $rate / 100);
        $adjustedBasePrice = $basePriceExcludingTax - $customDiscount;
        if ($adjustedBasePrice < 0) {
            $adjustedBasePrice = 0;
        }
        $adjustedPriceInclTax = $adjustedBasePrice * (1 + $rate / 100);

        // Update the row total and row total including tax
        $rowTotal = $adjustedBasePrice * $quantity;
        $rowTotalInclTax = $adjustedPriceInclTax * $quantity;

        return $this->taxDetailsItemDataObjectFactory->create()
            ->setCode($item->getCode())
            ->setType($item->getType())
            ->setRowTax($this->getRowTax($adjustedPriceInclTax, $rate, $quantity))
            ->setPrice($adjustedBasePrice)
            ->setPriceInclTax($adjustedPriceInclTax)
            ->setRowTotal($rowTotal)
            ->setRowTotalInclTax($rowTotalInclTax)
            ->setDiscountTaxCompensationAmount(0)
            ->setAssociatedItemCode($item->getAssociatedItemCode())
            ->setTaxPercent($rate)
            ->setAppliedTaxes($this->getAppliedTaxes($rate));
    }

    private function getCustomDiscount(QuoteDetailsItemInterface $item)
    {
        // Fetch the custom discount from the reward points spent
        return $item->getRewardpointsSpent() ?: 0; // Return 0 if no points are spent
    }

    /**
     * Round amount
     *
     * @param float $amount
     * @param null|float $rate
     * @param null|bool $direction
     * @param string $type
     * @param bool $round
     * @param QuoteDetailsItemInterface|null $item
     * @return float
     */
    protected function roundAmount(
        $amount,
        $rate = null,
        $direction = null,
        $type = self::KEY_REGULAR_DELTA_ROUNDING,
        $round = true,
        $item = null
    ) {
        // Implement your custom rounding logic here
        return $round ? round($amount, 2) : $amount;
    }
}
