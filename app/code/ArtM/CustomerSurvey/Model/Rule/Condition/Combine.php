<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Model\Rule\Condition;

use Magento\Rule\Model\Condition\Context;
use Magento\Rule\Model\Condition\Combine as MagentoCombine;

class Combine extends MagentoCombine
{
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->setType(self::class);
    }

    public function getNewChildSelectOptions()
    {
        $conditions = parent::getNewChildSelectOptions();

        $customConditions = [
            [
                'value' => \ArtM\CustomerSurvey\Model\Rule\Condition\Product::class,
                'label' => __('Product Attributes'),
            ],
            [
                'value' => \ArtM\CustomerSurvey\Model\Rule\Condition\ProductCategory::class,
                'label' => __('Product Categories'),
            ],
            [
                'value' => \ArtM\CustomerSurvey\Model\Rule\Condition\CustomerGroup::class,
                'label' => __('Customer Group'),
            ],
            [
                'value' => \ArtM\CustomerSurvey\Model\Rule\Condition\CustomerLoginState::class,
                'label' => __('Customer Login State'),
            ],
            [
                'value' => \ArtM\CustomerSurvey\Model\Rule\Condition\StoreView::class,
                'label' => __('Store View'),
            ],
            [
                'value' => \ArtM\CustomerSurvey\Model\Rule\Condition\Cart::class,
                'label' => __('Cart Conditions'),
            ],
            [
                'value' => \ArtM\CustomerSurvey\Model\Rule\Condition\Customer::class,
                'label' => __('Customer Conditions'),
            ],
            [
                'value' => \ArtM\CustomerSurvey\Model\Rule\Condition\Custom::class,
                'label' => __('Custom Condition (Extensible)'),
            ],
        ];

        $conditions[] = [
            'label' => __('Customer Survey Conditions'),
            'value' => $customConditions,
        ];

        return $conditions;
    }
}
