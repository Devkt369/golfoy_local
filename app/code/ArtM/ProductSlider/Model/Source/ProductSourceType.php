<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ProductSourceType implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'category', 'label' => __('Category')],
            ['value' => 'products', 'label' => __('Products')],
            ['value' => 'mixed', 'label' => __('Mixed')],
        ];
    }
}
