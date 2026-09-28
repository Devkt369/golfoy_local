<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Free Gift Base for Magento 2
 */

namespace Amasty\Promo\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class FreeGiftPosition implements OptionSourceInterface
{
    public const POPUP = 0;
    public const BANNER = 1;

    public function toOptionArray(): array
    {
        $optionArray = [];
        foreach ($this->toArray() as $position => $label) {
            $optionArray[] = ['value' => $position, 'label' => $label];
        }

        return $optionArray;
    }

    public function toArray(): array
    {
        return [
            self::POPUP => __('Popup'),
            self::BANNER => __('Banner Slider')
        ];
    }
}
