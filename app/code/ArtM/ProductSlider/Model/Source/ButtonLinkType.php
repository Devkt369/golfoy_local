<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ButtonLinkType implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'manual', 'label' => __('Manual URL')],
            ['value' => 'category', 'label' => __('Category')],
        ];
    }
}
