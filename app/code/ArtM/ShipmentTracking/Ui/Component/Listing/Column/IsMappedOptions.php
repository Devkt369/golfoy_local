<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Ui\Component\Listing\Column;

use Magento\Framework\Data\OptionSourceInterface;

class IsMappedOptions implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => '1', 'label' => __('Mapped')],
            ['value' => '0', 'label' => __('Not Mapped')],
        ];
    }
}
