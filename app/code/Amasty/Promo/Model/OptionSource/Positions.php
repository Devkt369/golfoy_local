<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Free Gift Base for Magento 2
 */

namespace Amasty\Promo\Model\OptionSource;

use Magento\Framework\Data\OptionSourceInterface;

class Positions implements OptionSourceInterface
{
    public const TOP_UNFIXED = 10;
    public const TOP_FIXED = 15;
    public const BOTTOM_UNFIXED = 20;
    public const BOTTOM_FIXED = 25;

    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::TOP_UNFIXED,
                'label' => __('Page Top, static')
            ],
            [
                'value' => self::TOP_FIXED,
                'label' => __('Page Top, fixed (sticky)')
            ],
            [
                'value' => self::BOTTOM_UNFIXED,
                'label' => __('Page Bottom, static')
            ],
            [
                'value' => self::BOTTOM_FIXED,
                'label' => __('Page Bottom, fixed (sticky)')
            ]
        ];
    }
}
