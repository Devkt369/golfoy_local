<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class SortDirection implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'ASC', 'label' => __('Ascending')],
            ['value' => 'DESC', 'label' => __('Descending')],
        ];
    }
}
