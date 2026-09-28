<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Free Gift Base for Magento 2
 */

namespace Amasty\Promo\Model\OptionSource;

use Magento\Framework\Data\OptionSourceInterface;

class Pages implements OptionSourceInterface
{
    public const HOME = 'home';
    public const CATEGORY = 'category';
    public const PRODUCT = 'product';
    public const SEARCH = 'search';
    public const CART = 'cart';
    public const CHECKOUT = 'checkout';
    public const OTHER = 'other';

    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::HOME,
                'label' => __('Home Page')
            ],
            [
                'value' => self::CATEGORY,
                'label' => __('Category Pages')
            ],
            [
                'value' => self::PRODUCT,
                'label' => __('Product Pages')
            ],
            [
                'value' => self::SEARCH,
                'label' => __('Search Pages')
            ],
            [
                'value' => self::CART,
                'label' => __('Shopping Cart')
            ],
            [
                'value' => self::OTHER,
                'label' => __('Another Pages')
            ]
        ];
    }
}
