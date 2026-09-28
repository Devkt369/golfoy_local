<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Model\Rule\Condition;

class Cart extends AbstractSimpleCondition
{
    /**
     * @var string[]
     */
    protected $attributeOptions = [
        'cart_subtotal' => 'Cart Subtotal',
        'cart_items_qty' => 'Cart Items Quantity',
        'cart_has_coupon' => 'Cart Has Coupon',
    ];

    public function getInputType()
    {
        if ($this->getAttribute() === 'cart_has_coupon') {
            return 'select';
        }

        return parent::getInputType();
    }

    public function getValueElementType()
    {
        if ($this->getAttribute() === 'cart_has_coupon') {
            return 'select';
        }

        return parent::getValueElementType();
    }

    public function getValueSelectOptions()
    {
        if ($this->getAttribute() !== 'cart_has_coupon') {
            return [];
        }

        return [
            ['value' => '1', 'label' => __('Yes')],
            ['value' => '0', 'label' => __('No')],
        ];
    }
}