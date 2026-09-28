<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Ui\Component\Listing\Column;

use Magento\Framework\Data\OptionSourceInterface;

class PartnerStatusOptions implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 1, 'label' => __('Enabled')],
            ['value' => 0, 'label' => __('Disabled')],
        ];
    }
}
