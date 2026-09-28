<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class DisplayType implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'slider', 'label' => __('Slider')],
            ['value' => 'grid', 'label' => __('Grid')],
        ];
    }
}
