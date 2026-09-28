<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class SortingType implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'manual', 'label' => __('Manual')],
            ['value' => 'category_position', 'label' => __('Category Position')],
            ['value' => 'newest', 'label' => __('Newest')],
            ['value' => 'price_asc', 'label' => __('Price Low to High')],
            ['value' => 'price_desc', 'label' => __('Price High to Low')],
            ['value' => 'name_asc', 'label' => __('Name A-Z')],
            ['value' => 'name_desc', 'label' => __('Name Z-A')],
            ['value' => 'sku', 'label' => __('SKU')],
            ['value' => 'random', 'label' => __('Random')],
            ['value' => 'custom_attribute', 'label' => __('Custom Attribute')],
        ];
    }
}
