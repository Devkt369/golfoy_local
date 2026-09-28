<?php

namespace ArtM\CustomerSurvey\Model\Source;

use ArtM\CustomerSurvey\Model\Location;
use Magento\Framework\Data\OptionSourceInterface;

class PageType implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => Location::PAGE_TYPE_PRODUCT, 'label' => __('Product')],
            ['value' => Location::PAGE_TYPE_CATEGORY, 'label' => __('Category')],
            ['value' => Location::PAGE_TYPE_CMS, 'label' => __('CMS')],
            ['value' => Location::PAGE_TYPE_CART, 'label' => __('Cart')],
            ['value' => Location::PAGE_TYPE_CHECKOUT, 'label' => __('Checkout')],
            ['value' => Location::PAGE_TYPE_SUCCESS, 'label' => __('Success')],
            ['value' => Location::PAGE_TYPE_CUSTOMER, 'label' => __('Customer')],
            ['value' => Location::PAGE_TYPE_CUSTOM_URL, 'label' => __('Custom URL')],
        ];
    }
}
