<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class SortBy implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'entity_id', 'label' => __('Entity ID')],
            ['value' => 'created_at', 'label' => __('Created At')],
            ['value' => 'name', 'label' => __('Name')],
            ['value' => 'price', 'label' => __('Price')],
            ['value' => 'position', 'label' => __('Position')],
        ];
    }
}
