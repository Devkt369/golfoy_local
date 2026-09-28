<?php

namespace ArtM\Common\Model\Pdf;

class InvoiceTotalsCalculator
{
    public function calculate(array $data): array
    {
        $subtotal = $this->toFloat($data['subtotal'] ?? 0);
        $grandTotal = $this->toFloat($data['grand_total'] ?? 0);
        $giftCardDiscount = $this->toFloat($data['gift_voucher_discount'] ?? 0);
        $taxAmount = $this->toFloat($data['tax_amount'] ?? 0);
        $shippingTaxAmount = $this->toFloat($data['shipping_tax_amount'] ?? 0);
        $shippingInclTax = $this->resolveShippingInclTax($data);
        $itemTaxAmount = max($taxAmount - $shippingTaxAmount, 0.0);
        $subtotalInclTax = max($grandTotal - $shippingInclTax + $giftCardDiscount, 0.0);
        $subtotalExclTax = max($subtotalInclTax - $itemTaxAmount, 0.0);
        $discountAmount = max($subtotal - $subtotalExclTax, 0.0);

        return [
            'discount_amount' => $this->round($discountAmount),
            'subtotal_excl_tax' => $this->round($subtotalExclTax),
            'subtotal_incl_tax' => $this->round($subtotalInclTax),
            'item_tax_amount' => $this->round($itemTaxAmount),
            'gift_card_amount' => $this->round($giftCardDiscount),
            'payable_amount' => $this->round($grandTotal),
        ];
    }

    private function resolveShippingInclTax(array $data): float
    {
        if (isset($data['shipping_incl_tax']) && $data['shipping_incl_tax'] !== null && $data['shipping_incl_tax'] !== '') {
            return $this->toFloat($data['shipping_incl_tax']);
        }

        return $this->toFloat($data['shipping_amount'] ?? 0) + $this->toFloat($data['shipping_tax_amount'] ?? 0);
    }

    private function toFloat($value): float
    {
        return (float) $value;
    }

    private function round(float $value): float
    {
        return round($value, 4);
    }
}