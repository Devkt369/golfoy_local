<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class SlidesToShow implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => '1', 'label' => __('Show 1 card (focused)')],
            ['value' => '2', 'label' => __('Show 2 cards (compact)')],
            ['value' => '3', 'label' => __('Show 3 cards (balanced)')],
            ['value' => '4', 'label' => __('Show 4 cards (standard desktop)')],
            ['value' => '5', 'label' => __('Show 5 cards (wide desktop)')],
            ['value' => '6', 'label' => __('Show 6 cards (dense)')],
            ['value' => '8', 'label' => __('Show 8 cards (max density)')],
        ];
    }
}
